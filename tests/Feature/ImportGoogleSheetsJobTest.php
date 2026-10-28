<?php

namespace Tests\Feature;

use App\Jobs\ImportGoogleSheetsJob;
use App\Models\Budget;
use App\Models\Formalization;
use App\Models\Project;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\GoogleSheetsService;
use App\Support\Notify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ImportGoogleSheetsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_notifies_the_initiator_and_always_enables_files_and_registration_data(): void
    {
        Notification::fake();
        $initiator = User::factory()->create();
        $responsible = User::factory()->create();
        $counts = ['Abertura' => 2, 'Formalização' => 3, 'Orçamento' => 4, 'Pagamento' => 5];
        $service = $this->mock(GoogleSheetsService::class);
        $service->shouldReceive('importAll')->once()->with('sheet-id', $responsible->id, 7, true, true)
            ->andReturn(['counts' => $counts, 'errors' => []]);

        (new ImportGoogleSheetsJob('sheet-id', $responsible->id, 7, $initiator->id))->handle($service, app(Notify::class));

        Notification::assertSentTo($initiator, AppNotification::class, fn ($notification) => $notification->type === 'success' && $notification->meta['counts'] === $counts
            && str_contains($notification->message, 'segundo plano'));
        Notification::assertNotSentTo($responsible, AppNotification::class);
    }

    public function test_partial_failure_reports_failed_tabs_without_exposing_exception_details(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = $this->mock(GoogleSheetsService::class);
        $service->shouldReceive('importAll')->once()->andReturn([
            'counts' => ['Abertura' => 2, 'Orçamento' => 0, 'Pagamento' => 0],
            'errors' => ['Formalização' => 'private exception details'],
        ]);

        (new ImportGoogleSheetsJob('sheet-id', $user->id, 7, $user->id))->handle($service, app(Notify::class));

        Notification::assertSentTo($user, AppNotification::class, fn ($notification) => $notification->type === 'error'
            && $notification->meta['failed_sheets'] === ['Formalização']
            && ! str_contains(json_encode($notification->toArray($user)), 'private exception details'));
    }

    public function test_failed_job_notifies_the_initiator(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        (new ImportGoogleSheetsJob('sheet-id', $user->id, 7, $user->id))->failed(new RuntimeException('Worker failed'));

        Notification::assertSentTo($user, AppNotification::class, fn ($notification) => $notification->type === 'error');
    }

    #[DataProvider('rowFailureTabs')]
    public function test_row_save_failure_reports_error_with_partial_counts_and_continues_import(string $sheet, string $model): void
    {
        Notification::fake();
        Http::preventStrayRequests();
        $user = User::factory()->create();
        $failedProject = Project::factory()->create(['number' => 'on-123']);
        $successfulProject = Project::factory()->create(['number' => 'on-456']);

        $model::saving(function ($record) use ($failedProject) {
            if ($record->project_id === $failedProject->id) {
                throw new RuntimeException('Private database error');
            }
        });

        Http::fake(['docs.google.com/*' => function ($request) {
            return Http::response(['table' => [
                'cols' => [['label' => 'CÓDIGO INSCRIÇÃO MAPAS']],
                'rows' => $request['sheet'] === 'Abertura' ? [] : [
                    ['c' => [['v' => 'on-123']]],
                    ['c' => [['v' => 'on-456']]],
                ],
            ]]);
        }]);

        (new ImportGoogleSheetsJob('sheet-id', $user->id, null, $user->id))
            ->handle(app(GoogleSheetsService::class), app(Notify::class));

        $counts = ['Abertura' => 0, 'Formalização' => 2, 'Orçamento' => 2, 'Pagamento' => 2];
        $counts[$sheet] = 1;
        Notification::assertSentTo($user, AppNotification::class, fn ($notification) => $notification->type === 'error'
            && $notification->meta['counts'] === $counts
            && $notification->meta['failed_sheets'] === [$sheet]
            && ! str_contains(json_encode($notification->toArray($user)), 'Private database error'));
        Notification::assertSentToTimes($user, AppNotification::class, 1);
        $this->assertTrue($model::where('project_id', $successfulProject->id)->exists());
        $this->assertFalse($model::where('project_id', $failedProject->id)->exists());
        Http::assertSentCount(4);
    }

    public static function rowFailureTabs(): array
    {
        return [
            'formalization' => ['Formalização', Formalization::class],
            'budget' => ['Orçamento', Budget::class],
        ];
    }

    public function test_import_all_runs_every_tab_in_order_even_after_a_tab_fails(): void
    {
        $service = $this->partialMock(GoogleSheetsService::class);
        $service->shouldReceive('importSheet')->once()->with('sheet-id', 'Abertura', true, 1, 7, true)
            ->ordered()->andReturn(2);
        $service->shouldReceive('syncFormalization')->once()->with('sheet-id', 'Formalização', 1)
            ->ordered()->andThrow(new RuntimeException('Tab unavailable'));
        $service->shouldReceive('syncBudget')->once()->with('sheet-id', 'Orçamento', 1)
            ->ordered()->andReturn(3);
        $service->shouldReceive('syncPagamento')->once()->with('sheet-id', 'Pagamento', 1)
            ->ordered()->andReturn(4);

        $this->assertSame([
            'counts' => ['Abertura' => 2, 'Orçamento' => 3, 'Pagamento' => 4],
            'errors' => ['Formalização' => 'Tab unavailable'],
        ], $service->importAll('sheet-id', 1, 7));
    }
}

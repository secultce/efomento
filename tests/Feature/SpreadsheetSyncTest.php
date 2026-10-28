<?php

namespace Tests\Feature;

use App\Jobs\ImportGoogleSheetsJob;
use App\Jobs\LoadSpreadsheetProjectFilesJob;
use App\Jobs\SyncOpeningRegistrationDataJob;
use App\Models\Notice;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\GoogleSheetsService;
use App\Support\Notify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SpreadsheetSyncTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_super_admin_queues_import_with_separate_responsible_user(): void
    {
        Queue::fake();
        Http::preventStrayRequests();
        $admin = $this->admin();
        $responsible = User::factory()->create();
        $notice = Notice::factory()->create();

        $this->actingAs($admin)->from('/editais')->post(route('spreadsheets.sync'), [
            'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/sheet-123/edit#gid=42',
            'user_id' => $responsible->id,
            'fallback_notice_id' => $notice->id,
            'triggered_by_user_id' => $responsible->id,
            'with_files' => false,
            'with_registration_data' => false,
        ])->assertRedirect('/editais')->assertSessionHas('success');

        Queue::assertPushedOn('default', ImportGoogleSheetsJob::class, fn ($job) => $job->spreadsheetId === 'sheet-123'
            && $job->userId === $responsible->id
            && $job->fallbackNoticeId === $notice->id
            && $job->triggeredByUserId === $admin->id);
        Queue::assertPushed(ImportGoogleSheetsJob::class, 1);
        Http::assertNothingSent();
    }

    public function test_guests_cannot_import_or_list_users(): void
    {
        Queue::fake();
        $this->post(route('spreadsheets.sync'))->assertRedirect(route('login'));
        $this->get(route('spreadsheets.users'))->assertRedirect(route('login'));
        Queue::assertNothingPushed();
    }

    #[DataProvider('emptyFallbackValues')]
    public function test_import_can_be_queued_without_a_fallback_edital(array $fallback): void
    {
        Queue::fake();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('spreadsheets.sync'), [
            'spreadsheet_url' => 'sheet-id',
            'user_id' => $admin->id,
            ...$fallback,
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');

        Queue::assertPushed(ImportGoogleSheetsJob::class, fn ($job) => $job->fallbackNoticeId === null);
    }

    public static function emptyFallbackValues(): array
    {
        return [[[]], [['fallback_notice_id' => null]], [['fallback_notice_id' => '']]];
    }

    public function test_queued_import_resolves_edital_from_sheet_and_imports_all_tabs_without_fallback(): void
    {
        Queue::fake();
        Notification::fake();
        Http::preventStrayRequests();
        $admin = $this->admin();
        $notice = Notice::factory()->create(['external_id' => '987654']);
        $opening = [
            'LINK FICHA DE INSCRIÇÃO' => 'https://mapacultural.secult.ce.gov.br/inscricao/123456',
            'CPF / CNPJ DO PROPONENTE' => '52998224725',
            'NOME COMPLETO / RAZÃO SOCIAL DO PROPONENTE' => 'Agente de teste',
            'Nº DA INSCRIÇÃO NO MAPA DA CULTURA' => $notice->external_id,
            'N° DO PROCESSO (NUP)' => '12345678901234567',
        ];

        Http::fake(['docs.google.com/*' => function ($request) use ($opening) {
            $row = $request['sheet'] === 'Abertura'
                ? $opening
                : ['CÓDIGO INSCRIÇÃO MAPAS' => 'on-123456'];

            return Http::response(['table' => [
                'cols' => array_map(fn ($label) => ['label' => $label], array_keys($row)),
                'rows' => [['c' => array_map(fn ($value) => ['v' => $value], array_values($row))]],
            ]]);
        }]);

        $this->actingAs($admin)->post(route('spreadsheets.sync'), [
            'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/sheet-id/edit',
            'user_id' => $admin->id,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $job = Queue::pushed(ImportGoogleSheetsJob::class)->first();
        $job->handle(app(GoogleSheetsService::class), app(Notify::class));

        $this->assertDatabaseHas('projects', ['registration_id' => '123456', 'notice_id' => $notice->id]);
        Http::assertSentCount(4);
        Queue::assertPushed(LoadSpreadsheetProjectFilesJob::class);
        Queue::assertPushed(SyncOpeningRegistrationDataJob::class);
        Notification::assertSentTo($admin, AppNotification::class, fn ($notification) => $notification->type === 'success'
            && $notification->meta['counts'] === ['Abertura' => 1, 'Formalização' => 1, 'Orçamento' => 1, 'Pagamento' => 1]);
    }

    #[DataProvider('disallowedRoles')]
    public function test_other_roles_cannot_import_or_list_users(?string $role): void
    {
        Queue::fake();
        $user = User::factory()->create();
        if ($role) {
            Role::findOrCreate($role, 'web');
            $user->assignRole($role);
        }

        $this->actingAs($user)->post(route('spreadsheets.sync'))->assertForbidden();
        $this->get(route('spreadsheets.users'))->assertForbidden();
        Queue::assertNothingPushed();
    }

    public static function disallowedRoles(): array
    {
        return [[null], ['fomentation'], ['coord_fomentation'], ['financial']];
    }

    public function test_required_and_invalid_inputs_do_not_queue_an_import(): void
    {
        Queue::fake();
        $this->actingAs($this->admin())->post(route('spreadsheets.sync'), [])
            ->assertSessionHasErrors(['spreadsheet_url', 'user_id'])
            ->assertSessionDoesntHaveErrors('fallback_notice_id');

        $this->post(route('spreadsheets.sync'), [
            'spreadsheet_url' => 'https://example.com/spreadsheets/d/sheet-id/edit',
            'user_id' => 999999,
            'fallback_notice_id' => 999999,
        ])->assertSessionHasErrors(['spreadsheet_url', 'user_id', 'fallback_notice_id']);

        $this->post(route('spreadsheets.sync'), [
            'spreadsheet_url' => ['sheet-id'],
            'user_id' => 'not-a-user',
            'fallback_notice_id' => 1.5,
        ])->assertSessionHasErrors(['spreadsheet_url', 'user_id', 'fallback_notice_id']);

        Queue::assertNothingPushed();
    }

    public function test_user_list_contains_only_picker_fields(): void
    {
        $admin = $this->admin();
        $response = $this->actingAs($admin)->getJson(route('spreadsheets.users'))->assertOk();
        $response->assertJsonFragment(['id' => $admin->id, 'name' => $admin->name, 'avatar' => null]);
        $this->assertSame(['id', 'name', 'avatar'], array_keys($response->json('0')));
    }
}

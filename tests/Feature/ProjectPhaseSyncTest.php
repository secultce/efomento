<?php

namespace Tests\Feature;

use App\Jobs\SyncProjectPhasesJob;
use App\Jobs\SyncProjectPhasesProjectJob;
use App\Models\Project;
use App\Services\ProjectPhaseSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProjectPhaseSyncTest extends TestCase
{
    use RefreshDatabase;

    private function phase(int $order, int $id, int $status, bool $hasNext = false, ?string $nextFrom = null, array $extra = []): array
    {
        return array_merge([
            'order' => $order,
            'isFirstPhase' => $order === 1,
            'registrationId' => $id,
            'number' => 'on-100',
            'category' => null,
            'status' => $status,
            'sentDate' => '26/09/2023',
            'opportunity' => [
                'id' => 4000 + $order,
                'name' => "Fase {$order}",
                'registrationFrom' => '2023-09-07T00:00:00-03:00',
                'registrationTo' => '2023-09-26T23:59:00-03:00',
            ],
            'hasNextPhase' => $hasNext,
            'nextPhase' => $hasNext ? ['opportunityId' => 5000, 'registrationFrom' => $nextFrom] : null,
        ], $extra);
    }

    private function summary(array $phases, ?int $latest = null): array
    {
        return [
            'latestRegistrationId' => $latest ?? end($phases)['registrationId'],
            'latestPhase' => ['order' => count($phases)],
            'totalPhases' => count($phases),
            'phases' => $phases,
        ];
    }

    private function fakeMapas(array $summary): void
    {
        Http::swap(new Factory);

        Http::fake(function (Request $request) use ($summary) {
            $isSummary = str_contains($request->url(), 'summary=1');
            preg_match('#todas_as_fases/(\d+)#', $request->url(), $m);
            $id = (int) $m[1];

            if ($isSummary) {
                return Http::response($summary);
            }

            $phases = collect($summary['phases'])
                ->map(fn ($p) => $p + ['sections' => [['title' => null, 'fields' => [['id' => 1, 'value' => "v{$p['status']}"]]]]])
                ->all();

            return Http::response(['registration' => ['id' => $id], 'phases' => $phases]);
        });
    }

    private function sync(Project $project): void
    {
        app(ProjectPhaseSyncService::class)->sync($project);
    }

    public function test_new_project_stores_all_phases_and_schedules_next_check(): void
    {
        $project = Project::factory()->create(['registration_id' => '100']);
        $this->fakeMapas($this->summary([
            $this->phase(1, 100, 10, true, now()->addDays(30)->toIso8601String()),
            $this->phase(2, 200, 1),
        ]));

        $this->sync($project);

        $project->refresh();
        $this->assertSame(2, $project->registrationPhases()->count());
        $this->assertSame(200, $project->latest_registration_id);
        $this->assertSame(2, $project->total_phases);
        $this->assertNotNull($project->summary_hash);
        $this->assertNotNull($project->last_changed_at);
        $this->assertTrue($project->next_check_at->isTomorrow());
        $this->assertNull($project->sync_closed_at);
    }

    public function test_unchanged_summary_only_updates_check_dates(): void
    {
        $project = Project::factory()->create(['registration_id' => '100']);
        $this->fakeMapas($this->summary([$this->phase(1, 100, 1)]));
        $this->sync($project);

        $phase = $project->registrationPhases()->first();
        $changedAt = $project->fresh()->last_changed_at;
        $fetchedAt = $phase->fetched_at;

        $this->travel(1)->day();
        $this->sync($project->fresh());

        $this->assertEquals($fetchedAt, $phase->fresh()->fetched_at);
        $this->assertEquals($changedAt, $project->fresh()->last_changed_at);
        $this->assertTrue($project->fresh()->last_checked_at->gt($changedAt));
    }

    public function test_unchanged_check_does_not_touch_updated_at(): void
    {
        $project = Project::factory()->create(['registration_id' => '100']);
        $this->fakeMapas($this->summary([$this->phase(1, 100, 1)]));
        $this->sync($project);

        $updatedAt = $project->fresh()->updated_at;

        $this->travel(1)->day();
        $this->sync($project->fresh());

        $this->assertEquals($updatedAt, $project->fresh()->updated_at);
    }

    public function test_hash_is_stable_across_runs(): void
    {
        $project = Project::factory()->create(['registration_id' => '100']);
        $this->fakeMapas($this->summary([$this->phase(1, 100, 1)]));

        $this->sync($project);
        $first = $project->fresh()->summary_hash;
        $this->travel(1)->day();
        $this->sync($project->fresh());

        $this->assertSame($first, $project->fresh()->summary_hash);
        $this->assertSame(64, strlen($first));
    }

    public function test_new_phase_creates_row_and_keeps_data_registration(): void
    {
        $project = Project::factory()->create(['registration_id' => '100', 'data_registration' => ['keep' => true]]);
        $this->fakeMapas($this->summary([$this->phase(1, 100, 10, true, now()->addDays(60)->toIso8601String())]));
        $this->sync($project);

        $this->fakeMapas($this->summary([
            $this->phase(1, 100, 10, true, now()->addDays(60)->toIso8601String()),
            $this->phase(2, 200, 1),
        ]));
        $this->sync($project->fresh());

        $project->refresh();
        $this->assertSame(2, $project->registrationPhases()->count());
        $this->assertSame(200, $project->latest_registration_id);
        $this->assertSame(['keep' => true], $project->data_registration);
    }

    public function test_status_change_updates_same_row(): void
    {
        $project = Project::factory()->create(['registration_id' => '100']);
        $this->fakeMapas($this->summary([$this->phase(1, 100, 1, true, now()->addDays(30)->toIso8601String())]));
        $this->sync($project);

        $this->fakeMapas($this->summary([$this->phase(1, 100, 10, true, now()->addDays(30)->toIso8601String())]));
        $this->sync($project->fresh());

        $this->assertSame(1, $project->registrationPhases()->count());
        $this->assertSame(10, $project->registrationPhases()->first()->status);
        $this->assertNotNull($project->fresh()->last_changed_at);
    }

    public function test_final_result_without_next_phase_closes_sync(): void
    {
        $project = Project::factory()->create(['registration_id' => '100']);
        $this->fakeMapas($this->summary([$this->phase(1, 100, 10)]));

        $this->sync($project);

        $project->refresh();
        $this->assertNotNull($project->sync_closed_at);
        $this->assertNull($project->next_check_at);
    }

    public function test_waitlist_never_closes_and_rechecks_in_seven_days(): void
    {
        $project = Project::factory()->create(['registration_id' => '100']);
        $summary = $this->summary([$this->phase(1, 100, 8)]);
        $this->fakeMapas($summary);

        [$next, $close] = app(ProjectPhaseSyncService::class)->schedule($summary, false);

        $this->assertFalse($close);
        $this->assertTrue($next->isSameDay(now()->addDays(7)));
    }

    public function test_next_phase_far_away_uses_seven_days(): void
    {
        $summary = $this->summary([$this->phase(1, 100, 10, true, now()->addDays(30)->toIso8601String())]);

        [$next, $close] = app(ProjectPhaseSyncService::class)->schedule($summary, false);

        $this->assertFalse($close);
        $this->assertTrue($next->isSameDay(now()->addDays(7)));
    }

    public function test_next_phase_close_uses_day_before_opening(): void
    {
        $summary = $this->summary([$this->phase(1, 100, 10, true, now()->addDays(4)->toIso8601String())]);

        [$next] = app(ProjectPhaseSyncService::class)->schedule($summary, false);

        $this->assertTrue($next->isSameDay(now()->addDays(3)));
    }

    public function test_mapas_failure_keeps_state_and_postpones_one_hour(): void
    {
        $project = Project::factory()->create(['registration_id' => '100']);
        Http::fake(['*' => Http::response([], 500)]);

        $job = new SyncProjectPhasesProjectJob($project->id);

        try {
            $job->handle(app(ProjectPhaseSyncService::class));
            $this->fail('Exceção esperada');
        } catch (\Throwable) {
        }

        $project->refresh();
        $this->assertNull($project->summary_hash);
        $this->assertSame(0, $project->registrationPhases()->count());
        $this->assertTrue($project->next_check_at->between(now()->addMinutes(59), now()->addMinutes(61)));
    }

    public function test_registration_period_is_stored_in_app_timezone(): void
    {
        $project = Project::factory()->create(['registration_id' => '100']);
        $this->fakeMapas($this->summary([$this->phase(1, 100, 1)]));

        $this->sync($project);

        $phase = $project->registrationPhases()->first();
        $this->assertSame('2023-09-07T03:00:00+00:00', $phase->registration_from->toIso8601String());
        $this->assertSame('2023-09-27T02:59:00+00:00', $phase->registration_to->toIso8601String());
    }

    public function test_unchanged_summary_with_final_result_closes_sync(): void
    {
        $project = Project::factory()->create(['registration_id' => '100']);
        $this->fakeMapas($this->summary([$this->phase(1, 100, 10)]));

        $this->sync($project);

        Project::whereKey($project->id)->toBase()->update(['next_check_at' => null, 'sync_closed_at' => null]);
        $project->refresh();

        $this->sync($project);

        $project->refresh();
        $this->assertNull($project->next_check_at);
        $this->assertNotNull($project->sync_closed_at);
    }

    public function test_detail_without_requested_phase_does_not_persist_summary(): void
    {
        $project = Project::factory()->create(['registration_id' => '100']);
        $summary = $this->summary([$this->phase(1, 100, 1)]);

        Http::swap(new Factory);
        Http::fake(function (Request $request) use ($summary) {
            return str_contains($request->url(), 'summary=1')
                ? Http::response($summary)
                : Http::response(['registration' => ['id' => 100], 'phases' => []]);
        });

        try {
            (new SyncProjectPhasesProjectJob($project->id))->handle(app(ProjectPhaseSyncService::class));
            $this->fail('Exceção esperada');
        } catch (\UnexpectedValueException) {
        }

        $project->refresh();
        $this->assertNull($project->summary_hash);
        $this->assertSame(0, $project->registrationPhases()->count());
        $this->assertTrue($project->next_check_at->between(now()->addMinutes(59), now()->addMinutes(61)));
    }

    public function test_dispatcher_selects_only_due_active_projects(): void
    {
        Bus::fake();

        $never = Project::factory()->create();
        $due = Project::factory()->create(['next_check_at' => now()->subMinute()]);
        Project::factory()->create(['next_check_at' => now()->addDay()]);
        Project::factory()->create(['sync_closed_at' => now(), 'next_check_at' => now()->subDay()]);

        (new SyncProjectPhasesJob)->handle();

        Bus::assertBatched(fn ($batch) => $batch->jobs->pluck('projectId')->sort()->values()->all()
            === collect([$never->id, $due->id])->sort()->values()->all());
    }
}

<?php

namespace Tests\Feature\Project;

use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Models\Agent;
use App\Models\Notice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectSearchTest extends TestCase
{
    use RefreshDatabase;

    private Notice $notice;

    private array $projects;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['monitoring', 'coord_monitoring', 'super_admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->actingAs(User::factory()->create());
        $this->notice = Notice::factory()->create();
        $this->projects = [
            $this->createProject($this->notice, 'Maria José da Silva', '12345123456202612'),
            $this->createProject($this->notice, 'JOÃO SOUZA', '98765987654202599'),
            $this->createProject($this->notice, 'Coletivo Silva 2026', null),
            $this->createProject($this->notice, 'Ana Luísa Gonçalves', null),
        ];

        $this->projects[1]->stages()->where('slug', ProjectStageSlug::ABERTURA)
            ->update(['status' => ProjectStageStatus::APROVADO]);
        $this->projects[1]->stages()->where('slug', ProjectStageSlug::ANALISE_JURIDICA)
            ->update(['status' => ProjectStageStatus::EM_ANDAMENTO]);

        $this->createProject(Notice::factory()->create(), 'Maria José da Silva', '12345123456202612');
    }

    #[DataProvider('searchTerms')]
    public function test_search_filters_projects_and_phase_counts(?string $search, array $expectedIndexes): void
    {
        $expectedIds = array_map(fn ($index) => $this->projects[$index]->id, $expectedIndexes);
        $openingCount = count(array_intersect($expectedIndexes, [0, 2, 3]));
        $legalCount = in_array(1, $expectedIndexes, true) ? 1 : 0;

        $this->get(route('notices.projects', [
            'notice' => $this->notice,
            'search' => $search,
        ]))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('projects', count($expectedIds))
            ->where('projects', fn ($projects) => $projects->pluck('id')->sort()->values()->all() === $expectedIds)
            ->where('phases', function ($phases) use ($openingCount, $legalCount) {
                $totals = $phases->pluck('total', 'value');

                return $totals[ProjectStageSlug::ABERTURA->value] === $openingCount
                    && $totals[ProjectStageSlug::ANALISE_JURIDICA->value] === $legalCount
                    && $totals->sum() === $openingCount + $legalCount;
            })
        );
    }

    public static function searchTerms(): array
    {
        return [
            'masked NUP' => ['12345.123456/2026-12', [0]],
            'unmasked NUP' => ['12345123456202612', [0]],
            'masked prefix' => ['12345.123', [0]],
            'unmasked prefix' => ['12345123', [0]],
            'masked suffix' => ['/2026-12', [0]],
            'unmasked suffix' => ['202612', [0]],
            'multiple NUP matches' => ['/202', [0, 1]],
            'unknown NUP' => ['11111.111111/1111-11', []],
            'lowercase name' => ['maria', [0]],
            'uppercase name' => ['MARIA', [0]],
            'accented name' => ['josé', [0]],
            'uppercase accented name' => ['JOSÉ', [0]],
            'another accented name' => ['JOÃO', [1]],
            'unaccented name' => ['joao', [1]],
            'lowercase accented query against uppercase name' => ['João', [1]],
            'unaccented uppercase name' => ['JOAO', [1]],
            'unaccented vowels and cedilla' => ['ana luisa goncalves', [3]],
            'accented query against unaccented name' => ['MÁRIA', [0]],
            'decomposed accent in query' => ["jose\u{0301}", [0]],
            'nonadjacent name words' => ['maria silva', [0]],
            'name words in reverse order' => ['SILVA MARIA', [0]],
            'every name word must match' => ['maria souza', []],
            'extra whitespace between words' => ["  maria  \t silva  ", [0]],
            'partial name words' => ['mar sil', [0]],
            'name without digits does not match every NUP' => ['silva', [0, 2]],
            'unknown name' => ['inexistente', []],
            'punctuation without digits' => ['./-', []],
            'combining accent alone does not match every name' => ["\u{0301}", []],
            'mixed letters and digits' => ['silva 2026', [0]],
            'mixed name words and masked NUP' => ['SILVA MARIA /2026-12', [0]],
            'mixed NUP and name in reverse order' => ['12345.123 josé', [0]],
            'letters adjacent to digits' => ['silva2026', [0]],
            'name and NUP must belong to the same project' => ['silva 2025', []],
            'unknown name with existing NUP' => ['inexistente 2026', []],
            'numeric query does not match name digits' => ['2026', [0]],
            'unknown mixed term' => ['inexistente 111111', []],
            'single zero is not an empty search' => ['0', [0, 1]],
            'SQL wildcards alone do not match everything' => ['%_', []],
            'padded NUP' => ['  12345.123456/2026-12  ', [0]],
            'padded name' => ['  maria  ', [0]],
            'empty search' => ['', [0, 1, 2, 3]],
            'whitespace search' => ['   ', [0, 1, 2, 3]],
            'missing search' => [null, [0, 1, 2, 3]],
        ];
    }

    public function test_phase_filter_preserves_search_counts_for_other_phases(): void
    {
        $this->get(route('notices.projects', [
            'notice' => $this->notice,
            'search' => '/202',
            'phase' => ProjectStageSlug::ABERTURA->value,
        ]))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('projects', 1)
            ->where('projects.0.id', $this->projects[0]->id)
            ->where('phases', function ($phases) {
                $totals = $phases->pluck('total', 'value');

                return $totals[ProjectStageSlug::ABERTURA->value] === 1
                    && $totals[ProjectStageSlug::ANALISE_JURIDICA->value] === 1
                    && $totals->sum() === 2;
            })
        );
    }

    public function test_scope_ignores_whitespace_without_relying_on_request_middleware(): void
    {
        $this->assertEqualsCanonicalizing(
            array_map(fn (Project $project) => $project->id, $this->projects),
            $this->notice->projects()->search(" \t\n ")->pluck('projects.id')->all(),
        );
    }

    private function createProject(Notice $notice, string $name, ?string $nup): Project
    {
        $project = Project::factory()->for($notice)->for(Agent::factory()->state(['name' => $name]))->create();
        $project->opening->update(['opening_nup' => $nup]);

        return $project;
    }
}

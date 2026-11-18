<?php

namespace Tests\Feature\Notice;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NoticeSearchTest extends TestCase
{
    use RefreshDatabase;

    private array $notices;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->notices = [
            Notice::factory()->create(['name' => 'Ação Cultural do Ceará', 'nup' => '12345123456202612']),
            Notice::factory()->create(['name' => 'MÚSICA POPULAR CEARENSE', 'nup' => '98765.987654/2025-99']),
            Notice::factory()->create(['name' => 'Ação Cultural 2026', 'nup' => null]),
            Notice::factory()->create(['name' => 'Teatro de Rua', 'nup' => '00000.000000/2026-99']),
        ];
    }

    #[DataProvider('searchTerms')]
    public function test_search_matches_notice_titles_and_nups(?string $search, array $expectedIndexes): void
    {
        $expectedIds = array_map(fn ($index) => $this->notices[$index]->id, $expectedIndexes);

        $this->get(route('notices.index', ['search' => $search]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('notices', count($expectedIds))
                ->where('notices', fn ($notices) => $notices->pluck('id')->sort()->values()->all() === $expectedIds)
            );
    }

    public static function searchTerms(): array
    {
        return [
            'masked query against unmasked NUP' => ['12345.123456/2026-12', [0]],
            'unmasked query against masked NUP' => ['98765987654202599', [1]],
            'masked partial NUP' => ['/2025-99', [1]],
            'unmasked partial NUP' => ['98765987', [1]],
            'accent insensitive title words in reverse order' => ['CEARA ACAO', [0]],
            'mixed title and NUP excludes title-only numeric matches' => ['acao 2026', [0]],
            'title and NUP must match same notice' => ['acao 2025', []],
            'unknown NUP' => ['11111.111111/1111-11', []],
            'blank query' => ['   ', [0, 1, 2, 3]],
        ];
    }

    public function test_partial_reload_returns_search_results_and_the_current_query(): void
    {
        $this->get(route('notices.index', ['search' => 'acao /2026-12']), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(Request::create('/')) ?? '',
            'X-Inertia-Partial-Component' => 'Notices',
            'X-Inertia-Partial-Data' => 'notices,filters',
        ])->assertOk()
            ->assertJsonPath('props.filters.search', 'acao /2026-12')
            ->assertJsonCount(1, 'props.notices')
            ->assertJsonPath('props.notices.0.id', $this->notices[0]->id)
            ->assertJsonMissingPath('props.totais');
    }
}

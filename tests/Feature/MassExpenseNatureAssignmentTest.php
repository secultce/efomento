<?php

namespace Tests\Feature;

use App\Enums\ExpenseNature;
use App\Enums\Role;
use App\Models\Budget;
use App\Models\BudgetAllocation;
use App\Models\Installment;
use App\Models\Notice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class MassExpenseNatureAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (Role::values() as $name) {
            SpatieRole::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function test_it_assigns_the_expense_nature_to_all_selected_projects(): void
    {
        $notice = $this->noticeWithNatures([ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA]);
        $selected = Project::factory()->count(2)->create(['notice_id' => $notice->id]);
        $other = Project::factory()->create(['notice_id' => $notice->id]);

        $this->actingAs($this->fomentationUser())
            ->post(route('projects.assign-expense-nature'), [
                'selected_projects' => $selected->pluck('id')->all(),
                'expense_nature' => ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA->value,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        foreach ($selected as $project) {
            $this->assertSame(
                ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA,
                $project->opening->fresh()->expense_nature
            );
        }
        $this->assertNull($other->opening->fresh()->expense_nature);
    }

    public function test_it_replaces_an_existing_expense_nature(): void
    {
        $notice = $this->noticeWithNatures([
            ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA,
            ExpenseNature::CONTRIBUICOES_COM_FINS_LUCRATIVOS,
        ]);
        $project = Project::factory()->create(['notice_id' => $notice->id]);
        $project->opening->update(['expense_nature' => ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA]);

        $this->actingAs($this->fomentationUser())
            ->post(route('projects.assign-expense-nature'), [
                'selected_projects' => [$project->id],
                'expense_nature' => ExpenseNature::CONTRIBUICOES_COM_FINS_LUCRATIVOS->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(ExpenseNature::CONTRIBUICOES_COM_FINS_LUCRATIVOS, $project->opening->fresh()->expense_nature);
    }

    public function test_it_rejects_when_the_budget_allocation_spreadsheet_was_not_imported(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->fomentationUser())
            ->post(route('projects.assign-expense-nature'), [
                'selected_projects' => [$project->id],
                'expense_nature' => ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA->value,
            ])
            ->assertSessionHasErrors(['message' => 'A planilha de dotação orçamentária ainda não foi importada para este edital. Faça o upload das informações orçamentárias antes de atribuir a natureza da despesa.']);

        $this->assertNull($project->opening->fresh()->expense_nature);
    }

    public function test_it_rejects_a_nature_missing_from_the_spreadsheet_and_changes_nothing(): void
    {
        $notice = $this->noticeWithNatures([ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA]);
        $projects = Project::factory()->count(2)->create(['notice_id' => $notice->id]);

        $this->actingAs($this->fomentationUser())
            ->post(route('projects.assign-expense-nature'), [
                'selected_projects' => $projects->pluck('id')->all(),
                'expense_nature' => ExpenseNature::CONTRIBUICOES_SEM_FINS_LUCRATIVOS->value,
            ])
            ->assertSessionHasErrors('message');

        foreach ($projects as $project) {
            $this->assertNull($project->opening->fresh()->expense_nature);
        }
    }

    public function test_it_rejects_the_batch_when_a_project_has_no_opening(): void
    {
        $notice = $this->noticeWithNatures([ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA]);
        $withOpening = Project::factory()->create(['notice_id' => $notice->id]);
        $withoutOpening = Project::factory()->create(['notice_id' => $notice->id]);
        $withoutOpening->opening->delete();

        $this->actingAs($this->fomentationUser())
            ->post(route('projects.assign-expense-nature'), [
                'selected_projects' => [$withOpening->id, $withoutOpening->id],
                'expense_nature' => ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA->value,
            ])
            ->assertSessionHasErrors(['message' => "O projeto {$withoutOpening->number} não possui abertura."]);

        $this->assertNull($withOpening->opening->fresh()->expense_nature);
    }

    public function test_it_validates_the_payload(): void
    {
        $this->actingAs($this->fomentationUser())
            ->post(route('projects.assign-expense-nature'), [
                'selected_projects' => [],
                'expense_nature' => 'INVALIDA',
            ])
            ->assertSessionHasErrors(['selected_projects', 'expense_nature']);
    }

    public function test_it_requires_a_fomentation_role(): void
    {
        $notice = $this->noticeWithNatures([ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA]);
        $project = Project::factory()->create(['notice_id' => $notice->id]);

        $this->actingAs(User::factory()->create())
            ->post(route('projects.assign-expense-nature'), [
                'selected_projects' => [$project->id],
                'expense_nature' => ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA->value,
            ])
            ->assertForbidden();

        $this->assertNull($project->opening->fresh()->expense_nature);
    }

    public function test_it_rejects_the_whole_batch_when_a_project_has_an_installment_of_another_nature(): void
    {
        $notice = $this->noticeWithNatures([
            ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA,
            ExpenseNature::CONTRIBUICOES_COM_FINS_LUCRATIVOS,
        ]);
        $linked = Project::factory()->create(['notice_id' => $notice->id]);
        $linked->opening->update(['expense_nature' => ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA]);
        $this->linkInstallment($linked, $this->allocationOf($notice, ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA));
        $free = Project::factory()->create(['notice_id' => $notice->id]);

        $this->actingAs($this->fomentationUser())
            ->post(route('projects.assign-expense-nature'), [
                'selected_projects' => [$free->id, $linked->id],
                'expense_nature' => ExpenseNature::CONTRIBUICOES_COM_FINS_LUCRATIVOS->value,
            ])
            ->assertSessionHasErrors('message');

        $this->assertStringContainsString('abra um chamado', session('errors')->first('message'));
        $this->assertStringContainsString((string) $linked->number, session('errors')->first('message'));
        $this->assertSame(ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA, $linked->opening->fresh()->expense_nature);
        $this->assertNull($free->opening->fresh()->expense_nature);
    }

    public function test_it_allows_the_nature_of_the_already_linked_allocation(): void
    {
        $notice = $this->noticeWithNatures([ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA]);
        $project = Project::factory()->create(['notice_id' => $notice->id]);
        $this->linkInstallment($project, $this->allocationOf($notice, ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA));

        $this->actingAs($this->fomentationUser())
            ->post(route('projects.assign-expense-nature'), [
                'selected_projects' => [$project->id],
                'expense_nature' => ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA, $project->opening->fresh()->expense_nature);
    }

    public function test_an_installment_linked_to_an_allocation_without_nature_does_not_block(): void
    {
        $notice = $this->noticeWithNatures([ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA]);
        $project = Project::factory()->create(['notice_id' => $notice->id]);
        $this->linkInstallment($project, BudgetAllocation::factory()->create([
            'notice_id' => $notice->id,
            'expense_nature' => null,
        ]));

        $this->actingAs($this->fomentationUser())
            ->post(route('projects.assign-expense-nature'), [
                'selected_projects' => [$project->id],
                'expense_nature' => ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA, $project->opening->fresh()->expense_nature);
    }

    public function test_opening_tab_rejects_changing_the_nature_of_a_project_with_an_installment_of_another_nature(): void
    {
        $notice = $this->noticeWithNatures([ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA]);
        $project = Project::factory()->create(['notice_id' => $notice->id]);
        $project->opening->update(['expense_nature' => ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA]);
        $this->linkInstallment($project, $this->allocationOf($notice, ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA));

        $this->actingAs($this->fomentationUser())
            ->patch(route('projects.openings.update', [$project, $project->opening]), [
                'opening' => [
                    'creditor_number' => '999',
                    'expense_nature' => ExpenseNature::CONTRIBUICOES_SEM_FINS_LUCRATIVOS->value,
                ],
            ])
            ->assertSessionHasErrors('message');

        $this->assertStringContainsString('abra um chamado', session('errors')->first('message'));
        $opening = $project->opening->fresh();
        $this->assertSame(ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA, $opening->expense_nature);
        $this->assertNotSame('999', $opening->creditor_number);
    }

    public function test_opening_tab_still_saves_when_the_nature_is_unchanged(): void
    {
        $notice = $this->noticeWithNatures([ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA]);
        $project = Project::factory()->create(['notice_id' => $notice->id]);
        $project->opening->update(['expense_nature' => ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA]);
        $this->linkInstallment($project, $this->allocationOf($notice, ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA));

        $this->actingAs($this->fomentationUser())
            ->patch(route('projects.openings.update', [$project, $project->opening]), [
                'opening' => [
                    'creditor_number' => '999',
                    'expense_nature' => ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA->value,
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('999', $project->opening->fresh()->creditor_number);
    }

    public function test_index_exposes_only_the_natures_present_in_the_imported_spreadsheet(): void
    {
        $notice = $this->noticeWithNatures([
            ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA,
            ExpenseNature::CONTRIBUICOES_SEM_FINS_LUCRATIVOS,
        ]);
        BudgetAllocation::factory()->create(['notice_id' => $notice->id, 'expense_nature' => null]);

        $this->actingAs($this->fomentationUser())
            ->get(route('notices.projects', $notice))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('availableExpenseNatures', 2)
                ->where('availableExpenseNatures.0.value', ExpenseNature::CONTRIBUICOES_SEM_FINS_LUCRATIVOS->value)
                ->where('availableExpenseNatures.1.value', ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA->value)
            );
    }

    public function test_index_exposes_no_natures_when_the_spreadsheet_was_not_imported(): void
    {
        $notice = Notice::factory()->create();

        $this->actingAs($this->fomentationUser())
            ->get(route('notices.projects', $notice))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('availableExpenseNatures', 0));
    }

    /**
     * @param  array<int, ExpenseNature>  $natures
     */
    private function noticeWithNatures(array $natures): Notice
    {
        $notice = Notice::factory()->create();

        foreach ($natures as $nature) {
            BudgetAllocation::factory()->count(2)->create([
                'notice_id' => $notice->id,
                'expense_element' => $nature->code(),
                'expense_nature' => $nature,
            ]);
        }

        return $notice;
    }

    private function allocationOf(Notice $notice, ExpenseNature $nature): BudgetAllocation
    {
        return BudgetAllocation::where('notice_id', $notice->id)
            ->where('expense_nature', $nature->value)
            ->firstOrFail();
    }

    private function linkInstallment(Project $project, BudgetAllocation $allocation): void
    {
        $budget = Budget::factory()->create(['project_id' => $project->id]);

        Installment::factory()->create([
            'budget_id' => $budget->id,
            'budget_allocation_id' => $allocation->id,
            'installment_number' => 1,
        ]);
    }

    private function fomentationUser(): User
    {
        return User::factory()->create()->assignRole(Role::FOMENTATION->value);
    }
}

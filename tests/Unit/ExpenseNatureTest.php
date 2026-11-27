<?php

namespace Tests\Unit;

use App\Enums\ExpenseNature;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExpenseNatureTest extends TestCase
{
    #[DataProvider('expenseElements')]
    public function test_resolves_the_nature_from_the_expense_element_column(
        string $expenseElement,
        ExpenseNature $expected
    ): void {
        $this->assertSame($expected, ExpenseNature::fromExpenseElement($expenseElement));
    }

    public static function expenseElements(): array
    {
        return [
            'pessoa física' => ['339048 - OUTROS AUXÍLIOS FINANCEIROS A PESSOAS FÍSICAS', ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA],
            'contribuições 50' => ['335041 - CONTRIBUIÇÕES ', ExpenseNature::CONTRIBUICOES_SEM_FINS_LUCRATIVOS],
            'contribuições 60' => ['336041 - CONTRIBUIÇÕES ', ExpenseNature::CONTRIBUICOES_COM_FINS_LUCRATIVOS],
            'somente código' => ['339048', ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA],
            'código pontuado' => ['3.3.90.48', ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA],
            'código seguido de outro número' => ['339048 01 - OUTROS AUXÍLIOS', ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA],
        ];
    }

    public function test_returns_null_for_unknown_or_empty_values(): void
    {
        $this->assertNull(ExpenseNature::fromExpenseElement('339039 - OUTROS SERVIÇOS'));
        $this->assertNull(ExpenseNature::fromExpenseElement('CONTRIBUIÇÕES'));
        $this->assertNull(ExpenseNature::fromExpenseElement(''));
        $this->assertNull(ExpenseNature::fromExpenseElement(null));
    }
}

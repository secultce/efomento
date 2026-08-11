<?php

namespace App\Support\Spreadsheet\Maps;

use App\Support\Spreadsheet\SpreadsheetMap;

class BudgetAllocationSpreadsheetMap implements SpreadsheetMap
{
    public static function definition(): array
    {
        return [
            'management_unit' => [
                'gestao_unidade',
                'gestão_unidade',
                'gestao/unidade',
                'gestão/unidade',
                'gestao unidade',
                'gestão unidade',
            ],
            'work_program' => [
                'programa_de_trabalho',
                'programa de trabalho',
            ],
            'objective' => [
                'objetivo',
            ],
            'deliverable' => [
                'entrega',
            ],
            'budget_function' => [
                'funcao',
                'função',
            ],
            'budget_subfunction' => [
                'subfuncao',
                'subfunção',
                'sub funcao',
                'sub função',
            ],
            'project_activity' => [
                'projeto_atividade',
                'projeto/atividade',
                'projeto atividade',
            ],
            'expense_element' => [
                'elemento_de_despesa',
                'elemento de despesa',
            ],
            'funding_source' => [
                'fonte_de_recursos',
                'fonte de recursos',
            ],
            'mapp' => [
                'mapp',
            ],
            'finalistic_project' => [
                'projeto_finalistico',
                'projeto_finalístico',
                'projeto finalistico',
                'projeto finalístico',
            ],
            'region_code' => [
                'cod_da_regiao',
                'cód_da_região',
                'cod. da regiao',
                'cód. da região',
                'codigo_da_regiao',
                'código_da_região',
                'codigo da regiao',
                'código da região',
            ],
            'planning_macroregion' => [
                'macrorregiao_de_planejamento',
                'macrorregião_de_planejamento',
                'macrorregiao de planejamento',
                'macrorregião de planejamento',
            ],
            'allocation_code' => [
                'codigo_da_dotacao',
                'código_da_dotação',
                'codigo da dotacao',
                'código da dotação',
                'cod_da_dotacao',
                'cód_da_dotação',
            ],
            'allocation_number' => [
                'numero_da_dotacao_completa',
                'número_da_dotação_completa',
                'numero da dotacao completa',
                'número da dotação completa',
                'numero_completo_da_dotacao',
                'número_completo_da_dotação',
                'numero completo da dotacao',
                'número completo da dotação',
            ],
        ];
    }

    public static function required(): array
    {
        return [
            'allocation_code',
            'allocation_number',
        ];
    }

    public static function labels(): array
    {
        return [
            'management_unit' => 'Gestão/Unidade',
            'work_program' => 'Programa de trabalho',
            'objective' => 'Objetivo',
            'deliverable' => 'Entrega',
            'budget_function' => 'Função',
            'budget_subfunction' => 'Subfunção',
            'project_activity' => 'Projeto/Atividade',
            'expense_element' => 'Elemento de Despesa',
            'funding_source' => 'Fonte de Recursos',
            'mapp' => 'MAPP',
            'finalistic_project' => 'Projeto Finalístico',
            'region_code' => 'Cód. da Região',
            'planning_macroregion' => 'Macrorregião de Planejamento',
            'allocation_code' => 'Código da Dotação',
            'allocation_number' => 'Número da dotação completa',
        ];
    }
}

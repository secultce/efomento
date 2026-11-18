<?php

namespace App\Support\Spreadsheet\Maps;

use App\Support\Spreadsheet\SpreadsheetMap;

class InstallmentSpreadsheetMap implements SpreadsheetMap
{
    public static function definition(): array
    {
        return [
            'data_nl' => [
                'data_nl',
                'data nl',
                'data_n_l',
                'data n l',
                'data_liquidacao',
                'data_liquidação',
            ],

            'nota_de_liquidacao' => [
                'nota_de_liquidacao',
                'nota_de_liquidação',
                'nota de liquidacao',
                'nota de liquidação',
                'nl',
            ],

            'liquidado' => [
                'liquidado',
                'Liquidado',
            ],

            'data_ob' => [
                'data_ob',
                'data ob',
                'data_o_b',
                'data o b',
                'data_pagamento',
                'data pagamento',
            ],

            'ordem_bancaria' => [
                'ordem_bancaria',
                'ordem_bancária',
                'ordem bancaria',
                'ordem bancária',
                'ob',
            ],

            'fonte_completa' => [
                'fonte_completa',
                'fonte completa',
            ],

            'natureza_despesa' => [
                'natureza_despesa',
                'natureza despesa',
                'natureza_da_despesa',
                'natureza da despesa',
            ],

            'processo' => [
                'processo',
                'nup',
                'numero_processo',
                'número_processo',
                'processo_sei',
            ],

            'credor' => [
                'credor',
            ],

            'nome_credor' => [
                'nome_credor',
                'nome_do_credor',
                'nome do credor',
            ],

            'credor_retencao' => [
                'credor_retencao',
                'credor_retenção',
                'credor_da_retencao',
                'credor_da_retenção',
                'credor da retencao',
                'credor da retenção',
            ],

            'domicilio_bancario_origem' => [
                'domicilio_bancario_origem',
                'domicílio_bancário_origem',
                'domicilio bancario origem',
                'domicílio bancário origem',
                'domicilio_bancario_origem_ob',
                'domicílio_bancário_origem_ob',
                'domicilio bancario origem ob',
                'domicílio bancário origem ob',
                'domicílio bancário origem (ob)',
                'domicilio bancario origem (ob)',
            ],

            'empenhado' => [
                'empenhado',
                'valor_empenhado',
                'valor empenhado',
            ],

            'liquidado' => [
                'liquidado',
                'valor_liquidado',
                'valor liquidado',
            ],

            'pago' => [
                'pago',
                'valor_pago',
                'valor pago',
            ],
        ];
    }

    public static function required(): array
    {
        return [
            'processo',
        ];
    }
}

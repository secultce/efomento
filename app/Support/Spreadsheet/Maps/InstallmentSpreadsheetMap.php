<?php

namespace App\Support\Spreadsheet\Maps;

use App\Support\Spreadsheet\SpreadsheetMap;

class InstallmentSpreadsheetMap implements SpreadsheetMap
{
    public static function definition(): array
    {
        return [
            'nup' => [
                'processo',
                'nup',
                'numero_processo',
                'processo_sei',
            ],

            'liquidado' => [
                'liquidado',
                'valor_liquidado',
            ],

            'pago' => [
                'pago',
                'valor_pago',
            ],

            'nota_de_liquidacao' => [
                'nota_de_liquidação',
                'nota_de_liquidacao',
                'nl',
            ],

            'ordem_bancaria' => [
                'ordem_bancária',
                'ordem_bancaria',
                'ob',
            ],

            'data_n_l' => [
                'data_n_l',
                'data_liquidacao',
            ],

            'data_o_b' => [
                'data_o_b',
                'data_pagamento',
            ],
        ];
    }

    public static function required(): array
    {
        return [];
    }
}

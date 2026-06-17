<?php

namespace App\Support\Spreadsheet;

interface SpreadsheetMap
{
    public static function definition(): array;

    public static function required(): array;
}

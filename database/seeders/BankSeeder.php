<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        $banks = json_decode(
            file_get_contents(database_path('seeders/data/banks.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        foreach (array_chunk($banks, 100) as $chunk) {
            DB::table('banks')->upsert($chunk, ['ispb'], ['code', 'name', 'short_name']);
        }
    }
}

<?php

use Database\Seeders\BankSeeder;

return [

    /*
    |--------------------------------------------------------------------------
    | Database seeding mode
    |--------------------------------------------------------------------------
    |
    | Supported values: none, users, all.
    |
    */

    'mode' => env('SEED_MODE', 'none'),

    // Shared seeders for config tables
    'common' => [
        BankSeeder::class,
    ],

];

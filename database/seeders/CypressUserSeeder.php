<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CypressUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Lara Pimentel',
                'email' => 'lara.pimentel@secult.ce.gov.br',
            ],
            [
                'name' => 'Glaucivane Portela',
                'email' => 'glaucivane.portela@secult.ce.gov.br',
            ],
            [
                'name' => 'J Ferreira',
                'email' => 'jferreira@secult.ce.gov.br',
            ],
            [
                'name' => 'Claudia Moreira',
                'email' => 'claudia.moreira@secult.ce.gov.br',
            ],
            [
                'name' => 'Cicero Gondim',
                'email' => 'cicero.gondim@secult.ce.gov.br',
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                [
                    'email' => $userData['email'],
                ],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}

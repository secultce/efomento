<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class CypressRoleSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            'lara.pimentel@secult.ce.gov.br' => Role::FOMENTATION->value,
            'glaucivane.portela@secult.ce.gov.br' => Role::LEGAL_ANALYSIS->value,
            'jferreira@secult.ce.gov.br' => Role::BUDGETARY->value,
            'claudia.moreira@secult.ce.gov.br' => Role::FINANCIAL->value,
            'cicero.gondim@secult.ce.gov.br' => Role::COORD_MONITORING->value,
        ];

        foreach ($users as $email => $role) {
            $user = User::where('email', $email)->firstOrFail();

            $user->syncRoles([$role]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $json = file_get_contents(database_path('seeders/data/users.json'));
        $users = json_decode($json, true) ?: [];

        DB::transaction(function () use ($users) {
            $emails = array_column($users, 'email');

            if (! empty($emails)) {
                // Delete via model (não em massa) para disparar eventos e limpar model_has_roles
                User::whereNotIn('email', $emails)->get()->each->delete();
            }

            foreach ($users as $user) {
                User::firstOrCreate(
                    ['email' => $user['email']],
                    $user
                );
            }

            User::whereEmail('efomento@secult.ce.gov.br')->first()?->assignRole(Role::SUPER_ADMIN);
        });
    }
}

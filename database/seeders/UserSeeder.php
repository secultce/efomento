<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $json = file_get_contents(database_path('seeders/data/users.json'));
        $users = json_decode($json, true);

        $emails = array_column($users, 'email');
        User::whereNotIn('email', $emails)->delete();

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                $user
            );
        }

        User::whereEmail('efomento@secult.ce.gov.br')->first()?->assignRole(Role::SUPER_ADMIN);
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class CypressUserSeeder extends Seeder
{
    public function run(): void
    {
        $fixture = json_decode(
            File::get(base_path('cypress/fixtures/users.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        if (! is_array($fixture) || $fixture === []) {
            throw new RuntimeException('The Cypress users fixture must contain user accounts.');
        }

        $users = [];

        foreach ($fixture as $role => $account) {
            foreach (['name', 'email', 'password'] as $field) {
                if (! is_array($account) || ! is_string($account[$field] ?? null) || trim($account[$field]) === '') {
                    throw new RuntimeException("Invalid Cypress account [{$role}]: missing or empty {$field}.");
                }
            }

            if (! filter_var($account['email'], FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException("Invalid email for Cypress account [{$role}].");
            }

            $user = [
                'name' => $account['name'],
                'email' => $account['email'],
                'password' => $account['password'],
            ];
            $email = $user['email'];

            if (isset($users[$email]) && $users[$email] !== $user) {
                throw new RuntimeException("Conflicting Cypress accounts for email [{$email}].");
            }

            $users[$email] = $user;
        }

        DB::transaction(function () use ($users) {
            foreach ($users as $user) {
                User::updateOrCreate(
                    ['email' => $user['email']],
                    [
                        'name' => $user['name'],
                        'password' => Hash::make($user['password']),
                    ]
                );
            }
        });
    }
}

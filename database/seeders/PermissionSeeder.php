<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $modules = [
            'budget_allocation',
            'legal_term',
            'term_summary',
            'ci',
            'dispatch',
            'legal_opinion',
            'budget_opinion',
            'opening',
            'LegalAnalysis',
            'formalization',
            'budget',
            'payment',
            'users',
            'groups',
        ];

        $actions = [
            'create',
            'view_own',
            'view_any',
            'edit_own',
            'edit_any',
            'delete_own',
            'delete_any',
            'assign_supervisor',
        ];

        // Criar permissions
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$module}.{$action}",
                    'guard_name' => 'web',
                ]);
            }
        }

        // Criar roles
        $roles = [
            'fomentation',
            'coord_fomentation',
            'financial',
            'coord_financial',
            'LegalAnalysis',
            'coord_legal',
            'budgetary',
            'coord_budgetary',
            'monitoring',
            'coord_monitoring',
            'tracking',
            'super_admin',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        // Super Admin = tudo
        Role::findByName('super_admin')
            ->syncPermissions(Permission::all());
    }
}

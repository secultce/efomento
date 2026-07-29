<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class GroupController extends Controller
{
    private array $modules = [
        'budget_allocation' => 'Vinculação Orçamentária',
        'legal_term' => 'Termo Jurídico',
        'term_summary' => 'Extrato do Termo Jurídico',
        'ci' => 'CI',
        'dispatch' => 'Despacho',
        'legal_opinion' => 'Parecer Jurídico',
        'budget_opinion' => 'Parecer Orçamentário',
        'opening' => 'Abertura',
        'LegalAnalysis' => 'Análise Jurídica',
        'formalization' => 'Formalização',
        'budget' => 'Orçamento e Parcelas',
        'payment' => 'Pagamento Financeiro',
        'users' => 'Gerenciamento de Usuários',
        'groups' => 'Gerenciamento de Grupos',
    ];

    private array $roleLabels = [
        'fomentation' => 'Fomento',
        'coord_fomentation' => 'Coord. Fomento',
        'financial' => 'Financeiro',
        'coord_financial' => 'Coord. Financeiro',
        'LegalAnalysis' => 'Jurídico',
        'coord_legal' => 'Coord. Jurídico',
        'budgetary' => 'Orçamentário',
        'coord_budgetary' => 'Coord. Orçamentário',
        'monitoring' => 'Monitoramento',
        'coord_monitoring' => 'Coord. Monitoramento',
        'tracking' => 'Acompanhamento',
        'super_admin' => 'Super Admin',
    ];

    private array $actions = [
        ['key' => 'create',     'label' => 'Criar'],
        ['key' => 'view_own',   'label' => 'Visualizar o próprio'],
        ['key' => 'view_any',   'label' => 'Visualizar outros'],
        ['key' => 'edit_own',   'label' => 'Editar o próprio'],
        ['key' => 'edit_any',   'label' => 'Editar outros'],
        ['key' => 'delete_own', 'label' => 'Excluir o próprio'],
        ['key' => 'delete_any', 'label' => 'Excluir outros'],
        ['key' => 'assign_supervisor', 'label' => 'Atribuir Supervisor'],
    ];

    public function index()
    {
        $allPermissions = Permission::pluck('name')->toArray();

        $roles = Role::with('permissions')
            ->get()
            ->map(fn ($role) => [
                'name' => $role->name,
                'label' => $this->roleLabels[$role->name] ?? $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ]);

        $modules = collect($this->modules)
            ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
            ->values();

        $users = User::with(['roles', 'avatarFile'])->get()->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatarUrl(),
            'roles' => $user->roles->map(fn ($role) => [
                'name' => $role->name,
                'label' => $this->roleLabels[$role->name] ?? $role->name,
            ])->values()->toArray(),
        ]);

        return Inertia::render('Groups/Index', [
            'modules' => $modules,
            'actions' => $this->actions,
            'allPermissions' => $allPermissions,
            'roles' => $roles,
            'users' => $users,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*.name' => 'required|string|exists:roles,name',
            'roles.*.permissions' => 'present|array',
            'roles.*.permissions.*' => 'string|exists:permissions,name',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($validated['roles'] as $roleData) {
            $role = Role::findByName($roleData['name']);
            $role->syncPermissions($roleData['permissions']);
        }

        return back()->with('success', 'Permissões atualizadas com sucesso.');
    }
}

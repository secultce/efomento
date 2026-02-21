<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GroupController extends Controller
{
    private array $modules = [
        'vinc_orc'     => 'Vinculação Orçamentária',
        'termo_jur'    => 'Termo Jurídico',
        'extrato_jur'  => 'Extrato do Termo Jurídico',
        'ci'           => 'CI',
        'despacho'     => 'Despacho',
        'parecer_jur'  => 'Parecer Jurídico',
        'parecer_orc'  => 'Parecer Orçamentário',
        'abertura'     => 'Abertura',
        'analise_jur'  => 'Análise Jurídica',
        'formalizacao' => 'Formalização',
        'orcamento'    => 'Orçamento e Parcelas',
        'pagamento'    => 'Pagamento Financeiro',
        'usuarios'     => 'Gerenciamento de Usuários',
        'grupos'       => 'Gerenciamento de Grupos',
    ];

    private array $roleLabels = [
        'fomento'             => 'Fomento',
        'coord_fomento'       => 'Coord. Fomento',
        'financeiro'          => 'Financeiro',
        'coord_financeiro'    => 'Coord. Financeiro',
        'juridico'            => 'Jurídico',
        'coord_juridico'      => 'Coord. Jurídico',
        'orcamentario'        => 'Orçamentário',
        'coord_orcamentario'  => 'Coord. Orçamentário',
        'monitoramento'       => 'Monitoramento',
        'coord_monitoramento' => 'Coord. Monitoramento',
        'acompanhamento'      => 'Acompanhamento',
        'super_admin'         => 'Super Admin',
    ];

    private array $actions = [
        ['key' => 'create',     'label' => 'Criar'],
        ['key' => 'view_own',   'label' => 'Visualizar o próprio'],
        ['key' => 'view_any',   'label' => 'Visualizar outros'],
        ['key' => 'edit_own',   'label' => 'Editar o próprio'],
        ['key' => 'edit_any',   'label' => 'Editar outros'],
        ['key' => 'delete_own', 'label' => 'Excluir o próprio'],
        ['key' => 'delete_any', 'label' => 'Excluir outros'],
    ];

    public function index()
    {
        $allPermissions = Permission::pluck('name')->toArray();

        $roles = Role::with('permissions')
            ->get()
            ->map(fn ($role) => [
                'name'        => $role->name,
                'label'       => $this->roleLabels[$role->name] ?? $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ]);

        $modules = collect($this->modules)
            ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
            ->values();

        return Inertia::render('Groups/Index', [
            'modules'        => $modules,
            'actions'        => $this->actions,
            'allPermissions' => $allPermissions,
            'roles'          => $roles,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'roles'                   => 'required|array',
            'roles.*.name'            => 'required|string|exists:roles,name',
            'roles.*.permissions'     => 'present|array',
            'roles.*.permissions.*'   => 'string|exists:permissions,name',
        ]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($validated['roles'] as $roleData) {
            $role = Role::findByName($roleData['name']);
            $role->syncPermissions($roleData['permissions']);
        }

        return back()->with('success', 'Permissões atualizadas com sucesso.');
    }
}

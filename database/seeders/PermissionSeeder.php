<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpa o cache do Spatie antes de criar
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // -------------------------------------------------------
        // PERMISSIONS
        // -------------------------------------------------------

        $permissions = [

            // Vinculação Orçamentária
            'vinc_orc.view_own',        // Visualizar o próprio
            'vinc_orc.view_any',        // Visualizar qualquer um
            'vinc_orc.create',          // Criar
            'vinc_orc.edit_own',        // Editar o próprio
            'vinc_orc.edit_any',        // Editar qualquer um
            'vinc_orc.delete_own',      // Excluir o próprio
            'vinc_orc.delete_any',      // Excluir qualquer um

            // Termo Jurídico
            'termo_jur.view_own',
            'termo_jur.view_any',
            'termo_jur.create',
            'termo_jur.edit_own',
            'termo_jur.edit_any',
            'termo_jur.delete_own',
            'termo_jur.delete_any',

            // Extrato do Termo Jurídico
            'extrato_jur.view_own',
            'extrato_jur.view_any',
            'extrato_jur.create',
            'extrato_jur.edit_own',
            'extrato_jur.edit_any',
            'extrato_jur.delete_own',
            'extrato_jur.delete_any',

            // CI (Criar da CI)
            'ci.view_own',
            'ci.view_any',
            'ci.create',
            'ci.edit_own',
            'ci.edit_any',
            'ci.delete_own',
            'ci.delete_any',

            // Despacho
            'despacho.view_own',
            'despacho.view_any',
            'despacho.create',
            'despacho.edit_own',
            'despacho.edit_any',
            'despacho.delete_own',
            'despacho.delete_any',

            // Parecer Jurídico
            'parecer_jur.view_own',
            'parecer_jur.view_any',
            'parecer_jur.create',
            'parecer_jur.edit_own',
            'parecer_jur.edit_any',
            'parecer_jur.delete_own',
            'parecer_jur.delete_any',

            // Parecer Orçamentário
            'parecer_orc.view_own',
            'parecer_orc.view_any',
            'parecer_orc.create',
            'parecer_orc.edit_own',
            'parecer_orc.edit_any',
            'parecer_orc.delete_own',
            'parecer_orc.delete_any',

            // Abertura
            'abertura.view_own',
            'abertura.view_any',
            'abertura.create',
            'abertura.edit_own',
            'abertura.edit_any',
            'abertura.delete_own',
            'abertura.delete_any',

            // Análise Jurídica
            'analise_jur.view_own',
            'analise_jur.view_any',
            'analise_jur.create',
            'analise_jur.edit_own',
            'analise_jur.edit_any',
            'analise_jur.delete_own',
            'analise_jur.delete_any',

            // Formalização
            'formalizacao.view_own',
            'formalizacao.view_any',
            'formalizacao.create',
            'formalizacao.edit_own',
            'formalizacao.edit_any',
            'formalizacao.delete_own',
            'formalizacao.delete_any',

            // Orçamento e Parcelas
            'orcamento.view_own',
            'orcamento.view_any',
            'orcamento.create',
            'orcamento.edit_own',
            'orcamento.edit_any',
            'orcamento.delete_own',
            'orcamento.delete_any',

            // Pagamento Financeiro
            'pagamento.view_own',
            'pagamento.view_any',
            'pagamento.create',
            'pagamento.edit_own',
            'pagamento.edit_any',
            'pagamento.delete_own',
            'pagamento.delete_any',

            // Gerenciamento de Usuários
            'usuarios.create',
            'usuarios.edit_own',
            'usuarios.delete_own',
            'usuarios.edit_any',
            'usuarios.delete_any',

            // Gerenciamento de Grupos de Usuários
            'grupos.create',
            'grupos.edit_own',
            'grupos.delete_own',
            'grupos.edit_any',
            'grupos.delete_any',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // -------------------------------------------------------
        // ROLES e suas PERMISSIONS conforme a matriz
        // -------------------------------------------------------

        // Fomento
        $fomento = Role::firstOrCreate(['name' => 'fomento']);
        $fomento->givePermissionTo([
            'abertura.view_own',
            'abertura.create',
            'abertura.edit_own',
            'abertura.delete_own',
        ]);

        // Coord. Fomento
        $coordFomento = Role::firstOrCreate(['name' => 'coord_fomento']);
        $coordFomento->givePermissionTo([
            'abertura.view_own',
            'abertura.view_any',
            'abertura.create',
            'abertura.edit_own',
            'abertura.edit_any',
            'abertura.delete_own',
            'abertura.delete_any',
        ]);

        // Financeiro
        $financeiro = Role::firstOrCreate(['name' => 'financeiro']);
        $financeiro->givePermissionTo([
            'pagamento.view_own',
            'pagamento.create',
            'pagamento.edit_own',
            'pagamento.delete_own',
        ]);

        // Coord. Financeiro
        $coordFinanceiro = Role::firstOrCreate(['name' => 'coord_financeiro']);
        $coordFinanceiro->givePermissionTo([
            'pagamento.view_own',
            'pagamento.view_any',
            'pagamento.create',
            'pagamento.edit_own',
            'pagamento.edit_any',
            'pagamento.delete_own',
            'pagamento.delete_any',
        ]);

        // Jurídico
        $juridico = Role::firstOrCreate(['name' => 'juridico']);
        $juridico->givePermissionTo([
            'termo_jur.view_own',
            'termo_jur.create',
            'termo_jur.edit_own',
            'termo_jur.delete_own',
            'extrato_jur.view_own',
            'extrato_jur.create',
            'extrato_jur.edit_own',
            'extrato_jur.delete_own',
            'ci.view_own',
            'ci.create',
            'ci.edit_own',
            'ci.delete_own',
            'despacho.view_own',
            'despacho.create',
            'despacho.edit_own',
            'despacho.delete_own',
            'parecer_jur.view_own',
            'parecer_jur.create',
            'parecer_jur.edit_own',
            'parecer_jur.delete_own',
            'analise_jur.view_own',
            'analise_jur.create',
            'analise_jur.edit_own',
            'analise_jur.delete_own',
            'formalizacao.view_own',
            'formalizacao.create',
            'formalizacao.edit_own',
            'formalizacao.delete_own',
        ]);

        // Coord. Jurídico
        $coordJuridico = Role::firstOrCreate(['name' => 'coord_juridico']);
        $coordJuridico->givePermissionTo([
            'termo_jur.view_own',
            'termo_jur.view_any',
            'termo_jur.create',
            'termo_jur.edit_own',
            'termo_jur.edit_any',
            'termo_jur.delete_own',
            'termo_jur.delete_any',
            'extrato_jur.view_own',
            'extrato_jur.view_any',
            'extrato_jur.create',
            'extrato_jur.edit_own',
            'extrato_jur.edit_any',
            'extrato_jur.delete_own',
            'extrato_jur.delete_any',
            'ci.view_own',
            'ci.view_any',
            'ci.create',
            'ci.edit_own',
            'ci.edit_any',
            'ci.delete_own',
            'ci.delete_any',
            'despacho.view_own',
            'despacho.view_any',
            'despacho.create',
            'despacho.edit_own',
            'despacho.edit_any',
            'despacho.delete_own',
            'despacho.delete_any',
            'parecer_jur.view_own',
            'parecer_jur.view_any',
            'parecer_jur.create',
            'parecer_jur.edit_own',
            'parecer_jur.edit_any',
            'parecer_jur.delete_own',
            'parecer_jur.delete_any',
            'analise_jur.view_own',
            'analise_jur.view_any',
            'analise_jur.create',
            'analise_jur.edit_own',
            'analise_jur.edit_any',
            'analise_jur.delete_own',
            'analise_jur.delete_any',
            'formalizacao.view_own',
            'formalizacao.view_any',
            'formalizacao.create',
            'formalizacao.edit_own',
            'formalizacao.edit_any',
            'formalizacao.delete_own',
            'formalizacao.delete_any',
        ]);

        // Orçamentário
        $orcamentario = Role::firstOrCreate(['name' => 'orcamentario']);
        $orcamentario->givePermissionTo([
            'vinc_orc.view_own',
            'vinc_orc.create',
            'vinc_orc.edit_own',
            'vinc_orc.delete_own',
            'despacho.view_own',
            'despacho.create',
            'despacho.edit_own',
            'despacho.delete_own',
            'parecer_orc.view_own',
            'parecer_orc.create',
            'parecer_orc.edit_own',
            'parecer_orc.delete_own',
            'orcamento.view_own',
            'orcamento.create',
            'orcamento.edit_own',
            'orcamento.delete_own',
        ]);

        // Coord. Orçamentário
        $coordOrcamentario = Role::firstOrCreate(['name' => 'coord_orcamentario']);
        $coordOrcamentario->givePermissionTo([
            'vinc_orc.view_own',
            'vinc_orc.view_any',
            'vinc_orc.create',
            'vinc_orc.edit_own',
            'vinc_orc.edit_any',
            'vinc_orc.delete_own',
            'vinc_orc.delete_any',
            'despacho.view_own',
            'despacho.view_any',
            'despacho.create',
            'despacho.edit_own',
            'despacho.edit_any',
            'despacho.delete_own',
            'despacho.delete_any',
            'parecer_orc.view_own',
            'parecer_orc.view_any',
            'parecer_orc.create',
            'parecer_orc.edit_own',
            'parecer_orc.edit_any',
            'parecer_orc.delete_own',
            'parecer_orc.delete_any',
            'orcamento.view_own',
            'orcamento.view_any',
            'orcamento.create',
            'orcamento.edit_own',
            'orcamento.edit_any',
            'orcamento.delete_own',
            'orcamento.delete_any',
        ]);

        // Monitoramento
        $monitoramento = Role::firstOrCreate(['name' => 'monitoramento']);
        $monitoramento->givePermissionTo([
            // Apenas view_any conforme matriz (sem próprio separado)
        ]);

        // Coord. Monitoramento
        $coordMonitoramento = Role::firstOrCreate(['name' => 'coord_monitoramento']);
        $coordMonitoramento->givePermissionTo([
            'vinc_orc.view_any',
            'termo_jur.view_any',
            'extrato_jur.view_any',
            'ci.view_any',
            'despacho.view_any',
            'parecer_jur.view_any',
            'parecer_orc.view_any',
            'abertura.view_any',
            'analise_jur.view_any',
            'formalizacao.view_any',
            'orcamento.view_any',
            'pagamento.view_any',
        ]);

        // Acompanhamento
        $acompanhamento = Role::firstOrCreate(['name' => 'acompanhamento']);
        $acompanhamento->givePermissionTo([
            'vinc_orc.view_any',
            'termo_jur.view_any',
            'extrato_jur.view_any',
            'ci.view_any',
            'despacho.view_any',
            'parecer_jur.view_any',
            'parecer_orc.view_any',
            'abertura.view_any',
            'analise_jur.view_any',
            'formalizacao.view_any',
            'orcamento.view_any',
            'pagamento.view_any',
        ]);

        // Administração (acesso total)
        $administracao = Role::firstOrCreate(['name' => 'administracao']);
        $administracao->givePermissionTo(Permission::all());
    }
}

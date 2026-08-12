export const DOCUMENT_TYPES = {
    CI: 'ci',
    TC: 'tc',
    PJ: 'pj',
    ET: 'et',
    DO: 'do',
    DP: 'dp',
};

const placeHoldersDocsSchema = [
    { label: 'Nome Edital', value: 'notice_name' },
    { label: 'Nup Mãe', value: 'nup_mother' },
    { label: 'Finalidade', value: 'finality' },
    { label: 'Nup Projeto', value: 'project_nup' },
    { label: 'Nome do Projeto', value: 'project_name' },
    { label: 'Nome do Agente', value: 'agent_name' },
    { label: 'CPF do Agente', value: 'agent_cpf' },
    { label: 'End. do Agente', value: 'agent_address' },
    { label: 'E-mail do Agente', value: 'agent_email' },
    { label: 'Fone do Agente', value: 'agent_phone' },
    { label: 'Matricula do Fiscal', value: 'fiscal_matricula' },
    { label: 'Nome do Fiscal', value: 'fiscal_name' },
    { label: 'Código da dotação', value: 'allocation_code' },
    { label: 'Número completo da dotação', value: 'allocation_number' },
    { label: 'Banco do Agente', value: 'bank' },
    { label: 'Tipo de Conta do Agente', value: 'account_type' },
    { label: 'Agência do Agente', value: 'branch' },
    { label: 'Conta do Agente', value: 'account' },
    { label: 'N. Dotação Orçamentária', value: 'budget_allocation_nup' },
    { label: 'N. Cad. Credor', value: 'creditor_registration_nup' },
];

const installmentPlaceHoldersDocsSchema = [
    ...placeHoldersDocsSchema,
    { label: 'Número da parcela para este agente', value: 'notice_installment_number' },
];

export const documentConfigs = {
    [DOCUMENT_TYPES.CI]: {
        name: 'Comunicação Interna',
        titleCreate: 'Criar comunicação interna (CI)',
        titleEdit: 'Editar comunicação interna (CI)',
        save: 'ci',
        placeholders: placeHoldersDocsSchema,
    },

    [DOCUMENT_TYPES.TC]: {
        name: 'Termo de Execução Cultural',
        titleCreate: 'Criar termo de execução cultural (TC)',
        titleEdit: 'Editar termo de execução cultural (TC)',
        save: 'tc',
        placeholders: placeHoldersDocsSchema,
    },

    [DOCUMENT_TYPES.ET]: {
        name: 'Extrato do Termo',
        titleCreate: 'Criar extrato do termo (ET)',
        titleEdit: 'Editar extrato do termo (ET)',
        save: 'et',
        placeholders: placeHoldersDocsSchema,
    },

    [DOCUMENT_TYPES.PJ]: {
        name: 'Parecer Jurídico',
        titleCreate: 'Criar parecer jurídico (PJ)',
        titleEdit: 'Editar parecer jurídico (PJ)',
        save: 'pj',
        placeholders: placeHoldersDocsSchema,
    },

    [DOCUMENT_TYPES.DO]: {
        name: 'Despacho Orçamentário',
        titleCreate: 'Criar Despacho Orçamentário (DO)',
        titleEdit: 'Editar Despacho Orçamentário (DO)',
        save: 'do',
        placeholders: installmentPlaceHoldersDocsSchema,
    },

    [DOCUMENT_TYPES.DP]: {
        name: 'Despacho de Pagamento',
        titleCreate: 'Criar Despacho de Pagamento (DP)',
        titleEdit: 'Editar Despacho de Pagamento (DP)',
        save: 'dp',
        placeholders: installmentPlaceHoldersDocsSchema,
    },
};

export const DOCUMENT_TYPES = {
    CI: 'ci',
    TC: 'tc',
    PJ: 'pj',
    ET: 'et',
    PO: 'po',
    D: 'd',
};

const placeHoldersDocsSchema = [
    { label: 'Num. Edital', value: 'notice_name' },
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
    },

    [DOCUMENT_TYPES.ET]: {
        name: 'Extrato do Termo',
        titleCreate: 'Criar extrato do termo (ET)',
        titleEdit: 'Editar extrato do termo (ET)',
        save: 'et',
    },

    [DOCUMENT_TYPES.PJ]: {
        name: 'Parecer Jurídico',
        titleCreate: 'Criar parecer jurídico (PJ)',
        titleEdit: 'Editar parecer jurídico (PJ)',
        save: 'pj',
    },

    [DOCUMENT_TYPES.PO]: {
        name: 'Parecer Orçamentário',
        titleCreate: 'Criar Parecer Orçamentário (PO)',
        titleEdit: 'Editar Parecer Orçamentário',
        save: 'pj',
    },

    [DOCUMENT_TYPES.D]: {
        name: 'Despacho',
        titleCreate: 'Criar Despacho (D)',
        titleEdit: 'Editar Despacho (D)',
        save: 'd',
    },
};

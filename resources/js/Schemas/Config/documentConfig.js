export const DOCUMENT_TYPES = {
    CI: 'ci',
    TC: 'tc',
    PJ: 'pj',
    ET: 'et',
    PO: 'po',
};

export const documentConfigs = {
    [DOCUMENT_TYPES.CI]: {
        name: 'Comunicação Interna',
        titleCreate: 'Criar comunicação interna (CI)',
        titleEdit: 'Editar comunicação interna (CI)',
        save: 'ci',
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
        name: 'Criar documento de despacho',
        titleCreate: 'Criar Despacho (PO)',
        titleEdit: 'Editar Despacho (PO)',
        save: 'po',
    },
};

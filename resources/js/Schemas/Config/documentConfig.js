export const DOCUMENT_TYPES = {
    CI: 'ci',
    TC: 'tc',
    PJ: 'pj',
};

export const documentConfigs = {
    [DOCUMENT_TYPES.CI]: {
        titleCreate: 'Criar comunicação interna (CI)',
        titleEdit: 'Editar comunicação interna (CI)',

        hasImages: true,

        placeholders: [{ label: 'Nome do edital', value: '[notice_name]' }],

        save: 'ci',
    },

    [DOCUMENT_TYPES.TC]: {
        titleCreate: 'Criar termo de execução cultural (TC)',
        titleEdit: 'Editar termo de execução cultural (TC)',

        hasImages: true,

        placeholders: [{ label: 'Nome do edital', value: '[notice_name]' }],

        save: 'tc',
    },
};

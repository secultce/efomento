export const viewSections = [
    {
        title: 'Dados do agente e do projeto',
        fields: [
            { label: 'Nome social / Nome fantasia', key: 'agent.name' },
            { label: 'Personalidade jurídica', key: 'agent.legal_type' },
            { label: 'CPF / CNPJ do agente cultural', key: 'agent.cpf' },
            { label: 'Área / linguagem / ciclo', key: 'notice.name' },
            { label: 'Categoria de inscrição', key: 'category.name' },
            { label: 'Título do projeto', key: 'title_project' },
            { label: 'N° da inscrição', key: 'registration_id' },
            { label: 'Endereço completo', key: 'agent.latest_snapshot.address' },
            { label: 'Macrorregião', key: 'agent.latest_snapshot.macroregion' },
            { label: 'CEP', key: 'agent.latest_snapshot.postal_code' },
            { label: 'Município', key: 'agent.latest_snapshot.city' },
        ],
    },
    {
        title: 'Dados Bancários',
        fields: [
            { label: 'Banco', key: 'agente.openings.bank' },
            { label: 'Conta', key: 'agent.openings.account' },
            { label: 'Agência', key: 'agent.openings.branch' },
            { label: 'Tipo de Conta', key: 'agent.openings.account_type' },
        ],
    },
];

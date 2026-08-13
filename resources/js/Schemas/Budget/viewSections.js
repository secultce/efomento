const getBudgetAllocation = (project) => {
    if (!project) return null;

    const noticeAllocations = project.notice?.budget_allocations ?? [];

    let budgetsArray = Array.isArray(project.budgets) ? project.budgets : Object.values(project.budgets || {});

    if (budgetsArray.length === 0) {
        return noticeAllocations[0] ?? null;
    }

    const currentBudget = budgetsArray[budgetsArray.length - 1];

    const installmentsArray = Array.isArray(currentBudget.installments)
        ? currentBudget.installments
        : Object.values(currentBudget.installments || {});

    const installment = installmentsArray.find(
        (i) => Number(i.installment_number) === Number(project.current_installment_cycle)
    );

    if (installment) {
        if (installment.budget_allocation) return installment.budget_allocation;

        if (installment.budget_allocation_id) {
            return noticeAllocations.find((a) => a.id === installment.budget_allocation_id) ?? null;
        }
    }

    return noticeAllocations[0] ?? null;
};

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
            {
                label: 'Endereço completo',
                compute: (project) => {
                    const s = project?.agent?.latest_snapshot;
                    if (!s) return null;
                    return [s.address, s.number, s.neighborhood, s.city, s.state, s.postal_code]
                        .filter(Boolean)
                        .join(', ');
                },
            },
        ],
    },
    {
        title: 'Dados Bancários',
        fields: [
            { label: 'Banco', key: 'opening.bank' },
            { label: 'Conta', key: 'opening.account' },
            { label: 'Agência', key: 'opening.branch' },
            { label: 'Tipo de Conta', key: 'opening.account_type' },
        ],
    },
    {
        title: 'Dotação orçamentária',
        fields: [
            { label: 'Código da Dotação', compute: (project) => getBudgetAllocation(project)?.allocation_code },
            { label: 'Dotação orçamentária', compute: (project) => getBudgetAllocation(project)?.allocation_number },
            { label: 'Cidade do agente', compute: (project) => project?.agent?.latest_snapshot?.city },
            { label: 'Macrorregião', compute: (project) => project?.agent?.latest_snapshot?.macroregion },
        ],
    },
    {
        title: 'Informações da LOA (Lei Orçamentária Anual)',
        fields: [
            { label: 'Unidade orçamentária', compute: (project) => getBudgetAllocation(project)?.management_unit },
            { label: 'Programa de trabalho', compute: (project) => getBudgetAllocation(project)?.work_program },
            { label: 'Objetivo', compute: (project) => getBudgetAllocation(project)?.objective },
            { label: 'Entrega', compute: (project) => getBudgetAllocation(project)?.deliverable },
            { label: 'Função', compute: (project) => getBudgetAllocation(project)?.budget_function },
            { label: 'Subfunção', compute: (project) => getBudgetAllocation(project)?.budget_subfunction },
            { label: 'Projeto/atividade', compute: (project) => getBudgetAllocation(project)?.project_activity },
            { label: 'Elemento de despesa', compute: (project) => getBudgetAllocation(project)?.expense_element },
            { label: 'Fonte de recursos', compute: (project) => getBudgetAllocation(project)?.funding_source },
            { label: 'MAPP', compute: (project) => getBudgetAllocation(project)?.mapp },
            {
                label: 'Projeto finalistico - INTERIOR',
                compute: (project) => getBudgetAllocation(project)?.finalistic_project,
            },
        ],
    },
];

const fieldLabels = {
    nup: 'NUP',
    instrument_type: 'Tipo de Instrumento',
    process_manager: 'Gestor',
    budget_allocation_request_date: 'Data da Solicitação da Dotação Orçamentária',
    total_notice_amount: 'Valor Total do Edital',
    process_manager_email: 'Email do Gestor',
    creditor_registration_nup: 'N° Processo Cadastro do Credor',
    installments: 'Parcelas',
    budget_allocation_nup: 'N° Processo Dotação Orçamentária',
    creditor_registration_request_date: 'Data da Solicitação do Cadastro do Credor',
};

function translateEvent(event) {
    switch (event) {
        case 'created': return 'criou';
        case 'updated': return 'alterou';
        case 'deleted': return 'removeu';
        default: return event;
    }
}

function formatValue(field, value) {
    if (value === null || value === undefined) return '—';

    // dinheiro
    if (field.includes('amount')) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }

    // datas
    if (field.includes('date')) {
        return new Date(value).toLocaleDateString('pt-BR');
    }

    return value;
}

export function formatAudits(audits) {
    return audits.map(audit => {
        const changes = [];

        for (const field in audit.new_values || {}) {
            const oldVal = audit.old_values?.[field];
            const newVal = audit.new_values?.[field];

            if (oldVal === newVal) continue;

            changes.push({
                field,
                label: fieldLabels[field] || field,
                from: formatValue(field, oldVal),
                to: formatValue(field, newVal),
            });
        }

        return {
            id: audit.id,
            user: audit.user,
            action: translateEvent(audit.event),
            date: audit.date,
            changes
        };
    });
}

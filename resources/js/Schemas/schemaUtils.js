import { useMask } from '@/Composables/useMask';

const { maskProcessNumber } = useMask();

export function getInstallmentJustification(project) {
    const agentName = project?.agent?.name;
    const noticeName = project?.notice?.name;
    const categoryName = project?.category?.name;
    const openingNup = maskProcessNumber(project?.opening_nup);

    if (!agentName || !noticeName || !categoryName || !openingNup) return null;

    return `Repasse de recurso para o/a proponente ${agentName ?? ''}, selecionado no ${noticeName ?? ''}, na categoria - ${categoryName ?? ''}, conforme ${openingNup ?? ''}`;
}

export function getCurrentInstallment(project) {
    const installments = project.budgets?.installments ?? [];
    return installments.find(
        (installment) => Number(installment.installment_number) === Number(project.current_installment_cycle ?? 1)
    );
}

export function parseRegistrationFieldValue(raw) {
    if (raw === null || raw === undefined) return '—';

    const value = typeof raw === 'string' ? tryParseJson(raw) : raw;

    return formatValue(value);
}

function tryParseJson(text) {
    try {
        return JSON.parse(text);
    } catch {
        return text;
    }
}

function formatValue(value) {
    if (value === null || value === undefined) return '—';
    if (typeof value === 'string') return value;
    if (Array.isArray(value)) return value.map(formatValue).join(', ');
    if (typeof value === 'object') return JSON.stringify(value);

    return String(value);
}

export function getLegalType(project) {
    const cpfCnpj = project?.agent?.latest_snapshot?.cpf_cnpj;

    if (!cpfCnpj) return null;

    const digits = String(cpfCnpj).replace(/\D/g, '');

    if (digits.length === 11) return 'PESSOA FÍSICA';
    if (digits.length === 14) return 'PESSOA JURÍDICA';

    return null;
}

const normalizeMacroregion = (value) =>
    String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/^\s*\d+\s*[-–—]?\s*/, '')
        .replace(/[^a-zA-Z0-9]+/g, '')
        .toLowerCase();

const asArray = (value) => {
    if (Array.isArray(value)) return value;
    return value ? [value] : [];
};

export const getBudgetAllocation = (project) => {
    if (!project) return null;

    const noticeAllocations = asArray(project.notice?.budget_allocations);
    const noticeFallback = noticeAllocations[0] ?? null;
    const currentBudget = asArray(project.budgets).at(-1);
    const installment = asArray(currentBudget?.installments).find(
        (item) => Number(item.installment_number) === Number(project.current_installment_cycle)
    );

    if (installment?.budget_allocation) return installment.budget_allocation;

    if (installment?.budget_allocation_id) {
        const linkedAllocation = noticeAllocations.find(
            (allocation) => Number(allocation.id) === Number(installment.budget_allocation_id)
        );

        if (linkedAllocation) return linkedAllocation;
    }

    const macroregion = normalizeMacroregion(project.agent?.latest_snapshot?.macroregion);

    if (macroregion) {
        const match = noticeAllocations.find(
            (allocation) => normalizeMacroregion(allocation.planning_macroregion) === macroregion
        );

        if (match) return match;
    }

    return noticeFallback;
};

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

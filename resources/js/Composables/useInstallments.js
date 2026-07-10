export function useInstallmentStatus() {
    function hasValue(value) {
        return value !== null && value !== undefined && value !== '';
    }

    function toNumber(value) {
        const number = Number(value ?? 0);

        return Number.isFinite(number) ? number : 0;
    }

    function getInstallmentStatus(installment) {
        const committed = toNumber(installment?.committed_amount);

        const liquidated = toNumber(installment?.settlement_amount);

        const paid = toNumber(installment?.payment_amount);

        const installmentValue = toNumber(installment?.amount);

        const hasCommitmentData = committed > 0;

        const hasSettlementData =
            hasValue(installment?.settlement_date) || hasValue(installment?.settlement_number) || liquidated > 0;

        const hasPaymentData =
            hasValue(installment?.payment_date) || hasValue(installment?.payment_order_number) || paid > 0;

        const isIrregular =
            (hasPaymentData && !hasSettlementData) ||
            (hasSettlementData && !hasCommitmentData) ||
            (hasPaymentData && hasCommitmentData && paid !== committed) ||
            (liquidated > 0 && committed > 0 && liquidated > committed) ||
            (installmentValue > 0 && committed > 0 && installmentValue > committed);

        if (isIrregular) {
            return {
                key: 'irregular',
                label: 'Irregular',
                icon: 'mdi-alert-circle',
                class: 'text-red-700 border-red-200',
            };
        }

        if (hasPaymentData) {
            return {
                key: 'paid',
                label: 'Pago',
                icon: 'mdi-check-circle',
                class: 'text-green-700 border-green-200',
            };
        }

        if (hasSettlementData) {
            return {
                key: 'settled',
                label: 'Liquidado',
                icon: 'mdi-file-check-outline',
                class: 'text-blue-700 border-blue-200',
            };
        }

        if (hasCommitmentData) {
            return {
                key: 'committed',
                label: 'Empenhado',
                icon: 'mdi-cash-check',
                class: 'text-purple-700 border-purple-200',
            };
        }

        return {
            key: 'pending',
            label: 'Pendente',
            icon: 'mdi-clock-outline',
            class: 'text-yellow-700 border-yellow-200',
        };
    }

    function isInstallmentPaid(installment) {
        const status = getInstallmentStatus(installment);

        return status.key === 'paid';
    }

    function isInstallmentIrregular(installment) {
        const status = getInstallmentStatus(installment);

        return status.key === 'irregular';
    }

    function countPaidInstallments(installments = []) {
        if (!Array.isArray(installments)) {
            return 0;
        }

        return installments.filter(isInstallmentPaid).length;
    }

    return {
        hasValue,
        toNumber,
        getInstallmentStatus,
        isInstallmentPaid,
        isInstallmentIrregular,
        countPaidInstallments,
    };
}

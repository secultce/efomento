import { computed } from 'vue';
import { useAuth } from './useAuth';

export function usePermissions() {
    const { hasRole } = useAuth();

    return {
        canManageNotices: computed(() => hasRole(['fomentation', 'coord_fomentation', 'super_admin'])),
        canManageLegalAnalysis: computed(() =>
            hasRole(['legal_analysis', 'coord_legal', 'coord_financial', 'super_admin'])
        ),
        canManageFormalization: computed(() => hasRole(['legal_analysis', 'coord_legal', 'super_admin'])),
        canManageBudget: computed(() => hasRole(['budgetary', 'coord_budgetary', 'super_admin'])),
        canManagePayment: computed(() => hasRole(['financial', 'coord_financial', 'super_admin'])),
        canManageMonitoring: computed(() => hasRole(['monitoring', 'coord_monitoring', 'super_admin'])),
        isSuperAdmin: computed(() => hasRole('super_admin')),
    };
}

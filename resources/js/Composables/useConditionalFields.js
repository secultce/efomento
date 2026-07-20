import { computed, watch } from 'vue';

function evaluateCondition(condition, formData) {
    if (!condition) return true;

    if (condition.all) {
        return condition.all.every((child) => evaluateCondition(child, formData));
    }

    if (condition.any) {
        return condition.any.some((child) => evaluateCondition(child, formData));
    }

    const value = formData[condition.field];

    if ('equals' in condition) {
        return value === condition.equals;
    }

    if ('in' in condition) {
        return condition.in.includes(value);
    }

    return true;
}

export function useConditionalFields(fields, formData) {
    const isVisible = (field) => evaluateCondition(field.visibleWhen, formData);

    const visibleFields = computed(() => fields.filter((field) => isVisible(field)));

    const requiredFields = computed(() => visibleFields.value.filter((field) => field.required));

    watch(
        visibleFields,
        (visible) => {
            const visibleKeys = new Set(visible.map((field) => field.key));

            fields.forEach((field) => {
                if (!visibleKeys.has(field.key) && formData[field.key] !== undefined && formData[field.key] !== null) {
                    formData[field.key] = null;
                }
            });
        },
        { immediate: true }
    );

    return {
        visibleFields,
        requiredFields,
        isVisible: (key) => visibleFields.value.some((field) => field.key === key),
    };
}

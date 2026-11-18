import { onMounted, onUnmounted, toValue } from 'vue';

export function useSaveShortcut(onSave, enabled) {
    const handleKeydown = (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            if (toValue(enabled) !== false) {
                onSave();
            }
        }
    };

    onMounted(() => window.addEventListener('keydown', handleKeydown));
    onUnmounted(() => window.removeEventListener('keydown', handleKeydown));
}

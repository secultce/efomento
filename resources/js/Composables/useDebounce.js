import { onUnmounted } from 'vue';

export function useDebounce(callback, delay = 300) {
    let timeoutId = null;

    const cancel = () => {
        clearTimeout(timeoutId);
        timeoutId = null;
    };

    const debounced = (...args) => {
        cancel();

        timeoutId = setTimeout(() => {
            timeoutId = null;
            callback(...args);
        }, delay);
    };

    onUnmounted(cancel);

    return {
        debounced,
        cancel,
    };
}

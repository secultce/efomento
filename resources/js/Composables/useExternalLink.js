export function sanitizeExternalUrl(url) {
    if (typeof url !== 'string') return null;
    try {
        const { protocol } = new URL(url);
        return protocol === 'http:' || protocol === 'https:' ? url : null;
    } catch {
        return null;
    }
}

export function useExternalLink() {
    const openExternal = (url, options = {}) => {
        if (!url) return;

        const { target = '_blank', features = 'noopener,noreferrer', fallback = true } = options;

        if (typeof window === 'undefined') return;

        const newWindow = window.open(url, target, features);

        if (!newWindow && fallback) {
            const link = document.createElement('a');
            link.href = url;
            link.target = target;
            link.rel = 'noopener noreferrer';
            link.click();
        }
    };

    return {
        openExternal,
    };
}

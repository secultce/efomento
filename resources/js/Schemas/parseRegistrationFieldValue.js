export function parseRegistrationFieldValue(raw) {
    if (raw === null || raw === undefined) return '—';
    if (typeof raw !== 'string') return String(raw);

    try {
        const parsed = JSON.parse(raw);
        if (typeof parsed === 'string') return parsed;
        if (Array.isArray(parsed)) return parsed.join(', ');
        return JSON.stringify(parsed);
    } catch {
        return raw;
    }
}

import { sanitizeExternalUrl } from '@/Composables/useExternalLink';

const EMPTY = '—';

export const REGISTRATION_STATUS_LABELS = {
    0: 'Rascunho',
    1: 'Enviada',
    2: 'Inválida',
    3: 'Não selecionada',
    8: 'Suplente',
    10: 'Selecionada',
};

const isBlank = (value) => value === null || value === undefined || (typeof value === 'string' && value.trim() === '');

function toText(value) {
    if (isBlank(value)) return EMPTY;
    if (typeof value === 'boolean') return value ? 'Sim' : 'Não';
    if (typeof value === 'string' || typeof value === 'number') return String(value);
    if (Array.isArray(value)) return value.map(toText).join(', ');
    if (typeof value === 'object') {
        const label = value.name ?? value.title ?? value.label ?? value.value;
        return isBlank(label) ? EMPTY : toText(label);
    }

    return EMPTY;
}

function formatDate(value) {
    if (isBlank(value)) return EMPTY;
    const text = typeof value === 'object' ? (value.date ?? '') : String(value);
    const match = text.match(/^(\d{4})-(\d{2})-(\d{2})/);

    return match ? `${match[3]}/${match[2]}/${match[1]}` : toText(text);
}

export function useRegistrationFields(getMapasDomain = () => '') {
    // URLs relativas do Mapas (ex.: file/privateFile/12) recebem o domínio; só http/https passa.
    function resolveUrl(raw) {
        if (isBlank(raw) || typeof raw !== 'string') return null;

        if (/^https?:\/\//i.test(raw)) return sanitizeExternalUrl(raw);
        if (/^[a-z][a-z0-9+.-]*:/i.test(raw) || raw.startsWith('//')) return null;

        const domain = String(getMapasDomain() ?? '').replace(/\/+$/, '');
        if (!domain) return null;

        const base = /^https?:\/\//i.test(domain) ? domain : `https://${domain}`;

        return sanitizeExternalUrl(`${base}/${raw.replace(/^\/+/, '')}`);
    }

    function linkItem(entry) {
        if (typeof entry === 'string') {
            const href = resolveUrl(entry);
            return { text: entry, href };
        }

        const raw = entry?.url ?? entry?.link ?? entry?.value;
        const href = resolveUrl(raw);
        const text = entry?.title ?? entry?.name ?? (typeof raw === 'string' ? raw : EMPTY);

        return { text: toText(text), href };
    }

    // Devolve sempre uma lista de { text, href? }; o template só interpola texto (sem HTML bruto).
    function formatField(field) {
        const { type, value } = field ?? {};

        // No checkbox o `value` é o texto da declaração; quem diz se foi marcado é `checked`.
        if (type === 'checkbox') return [{ text: toText(Boolean(field.checked)) }];

        if (isBlank(value) || (Array.isArray(value) && value.length === 0)) return [{ text: EMPTY }];

        const list = Array.isArray(value) ? value : [value];

        switch (type) {
            case 'file':
            case 'links':
                return list.map(linkItem);
            case 'url':
                return [linkItem(value)];
            case 'date':
                return [{ text: formatDate(value) }];
            case 'checkboxes':
            case 'persons':
                if (Array.isArray(value)) return list.map((entry) => ({ text: toText(entry) }));
                if (typeof value === 'object') {
                    return Object.entries(value)
                        .filter(([, checked]) => checked)
                        .map(([label]) => ({ text: label }));
                }
                return [{ text: toText(value) }];
            default:
                return [{ text: toText(value) }];
        }
    }

    return { formatField, formatDate };
}

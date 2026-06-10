import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import 'dayjs/locale/pt-br';

export function useDate() {
    dayjs.extend(relativeTime);
    dayjs.locale('pt-br');

    const formatDate = (value) => {
        if (!value) return '-';

        if (typeof value === 'string' && value.includes('-')) {
            const [year, month, day] = value.slice(0, 10).split('-');
            return `${day}/${month}/${year}`;
        }

        const date = new Date(value);

        return date.toLocaleDateString('pt-BR');
    };

    const normalizeDate = (value) => {
        if (!value) return null;

        if (typeof value === 'string' && value.includes('-')) {
            return value.slice(0, 10);
        }

        const date = new Date(value);

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    };

    const formatRelativeDate = (date) => {
        return dayjs(date).fromNow();
    };

    const resolveKey = (key, obj) => key.split('.').reduce((acc, part) => acc?.[part], obj);

    const addDaysTo = (key, days) => (project) => {
        const ts = resolveKey(key, project);
        if (!ts) return null;
        const date = new Date(ts);
        date.setDate(date.getDate() + days);
        return date;
    };

    const getDate = (key) => (project) => {
        const ts = resolveKey(key, project);
        if (!ts) return null;
        return formatDate(ts);
    };

    return {
        formatDate,
        normalizeDate,
        formatRelativeDate,
        addDaysTo,
        getDate,
    };
}

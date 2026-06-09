export const addDaysTo = (key, days) => (project) => {
    const ts = key.split('.').reduce((obj, k) => obj?.[k], project);
    if (!ts) return null;
    const date = new Date(ts);
    date.setDate(date.getDate() + days);
    return date;
};

export const getDate = (key) => (project) => {
    const ts = key.split('.').reduce((obj, k) => obj?.[k], project);
    if (!ts) return null;
    const date = new Date(ts);
    return date.toLocaleDateString('pt-BR');
};

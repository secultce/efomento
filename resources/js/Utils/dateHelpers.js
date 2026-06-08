export const addDaysTo = (key, days) => (project) => {
    const ts = project?.[key];
    if (!ts) return null;
    const date = new Date(ts);
    date.setDate(date.getDate() + days);
    return date;
};

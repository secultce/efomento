export function useDate() {

    const formatDate = (value) => {
        if (!value) return '-'

        if (typeof value === 'string' && value.includes('-')) {
            const [year, month, day] = value.slice(0, 10).split('-')
            return `${day}/${month}/${year}`
        }

        const date = new Date(value)

        return date.toLocaleDateString('pt-BR')
    }

    const normalizeDate = (value) => {
        if (!value) return null

        if (typeof value === 'string' && value.includes('-')) {
            return value.slice(0, 10)
        }

        const date = new Date(value)

        const year = date.getFullYear()
        const month = String(date.getMonth() + 1).padStart(2, '0')
        const day = String(date.getDate()).padStart(2, '0')

        return `${year}-${month}-${day}`
    }

    return {
        formatDate,
        normalizeDate,
    }
}
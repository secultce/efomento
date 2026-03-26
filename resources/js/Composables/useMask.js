export function useMask() {

    const applyMask = (value, mask) => {
        if (!mask) return value
        if (!value) return ''

        const digits = String(value).replace(/\D/g, '')
        let result = ''
        let digitIndex = 0

        for (let i = 0; i < mask.length; i++) {
            if (mask[i] === '#') {
                if (digits[digitIndex] === undefined) break
                result += digits[digitIndex]
                digitIndex++
            } else {
                result += mask[i]
            }
        }

        return result
    }

    return {
        applyMask,
    }
}
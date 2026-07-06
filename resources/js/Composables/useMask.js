export function useMask() {
    const applyMask = (value, mask) => {
        if (!mask) return value;
        if (!value) return '';

        const digits = String(value).replace(/\D/g, '');
        let result = '';
        let digitIndex = 0;

        for (let i = 0; i < mask.length; i++) {
            if (mask[i] === '#') {
                if (digits[digitIndex] === undefined) break;
                result += digits[digitIndex];
                digitIndex++;
            } else {
                result += mask[i];
            }
        }

        return result;
    };

    const maskProcessNumber = (value) => {
        if (!value) return '';
        const digits = value.toString().replace(/\D/g, '');
        return digits.replace(/^(\d{5})(\d{6})(\d{4})(\d{2}).*/, '$1.$2/$3-$4');
    };

    const maskPhone = (value) => {
        if (!value) return '';
        const digits = value.toString().replace(/\D/g, '').slice(0, 11);
        const mask = digits.length > 10 ? '(##) #####-####' : '(##) ####-####';
        return applyMask(digits, mask);
    };

    return {
        applyMask,
        maskProcessNumber,
        maskPhone,
    };
}

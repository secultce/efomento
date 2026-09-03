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

    const maskCpf = (value, fallback = 'Não informado') => {
        if (!value) return fallback;
        const digits = String(value).replace(/\D/g, '');
        if (!digits) return fallback;
        return applyMask(digits, '###.###.###-##');
    };

    const maskCnpj = (value, fallback = 'Não informado') => {
        if (!value) return fallback;
        const digits = String(value).replace(/\D/g, '');
        if (!digits) return fallback;
        return applyMask(digits, '##.###.###/####-##');
    };

    const maskCpfCnpj = (value, fallback = 'Não informado') => {
        if (!value) return fallback;
        const digits = String(value).replace(/\D/g, '');
        if (!digits) return fallback;

        if (digits.length === 11) {
            return maskCpf(digits, fallback);
        }

        if (digits.length === 14) {
            return maskCnpj(digits, fallback);
        }

        return digits;
    };

    const resolveKey = (key, obj) => key.split('.').reduce((acc, part) => acc?.[part], obj);

    const getCpfCnpj =
        (key, fallback = 'Não informado') =>
        (project) => {
            const val = resolveKey(key, project);
            return maskCpfCnpj(val, fallback);
        };

    return {
        applyMask,
        maskProcessNumber,
        maskPhone,
        maskCpf,
        maskCnpj,
        maskCpfCnpj,
        getCpfCnpj,
    };
}

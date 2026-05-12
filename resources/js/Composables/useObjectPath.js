export function useObjectPath() {
    const resolvePath = (obj, key) => {
        if (!obj || typeof key !== 'string') {
            return { exists: false, value: undefined };
        }

        const path = key.replace(/\[(\d+)\]/g, '.$1').split('.');

        let current = obj;

        for (const part of path) {
            if (current == null || !(part in current)) {
                return { exists: false, value: undefined };
            }
            current = current[part];
        }

        return { exists: true, value: current };
    };

    return {
        resolvePath,
    };
}

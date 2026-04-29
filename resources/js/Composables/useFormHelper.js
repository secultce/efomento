import { useObjectPath } from '@/Composables/useObjectPath'

export function useFormHelper({ form, project }) {

    const { resolvePath } = useObjectPath()
    const getFieldValue = (key) => {
        const formResult = resolvePath(form?.value ?? form, key)

        const hasValue = (v) => v !== null && v !== undefined

        if (formResult.exists && hasValue(formResult.value)) {
            return formResult.value
        }

        const projectResult = resolvePath(project?.value ?? project, key)

        return projectResult.exists ? projectResult.value : null
    }

    const setFieldValue = (obj, key, value) => {
        if (!obj || typeof key !== 'string') return

        const path = key.replace(/\[(\d+)\]/g, '.$1').split('.')

        let current = obj

        path.forEach((part, index) => {
            const isLast = index === path.length - 1

            if (isLast) {
                current[part] = value
                return
            }

            if (!(part in current)) {
                const nextPart = path[index + 1]
                current[part] = isNaN(nextPart) ? {} : []
            }

            current = current[part]
        })
    }

    const buildFormFromSections = (sections) => {
        const form = {}

        sections.forEach(section => {
            section.fields.forEach(field => {
                if (!field.isEditable) return
                setFieldValue(form, field.key, null)
            })
        })

        return form
    }


    return {
        getFieldValue,
        setFieldValue,
        buildFormFromSections,
    }
}
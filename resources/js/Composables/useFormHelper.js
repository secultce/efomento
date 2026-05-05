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

    const setFieldValue = (obj, path, value) => {
        const parts = path.split('.')

        let current = obj

        parts.forEach((part, index) => {
            const isLast = index === parts.length - 1

            // check for array syntax
            const match = part.match(/^(\w+)\[(\d+)\]$/)

            if (match) {
            const key = match[1]
            const arrayIndex = Number(match[2])

            // ensure parent key is array
            if (!current[key]) {
                current[key] = []
            }

            // ensure index exists
            if (!current[key][arrayIndex]) {
                current[key][arrayIndex] = {}
            }

            if (isLast) {
                current[key][arrayIndex] = value
            } else {
                current = current[key][arrayIndex]
            }
            } else {
            // normal object key
            if (isLast) {
                current[part] = value
            } else {
                if (!current[part] || typeof current[part] !== 'object') {
                current[part] = {}
                }
                current = current[part]
            }
            }
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
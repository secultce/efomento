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

    return {
        getFieldValue,
    }
}
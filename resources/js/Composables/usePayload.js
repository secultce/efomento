import { useObjectPath } from '@/Composables/useObjectPath'
import { useFormHelper } from '@/Composables/useFormHelper'

export function usePayload() {
    const { resolvePath } = useObjectPath()
    const { setFieldValue } = useFormHelper({})
    
    const sanitizePayload = (form, sections) => {
        const payload = {}

        const hasValue = (v) =>
            v !== null &&
            v !== undefined &&
            v !== ''

        sections.forEach(section => {
            section.fields.forEach(field => {
                if (field.inputType === 'money_extenso') return

                const { exists, value } = resolvePath(form, field.key)

                if (exists && hasValue(value)) {
                    setFieldValue(payload, field.key, value)
                }
            })
        })

        return payload
    }

    return {
        sanitizePayload,
    }
}

import { ref, reactive } from 'vue'
import { useI18n } from 'vue-i18n'

interface FieldErrors {
    [field: string]: string[]
}

export function useFormErrors() {
    const { t } = useI18n()
    const generalError = ref('')
    const fieldErrors = reactive<FieldErrors>({})

    function setErrors(err: unknown) {
        generalError.value = t('errors.processing')
        fieldErrors as FieldErrors
        Object.keys(fieldErrors).forEach((k) => delete (fieldErrors as Record<string, unknown>)[k])

        const e = err as Error & {
            data?: {
                message?: string
                errors?: Record<string, string[]>
            }
        }

        if (e.data?.errors) {
            for (const [field, msgs] of Object.entries(e.data.errors)) {
                fieldErrors[field] = msgs
            }
        } else {
            generalError.value = e.data?.message || e.message || t('errors.generic')
        }
    }

    function clearErrors() {
        generalError.value = ''
        Object.keys(fieldErrors).forEach((k) => delete (fieldErrors as Record<string, unknown>)[k])
    }

    function fieldError(field: string): string {
        return fieldErrors[field]?.[0] ?? ''
    }

    function hasError(field: string): boolean {
        return !!fieldErrors[field]?.length
    }

    return {
        generalError,
        fieldErrors,
        setErrors,
        clearErrors,
        fieldError,
        hasError,
    }
}

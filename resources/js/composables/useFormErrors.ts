import { reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'primevue/usetoast'

interface FieldErrors {
    [field: string]: string[]
}

export function useFormErrors() {
    const { t } = useI18n()
    const toast = useToast()
    const fieldErrors = reactive<FieldErrors>({})

    function setErrors(err: unknown) {
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

            if (!('totp_code' in (e.data.errors ?? {}))) {
                toast.add({
                    severity: 'error',
                    summary: t('errors.processing'),
                    life: 5000,
                })
            }
        } else {
            toast.add({
                severity: 'error',
                summary: t('errors.generic'),
                detail: e.data?.message || e.message,
                life: 5000,
            })
        }
    }

    function clearErrors() {
        Object.keys(fieldErrors).forEach((k) => delete (fieldErrors as Record<string, unknown>)[k])
    }

    function fieldError(field: string): string {
        return fieldErrors[field]?.[0] ?? ''
    }

    function hasError(field: string): boolean {
        return !!fieldErrors[field]?.length
    }

    return {
        fieldErrors,
        setErrors,
        clearErrors,
        fieldError,
        hasError,
    }
}

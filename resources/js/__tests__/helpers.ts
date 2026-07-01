import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import InputOtp from 'primevue/inputotp'
import FloatLabel from 'primevue/floatlabel'
import Message from 'primevue/message'
import en from '@/i18n/en'

export const i18n = createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages: { en },
})

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function mountWithPlugins(component: any, options?: any) {
    const existingPlugins: unknown[] = options?.global?.plugins ?? []
    const existingComponents: Record<string, unknown> = options?.global?.components ?? {}

    return mount(component, {
        ...options,
        global: {
            ...options?.global,
            plugins: [
                ...existingPlugins,
                [i18n],
                [PrimeVue, {
                    theme: {
                        preset: Aura,
                        options: { darkModeSelector: false, cssLayer: false },
                    },
                }],
            ],
            components: {
                ...existingComponents,
                PvDialog: Dialog,
                PvButton: Button,
                PvInputText: InputText,
                PvPassword: Password,
                PvInputOtp: InputOtp,
                PvFloatLabel: FloatLabel,
                PvMessage: Message,
            },
        },
    })
}

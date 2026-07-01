import { createApp, type App as VueApp } from 'vue'
import { createPinia } from 'pinia'
import router from '@/router'
import Root from '@/App.vue'
import { i18n } from '@/i18n'
import PrimeVue from 'primevue/config'
import { CvPreset } from '@/prime-preset'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import InputOtp from 'primevue/inputotp'
import FloatLabel from 'primevue/floatlabel'
import SelectButton from 'primevue/selectbutton'
import Toast from 'primevue/toast'
import ToastService from 'primevue/toastservice'
import MinimalismCard from '@/components/MinimalismCard.vue'

export function createApplication(): VueApp {
    const app = createApp(Root)

    app.use(createPinia())
    app.use(router)
    app.use(i18n)
    app.use(PrimeVue, {
        theme: {
            preset: CvPreset,
            options: {
                darkModeSelector: false,
                cssLayer: false,
            },
        },
    })
    app.use(ToastService)

    app.component('PvDialog', Dialog)
    app.component('PvButton', Button)
    app.component('PvInputText', InputText)
    app.component('PvPassword', Password)
    app.component('PvInputOtp', InputOtp)
    app.component('PvFloatLabel', FloatLabel)
    app.component('PvSelectButton', SelectButton)
    app.component('PvToast', Toast)
    app.component('MinimalismCard', MinimalismCard)

    return app
}

const app = createApplication()
app.mount('#app')

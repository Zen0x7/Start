import { createApp, type App as VueApp } from 'vue'
import { createPinia } from 'pinia'
import router from '@/router'
import Root from '@/App.vue'
import { i18n } from '@/i18n'
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import InputOtp from 'primevue/inputotp'
import FloatLabel from 'primevue/floatlabel'
import Message from 'primevue/message'

export function createApplication(): VueApp {
    const app = createApp(Root)

    app.use(createPinia())
    app.use(router)
    app.use(i18n)
    app.use(PrimeVue, {
        theme: {
            preset: Aura,
            options: {
                darkModeSelector: false,
                cssLayer: false,
            },
        },
    })

    app.component('PvDialog', Dialog)
    app.component('PvButton', Button)
    app.component('PvInputText', InputText)
    app.component('PvPassword', Password)
    app.component('PvInputOtp', InputOtp)
    app.component('PvFloatLabel', FloatLabel)
    app.component('PvMessage', Message)

    return app
}

const app = createApplication()
app.mount('#app')

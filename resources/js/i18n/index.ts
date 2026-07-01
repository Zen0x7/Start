import { createI18n } from 'vue-i18n'
import { useStorage } from '@vueuse/core'
import en from './en'
import es from './es'

const savedLocale = useStorage('app_locale', '')

function detectLocale(): string {
    if (savedLocale.value) return savedLocale.value

    const browserLang =
        typeof navigator !== 'undefined' && navigator.language
            ? navigator.language.split('-')[0]
            : ''

    if (['es'].includes(browserLang)) {
        return 'es'
    }

    return 'en'
}

const locale = detectLocale()

export const i18n = createI18n({
    legacy: false,
    locale,
    fallbackLocale: 'en',
    messages: {
        en,
        es,
    },
})

export function setLocale(lang: string): void {
    i18n.global.locale.value = lang as 'en' | 'es'
    savedLocale.value = lang
    document.querySelector('html')?.setAttribute('lang', lang)
}

export function currentLocale(): string {
    return i18n.global.locale.value as string
}

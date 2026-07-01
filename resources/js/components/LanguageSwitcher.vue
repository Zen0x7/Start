<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useStorage } from '@vueuse/core'

const { locale } = useI18n()
const saved = useStorage('app_locale', '')

const languages = [
    { code: 'en', label: 'EN', icon: '🇬🇧' },
    { code: 'es', label: 'ES', icon: '🇪🇸' },
]

function select(lang: string) {
    locale.value = lang
    saved.value = lang
    document.querySelector('html')?.setAttribute('lang', lang)
}
</script>

<template>
    <div
        class="flex items-center gap-1"
        role="radiogroup"
        :aria-label="locale === 'es' ? 'Seleccionar idioma' : 'Select language'"
    >
        <button
            v-for="lang in languages"
            :key="lang.code"
            role="radio"
            :aria-checked="locale === lang.code"
            :aria-label="lang.code === 'en' ? 'English' : 'Español'"
            class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="locale === lang.code
                ? 'bg-blue-600 text-white shadow-sm'
                : 'border bg-white text-gray-600 hover:bg-gray-50'"
            @click="select(lang.code)"
        >
            {{ lang.label }}
        </button>
    </div>
</template>

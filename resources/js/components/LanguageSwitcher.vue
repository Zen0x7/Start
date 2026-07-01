<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useStorage } from '@vueuse/core'

const { locale } = useI18n()
const saved = useStorage('app_locale', '')

const languages = [
    { code: 'en', label: 'EN' },
    { code: 'es', label: 'ES' },
]

function select(lang: string) {
    locale.value = lang
    saved.value = lang
    document.querySelector('html')?.setAttribute('lang', lang)
}
</script>

<template>
    <div
        class="flex items-center gap-0 border-2 border-[#111] bg-white"
        role="radiogroup"
        :aria-label="locale === 'es' ? 'Seleccionar idioma' : 'Select language'"
    >
        <button
            v-for="lang in languages"
            :key="lang.code"
            role="radio"
            :aria-checked="locale === lang.code"
            :aria-label="lang.code === 'en' ? 'English' : 'Español'"
            class="px-3 py-1.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-[#111] focus:ring-inset"
            :class="locale === lang.code
                ? 'bg-[#111] text-white'
                : 'bg-white text-[#555] hover:bg-[#f5f5f0]'"
            @click="select(lang.code)"
        >
            {{ lang.label }}
        </button>
    </div>
</template>

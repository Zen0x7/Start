<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const auth = useAuthStore()
</script>

<template>
    <main class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-4">
        <div
            class="w-full max-w-lg border-2 border-[#111] bg-white p-12 text-center"
            style="box-shadow: 10px 10px 0 rgba(0, 0, 0, 0.06)"
        >
            <h2 class="mb-2 text-[0.6875rem] font-semibold uppercase tracking-[0.15em] text-[#999]">
                Start
            </h2>
            <h1 class="mb-4 text-4xl font-bold tracking-tight text-[#111] sm:text-5xl">
                {{ t('home.welcome') }}
            </h1>
            <p class="mb-8 text-lg text-[#555]">{{ t('home.description') }}</p>

            <div v-if="!auth.isAuthenticated" class="flex items-center justify-center gap-4">
                <router-link
                    :to="{ name: 'register' }"
                    class="border-2 border-[#111] bg-[#111] px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-[#333]"
                >
                    {{ t('auth.register') }}
                </router-link>
                <router-link
                    :to="{ name: 'login' }"
                    class="border-2 border-[#ddd] bg-white px-6 py-3 text-sm font-semibold text-[#555] transition-colors hover:border-[#999] hover:text-[#111]"
                >
                    {{ t('auth.login') }}
                </router-link>
            </div>

            <div v-else>
                <router-link
                    :to="{ name: 'dashboard' }"
                    class="border-2 border-[#111] bg-[#111] px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-[#333]"
                >
                    {{ t('home.dashboard') }}
                </router-link>
            </div>
        </div>
    </main>
</template>

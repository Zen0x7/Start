<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const auth = useAuthStore()
</script>

<template>
    <main class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-4">
        <div class="max-w-lg text-center">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
                {{ t('home.welcome') }}
            </h1>
            <p class="mt-4 text-lg text-gray-600">
                {{ t('home.description') }}
            </p>

            <div
                v-if="!auth.isAuthenticated"
                class="mt-10 flex items-center justify-center gap-4"
            >
                <router-link
                    :to="{ name: 'register' }"
                    class="rounded-lg bg-blue-600 px-6 py-3 text-white hover:bg-blue-700"
                >
                    {{ t('auth.register') }}
                </router-link>
                <router-link
                    :to="{ name: 'login' }"
                    class="rounded-lg border border-gray-300 px-6 py-3 text-gray-700 hover:bg-gray-50"
                >
                    {{ t('auth.login') }}
                </router-link>
            </div>

            <div v-else class="mt-10">
                <router-link
                    :to="{ name: 'dashboard' }"
                    class="rounded-lg bg-blue-600 px-6 py-3 text-white hover:bg-blue-700"
                >
                    {{ t('home.dashboard') }}
                </router-link>
            </div>
        </div>
    </main>
</template>

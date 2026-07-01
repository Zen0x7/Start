<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import TopBar from '@/components/TopBar.vue'

const { t } = useI18n()
const auth = useAuthStore()
</script>

<template>
    <div class="min-h-screen bg-[#fcfcf8]">
        <TopBar />

        <main class="mx-auto max-w-7xl px-4 py-12">
            <div class="border-2 border-[#111] bg-white p-8" style="box-shadow: 10px 10px 0 rgba(0,0,0,0.06)">
                <h2 class="mb-1 text-[0.6875rem] font-semibold uppercase tracking-[0.15em] text-[#999]">{{ t('dashboard.title') }}</h2>
                <p class="mb-2 text-2xl font-bold text-[#111]">{{ t('dashboard.welcome', { name: auth.currentUser?.name }) }}</p>
                <p class="text-[#555]">{{ t('dashboard.logged_in_as') }} <strong class="text-[#111]">{{ auth.currentUser?.email }}</strong>.</p>
                <p class="mt-2 text-sm text-[#555]">{{ t('dashboard.verified_and_totp') }}</p>
            </div>
        </main>
    </div>
</template>

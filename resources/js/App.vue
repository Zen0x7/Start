<script setup lang="ts">
import { RouterView, useRoute } from 'vue-router'
import { computed } from 'vue'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'
import AppLogo from '@/components/AppLogo.vue'

const route = useRoute()
const showGlobalNav = computed(() => !route.meta.hideGlobalNav)
</script>

<template>
    <div class="min-h-screen bg-[#fcfcf8]">
        <PvToast position="bottom-right" />

        <div v-if="showGlobalNav" class="mx-auto max-w-7xl px-4 py-3">
            <div class="flex items-center justify-between">
                <AppLogo class="h-8 w-8 text-[#111]" />
                <LanguageSwitcher />
            </div>
        </div>

        <RouterView />
    </div>
</template>

<script setup lang="ts">
import { RouterView } from 'vue-router'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'
</script>

<template>
    <div class="relative min-h-screen">
        <PvToast position="bottom-right" />
        <div class="fixed right-4 top-4 z-50">
            <LanguageSwitcher />
        </div>
        <RouterView />
    </div>
</template>

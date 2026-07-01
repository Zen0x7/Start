<script setup lang="ts">
defineProps<{ msg?: string }>()
</script>

<template>
    <div class="text-center">
        <h1 class="text-4xl font-bold tracking-tight">
            {{ msg ?? 'Vue 3 + TypeScript + Laravel' }}
        </h1>
        <p class="mt-2 text-gray-600">
            SPA powered by Pinia &amp; Vue Router
        </p>
    </div>
</template>

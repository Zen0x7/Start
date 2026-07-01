<script setup lang="ts">
defineProps<{
    class?: string
}>()
</script>

<template>
    <svg
        :class="class"
        viewBox="0 0 40 40"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-label="Start logo"
    >
        <polygon
            points="20,3 37,20 20,37 3,20"
            stroke="currentColor"
            stroke-width="2"
            stroke-linejoin="round"
            fill="none"
        />
        <line x1="3" y1="20" x2="37" y2="20" stroke="currentColor" stroke-width="2" />
    </svg>
</template>

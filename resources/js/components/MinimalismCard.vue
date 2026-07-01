<script setup lang="ts">
defineProps<{
    icon?: string
    label: string
    message?: string
}>()
</script>

<template>
    <main class="mx-auto flex min-h-screen max-w-md items-center px-4">
        <div
            class="w-full border-2 border-[#111] bg-white p-8 text-center"
            style="box-shadow: 10px 10px 0 rgba(0,0,0,0.06)"
        >
            <div
                v-if="icon"
                class="mx-auto mb-6 flex h-16 w-16 items-center justify-center border-2 border-[#111] bg-[#f5f5f0]"
                v-html="icon"
            />

            <h2
                v-if="label"
                class="mb-2 text-[0.6875rem] font-semibold uppercase tracking-[0.15em] text-[#999]"
            >
                {{ label }}
            </h2>

            <p
                v-if="message"
                class="mb-6 text-sm leading-relaxed text-[#555]"
            >
                {{ message }}
            </p>

            <div class="space-y-4">
                <slot />
            </div>

            <div
                v-if="$slots.footer"
                class="mt-6 text-xs text-[#999]"
            >
                <slot name="footer" />
            </div>
        </div>
    </main>
</template>

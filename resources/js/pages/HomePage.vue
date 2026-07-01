<script setup lang="ts">
import { useCounterStore } from '@/stores/counter'
import HelloWorld from '@/components/HelloWorld.vue'

const counter = useCounterStore()
</script>

<template>
    <main class="mx-auto max-w-7xl px-4 py-12">
        <HelloWorld />

        <section class="mt-12 text-center">
            <h2 class="mb-4 text-2xl font-semibold">Pinia Counter</h2>

            <p class="mb-4 text-4xl font-bold">{{ counter.count }}</p>

            <div class="flex items-center justify-center gap-4">
                <button
                    class="rounded-lg bg-gray-200 px-4 py-2 hover:bg-gray-300"
                    @click="counter.decrement()"
                >
                    -
                </button>
                <button
                    class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                    @click="counter.increment()"
                >
                    +
                </button>
                <button
                    class="rounded-lg bg-red-100 px-4 py-2 text-red-600 hover:bg-red-200"
                    @click="counter.reset()"
                >
                    Reset
                </button>
            </div>

            <p class="mt-4 text-gray-600">
                Double: {{ counter.doubleCount }}
            </p>
        </section>
    </main>
</template>

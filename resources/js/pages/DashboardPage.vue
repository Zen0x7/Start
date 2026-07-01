<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

function handleLogout() {
    auth.logout()
    router.push({ name: 'login' })
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <header class="border-b bg-white shadow-sm">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                <h1 class="text-xl font-bold text-gray-900">Dashboard</h1>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600">{{ auth.currentUser?.name }}</span>
                    <button
                        class="rounded-lg bg-red-50 px-3 py-1.5 text-sm text-red-600 hover:bg-red-100"
                        @click="handleLogout"
                    >
                        Cerrar Sesión
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-12">
            <div class="rounded-xl border bg-white p-8 shadow-sm">
                <h2 class="text-2xl font-semibold text-gray-900">
                    Bienvenido, {{ auth.currentUser?.name }}
                </h2>
                <p class="mt-2 text-gray-600">
                    Has iniciado sesión como <strong>{{ auth.currentUser?.email }}</strong>.
                </p>
                <p class="mt-1 text-sm text-green-600">
                    Correo verificado y autenticación de dos factores activa.
                </p>
            </div>
        </main>
    </div>
</template>

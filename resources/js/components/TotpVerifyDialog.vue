<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '@/services/api'

const { t } = useI18n()

const emit = defineEmits<{
    (e: 'verified'): void
    (e: 'cancel'): void
}>()

const visible = ref(true)
const totpCode = ref('')
const loading = ref(false)
const error = ref('')

async function handleSubmit() {
    error.value = ''
    loading.value = true

    try {
        await api.post('/auth/totp/confirm-action', {
            totp_code: totpCode.value,
            action: 'confirm_operation',
        })
        emit('verified')
        visible.value = false
    } catch (err: unknown) {
        const e = err as Error & { data?: { message?: string } }
        error.value = e.data?.message || e.message || t('errors.generic')
    } finally {
        loading.value = false
    }
}

function onHide() {
    emit('cancel')
}
</script>

<template>
    <div
        v-if="visible"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        role="dialog"
        aria-modal="true"
        :aria-label="t('totp.confirm_action')"
        @click.self="onHide"
    >
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold">{{ t('totp.confirm_action') }}</h2>
                <button
                    aria-label="Close"
                    class="rounded-full p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    @click="onHide"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <p class="mb-4 text-sm text-gray-600">
                {{ t('totp.confirm_action_desc') }}
            </p>

            <p
                v-if="error"
                class="mb-3 rounded-lg bg-red-50 p-2 text-sm text-red-600"
                role="alert"
            >
                {{ error }}
            </p>

            <form @submit.prevent="handleSubmit">
                <input
                    v-model="totpCode"
                    type="text"
                    maxlength="6"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    required
                    autofocus
                    class="mb-4 w-full rounded-lg border border-gray-300 px-4 py-2 text-center text-2xl tracking-widest focus:border-blue-500 focus:outline-none"
                    :placeholder="t('totp.code_placeholder')"
                    :aria-label="t('totp.code_placeholder')"
                />

                <div class="flex gap-3">
                    <PvButton
                        type="button"
                        severity="secondary"
                        class="flex-1"
                        @click="onHide"
                    >
                        {{ t('totp.cancel') }}
                    </PvButton>
                    <PvButton
                        type="submit"
                        :loading="loading"
                        :disabled="loading || totpCode.length !== 6"
                        class="flex-1"
                    >
                        {{ t('totp.confirm') }}
                    </PvButton>
                </div>
            </form>
        </div>
    </div>
</template>

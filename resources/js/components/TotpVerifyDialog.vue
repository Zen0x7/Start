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
        <div class="w-full max-w-sm border-2 border-[#111] bg-white p-6" style="box-shadow: 10px 10px 0 rgba(0,0,0,0.06)">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-bold text-[#111]">{{ t('totp.confirm_action') }}</h2>
                <button
                    aria-label="Close"
                    class="flex h-8 w-8 items-center justify-center text-[#999] transition-colors hover:bg-[#f5f5f0] hover:text-[#111] focus:outline-none focus:ring-2 focus:ring-[#111]"
                    @click="onHide"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <p class="mb-4 text-sm text-[#555]">{{ t('totp.confirm_action_desc') }}</p>

            <p v-if="error" class="mb-3 border-2 border-[#dc2626] bg-[#fef2f2] p-2 text-sm text-[#dc2626]" role="alert">
                {{ error }}
            </p>

            <form @submit.prevent="handleSubmit">
                <div class="mb-4 flex justify-center">
                    <PvInputOtp v-model="totpCode" :length="6" integer-only :aria-label="t('totp.code_placeholder')" />
                </div>

                <div class="flex gap-3">
                    <PvButton type="button" severity="secondary" class="flex-1" @click="onHide">
                        {{ t('totp.cancel') }}
                    </PvButton>
                    <PvButton type="submit" :loading="loading" :disabled="loading || totpCode.length !== 6" class="flex-1">
                        {{ t('totp.confirm') }}
                    </PvButton>
                </div>
            </form>
        </div>
    </div>
</template>

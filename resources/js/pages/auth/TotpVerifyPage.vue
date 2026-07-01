<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Icons } from '@/components/icons'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const tempToken = ref((route.query.temp_token as string) || '')
const totpCode = ref('')
const loading = ref(false)
const error = ref('')

async function handleSubmit() {
    error.value = ''
    loading.value = true

    try {
        await auth.verifyTotp(tempToken.value, totpCode.value)
        router.push({ name: 'dashboard' })
    } catch (err: unknown) {
        const e = err as Error & { data?: { message?: string } }
        error.value = e.data?.message || e.message || t('errors.generic')
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <MinimalismCard
        :icon="Icons.lock"
        :label="t('totp.verify_title')"
        :message="t('totp.verify_desc')"
    >
        <p v-if="error" class="mb-2 text-sm text-[#dc2626]" role="alert">
            {{ error }}
        </p>

        <div class="flex justify-center">
            <PvInputOtp v-model="totpCode" :length="6" integer-only :aria-label="t('totp.code_placeholder')" />
        </div>

        <PvButton type="submit" :loading="loading" :disabled="loading || totpCode.length !== 6" class="w-full" :label="loading ? t('totp.verifying') : t('totp.verify_code')" @click="handleSubmit" />
    </MinimalismCard>
</template>

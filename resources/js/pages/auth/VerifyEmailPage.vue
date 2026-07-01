<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { api } from '@/services/api'
import { Icons } from '@/components/icons'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const email = ref((route.query.email as string) || '')
const resendLoading = ref(false)
const resendMessage = ref('')
const resendError = ref('')

onMounted(() => {
    if (auth.isAuthenticated && auth.currentUser) {
        email.value = auth.currentUser.email
    }
})

async function resendEmail() {
    if (!email.value) return

    resendLoading.value = true
    resendMessage.value = ''
    resendError.value = ''

    try {
        const res = await api.post<{ message: string }>('/auth/resend-verification', {
            email: email.value,
        })
        resendMessage.value = res.message || t('verify.resent')
    } catch (err: unknown) {
        const e = err as Error & { data?: { message?: string } }
        resendError.value = e.data?.message || e.message || t('errors.generic')
    } finally {
        resendLoading.value = false
    }
}
</script>

<template>
    <MinimalismCard
        :icon="Icons.mail"
        :label="t('verify.title')"
        :message="t('verify.waiting', { email })"
    >
        <p v-if="resendMessage" class="mb-2 text-sm text-[#555]">
            {{ resendMessage }}
        </p>

        <p v-if="resendError" class="mb-2 text-sm text-[#dc2626]">
            {{ resendError }}
        </p>

        <PvButton
            :loading="resendLoading"
            class="w-full"
            :label="resendLoading ? t('verify.sending') : t('verify.resend')"
            @click="resendEmail"
        />

        <template #footer>
            {{ t('verify.wrong_account') }}
            <router-link
                :to="{ name: 'register' }"
                class="font-semibold text-[#111] underline hover:text-[#333]"
                >{{ t('verify.create_another') }}</router-link
            >
        </template>
    </MinimalismCard>
</template>

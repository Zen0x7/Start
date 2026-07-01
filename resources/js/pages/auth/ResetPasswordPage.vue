<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/services/api'
import { Icons } from '@/components/icons'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const token = route.params.token as string
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const totpCode = ref('')
const hasTotp = ref(false)
const loading = ref(false)
const error = ref('')
const done = ref(false)

onMounted(async () => {
    try {
        const res = await api.get(`/auth/password/reset/${encodeURIComponent(token)}`)
        const data = res as unknown as { email: string; has_totp: boolean }
        email.value = data.email
        hasTotp.value = data.has_totp
    } catch {
        error.value = t('password.invalid_link')
    }
})

async function handleSubmit() {
    error.value = ''
    loading.value = true

    try {
        const body: Record<string, string> = {
            token,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        }

        if (hasTotp.value) {
            body.totp_code = totpCode.value
        }

        await api.post('/auth/password/reset', body)
        done.value = true
    } catch (err: unknown) {
        const e = err as Error & { data?: { message?: string } }
        error.value = e.data?.message || e.message || t('errors.generic')
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <template v-if="error && !email">
        <MinimalismCard :icon="Icons.lock" :label="t('password.forgot_title')" :message="error">
            <template #footer>
                <router-link
                    :to="{ name: 'forgot-password' }"
                    class="font-semibold text-[#111] underline hover:text-[#333]"
                    >{{ t('password.request_again') }}</router-link
                >
            </template>
        </MinimalismCard>
    </template>

    <template v-else-if="done">
        <MinimalismCard
            :icon="Icons.check"
            :label="t('password.reset_title')"
            :message="t('password.reset_desc')"
        >
            <template #footer>
                <router-link
                    :to="{ name: 'login' }"
                    class="font-semibold text-[#111] underline hover:text-[#333]"
                    >{{ t('auth.login') }}</router-link
                >
            </template>
        </MinimalismCard>
    </template>

    <template v-else>
        <MinimalismCard :icon="Icons.lock" :label="t('password.reset_title')">
            <p v-if="error" class="mb-2 text-sm text-[#dc2626]">{{ error }}</p>

            <form @submit.prevent="handleSubmit" class="space-y-4">
                <PvFloatLabel>
                    <PvPassword
                        id="rp-password"
                        input-id="rp-password"
                        v-model="password"
                        class="w-full"
                        :feedback="false"
                        toggle-mask
                    />
                    <label for="rp-password">{{ t('auth.password') }}</label>
                </PvFloatLabel>

                <PvFloatLabel>
                    <PvPassword
                        id="rp-confirm"
                        input-id="rp-confirm"
                        v-model="passwordConfirmation"
                        class="w-full"
                        :feedback="false"
                        toggle-mask
                    />
                    <label for="rp-confirm">{{ t('auth.password_confirm') }}</label>
                </PvFloatLabel>

                <div v-if="hasTotp" class="pt-2">
                    <p class="mb-2 text-sm text-[#555]">{{ t('password.totp_required') }}</p>
                    <div class="flex justify-center">
                        <PvInputOtp v-model="totpCode" :length="6" integer-only />
                    </div>
                </div>

                <PvButton
                    type="submit"
                    :loading="loading"
                    class="w-full"
                    :label="loading ? t('password.resetting') : t('password.reset_button')"
                />
            </form>

            <template #footer>
                <router-link
                    :to="{ name: 'login' }"
                    class="font-semibold text-[#111] underline hover:text-[#333]"
                    >{{ t('auth.login') }}</router-link
                >
            </template>
        </MinimalismCard>
    </template>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { useFormErrors } from '@/composables/useFormErrors'
import { Icons } from '@/components/icons'

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()
const { setErrors, clearErrors, fieldError, hasError } = useFormErrors()

const email = ref('')
const password = ref('')
const loading = ref(false)

async function handleSubmit() {
    clearErrors()
    loading.value = true

    try {
        const result = await auth.login(email.value, password.value)

        if ('totp_status' in result) {
            const routeName = result.totp_status === 'setup_required' ? 'totp-setup' : 'totp-verify'
            router.push({ name: routeName, query: { temp_token: result.temp_token, email: email.value } })
            return
        }

        router.push({ name: 'verify-email', query: { email: result.email } })
    } catch (err: unknown) {
        setErrors(err)
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <MinimalismCard
        :icon="Icons.person"
        :label="t('auth.login_title')"
    >
        <form @submit.prevent="handleSubmit" class="space-y-7 text-left">
            <div>
                <PvFloatLabel>
                    <PvInputText id="email" v-model="email" class="w-full" :class="{ 'p-invalid': hasError('email') }" />
                    <label for="email">{{ t('auth.email') }}</label>
                </PvFloatLabel>
                <small v-if="hasError('email')" class="text-[#dc2626]">{{ fieldError('email') }}</small>
            </div>

            <div>
                <PvFloatLabel>
                    <PvPassword input-id="password" v-model="password" class="w-full" :class="{ 'p-invalid': hasError('password') }" :feedback="false" toggle-mask />
                    <label for="password">{{ t('auth.password') }}</label>
                </PvFloatLabel>
                <small v-if="hasError('password')" class="text-[#dc2626]">{{ fieldError('password') }}</small>
            </div>

            <PvButton type="submit" :loading="loading" class="w-full" :label="loading ? t('auth.logging_in') : t('auth.login')" />
        </form>

        <p class="mt-2 text-center text-xs text-[#999]">
            <router-link :to="{ name: 'forgot-password' }" class="underline hover:text-[#111]">{{ t('password.forgot_title') }}</router-link>
        </p>

        <template #footer>
            {{ t('auth.register_link') }}
            <router-link :to="{ name: 'register' }" class="font-semibold text-[#111] underline hover:text-[#333]">{{ t('auth.register') }}</router-link>
        </template>
    </MinimalismCard>
</template>

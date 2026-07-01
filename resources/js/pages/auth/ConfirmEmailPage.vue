<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useFormErrors } from '@/composables/useFormErrors'
import { api } from '@/services/api'
import { Icons } from '@/components/icons'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { fieldError, hasError, setErrors, clearErrors } = useFormErrors()

const token = route.params.token as string
const email = ref('')
const password = ref('')
const loading = ref(false)
const verified = ref(false)
const initialError = ref('')

onMounted(async () => {
    try {
        const res = await api.get<{ email: string }>(
            `/auth/verify-email/${encodeURIComponent(token)}`,
        )
        email.value = res.data?.email ?? ''
    } catch {
        initialError.value = t('verify.invalid_link')
    }
})

async function handleSubmit() {
    clearErrors()
    loading.value = true

    try {
        const res = await api.post<{ temp_token: string; user: { email: string } }>(
            '/auth/verify-email',
            { token, password: password.value },
        )
        const data = res as unknown as { temp_token: string; user: { name: string; email: string } }
        verified.value = true

        setTimeout(() => {
            router.push({
                name: 'totp-setup',
                query: { temp_token: data.temp_token, email: data.user.email },
            })
        }, 1500)
    } catch (err: unknown) {
        setErrors(err)
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <template v-if="initialError && !email && !loading">
        <MinimalismCard :icon="Icons.mail" :label="t('verify.title')" :message="initialError">
            <template #footer>
                <router-link
                    :to="{ name: 'register' }"
                    class="font-semibold text-[#111] underline hover:text-[#333]"
                    >{{ t('verify.create_another') }}</router-link
                >
            </template>
        </MinimalismCard>
    </template>

    <template v-else-if="verified">
        <MinimalismCard :icon="Icons.check" label="Status" :message="t('verify.confirmed')">
            <p class="text-sm text-[#555]">{{ t('verify.redirecting_setup') }}</p>
        </MinimalismCard>
    </template>

    <template v-else>
        <MinimalismCard
            :icon="Icons.mail"
            :label="t('verify.confirm_title')"
            :message="t('verify.confirm_desc')"
        >
            <form @submit.prevent="handleSubmit" class="text-left">
                <div>
                    <PvFloatLabel>
                        <PvPassword
                            input-id="password"
                            v-model="password"
                            class="w-full"
                            :class="{ 'p-invalid': hasError('password') }"
                            :feedback="false"
                            toggle-mask
                        />
                        <label for="password">{{ t('auth.password') }}</label>
                    </PvFloatLabel>
                    <small v-if="hasError('password')" class="text-[#dc2626]">{{
                        fieldError('password')
                    }}</small>
                </div>

                <PvButton
                    type="submit"
                    :loading="loading"
                    class="w-full"
                    :label="loading ? t('verify.confirming') : t('verify.confirm_button')"
                />
            </form>

            <template #footer>
                <router-link
                    :to="{ name: 'register' }"
                    class="font-semibold text-[#111] underline hover:text-[#333]"
                    >{{ t('verify.create_another') }}</router-link
                >
            </template>
        </MinimalismCard>
    </template>
</template>

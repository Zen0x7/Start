<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useFormErrors } from '@/composables/useFormErrors'
import { api } from '@/services/api'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { generalError, fieldError, hasError, setErrors, clearErrors } = useFormErrors()

const token = route.params.token as string
const email = ref('')
const password = ref('')
const loading = ref(false)
const verified = ref(false)

onMounted(async () => {
    try {
        const res = await api.get<{ email: string }>(
            `/auth/verify-email/${encodeURIComponent(token)}`,
        )
        email.value = res.data?.email ?? ''
    } catch {
        clearErrors()
        generalError.value = t('verify.invalid_link')
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
    <main class="mx-auto flex min-h-screen max-w-md items-center px-4">
        <template v-if="generalError && !email && !loading">
            <div class="w-full space-y-4 text-center">
                <PvMessage
                    severity="error"
                    variant="simple"
                    :closable="false"
                >
                    {{ generalError }}
                </PvMessage>
                <router-link
                    :to="{ name: 'register' }"
                    class="text-blue-600 hover:underline"
                >{{ t('verify.create_another') }}</router-link>
            </div>
        </template>

        <template v-else-if="verified">
            <div class="w-full space-y-4 text-center">
                <div class="rounded-full bg-green-100 p-4 mx-auto w-16 h-16 flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ t('verify.confirmed') }}
                </h1>
                <p class="text-gray-600">{{ t('verify.redirecting_setup') }}</p>
            </div>
        </template>

        <template v-else>
            <form
                class="flex w-full flex-col gap-5"
                @submit.prevent="handleSubmit"
            >
                <h1 class="text-center text-2xl font-bold">
                    {{ t('verify.confirm_title') }}
                </h1>

                <p class="text-center text-gray-600">
                    {{ t('verify.confirm_desc', { email }) }}
                </p>

                <PvMessage
                    v-if="generalError"
                    severity="error"
                    variant="simple"
                    :closable="false"
                >
                    {{ generalError }}
                </PvMessage>

                <div>
                    <PvFloatLabel>
                        <PvPassword
                            id="password"
                            v-model="password"
                            class="w-full"
                            :class="{ 'p-invalid': hasError('password') }"
                            :feedback="false"
                            toggle-mask
                            aria-required="true"
                        />
                        <label for="password">{{ t('auth.password') }}</label>
                    </PvFloatLabel>
                    <small
                        v-if="hasError('password')"
                        class="text-red-500"
                    >{{ fieldError('password') }}</small>
                </div>

                <PvButton
                    type="submit"
                    :loading="loading"
                    class="w-full"
                    :label="loading ? t('verify.confirming') : t('verify.confirm_button')"
                />
            </form>
        </template>
    </main>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { useFormErrors } from '@/composables/useFormErrors'

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()
const { generalError, setErrors, clearErrors, fieldError, hasError } = useFormErrors()

const email = ref('')
const password = ref('')
const loading = ref(false)

async function handleSubmit() {
    clearErrors()
    loading.value = true

    try {
        const result = await auth.login(email.value, password.value)

        if ('totp_status' in result) {
            const routeName =
                result.totp_status === 'setup_required'
                    ? 'totp-setup'
                    : 'totp-verify'

            router.push({
                name: routeName,
                query: { temp_token: result.temp_token, email: email.value },
            })
            return
        }

        router.push({
            name: 'verify-email',
            query: { email: result.email },
        })
    } catch (err: unknown) {
        setErrors(err)
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <main class="mx-auto flex min-h-screen max-w-md items-center px-4">
        <form
            class="flex w-full flex-col gap-5"
            @submit.prevent="handleSubmit"
        >
            <h1 class="text-center text-2xl font-bold">{{ t('auth.login_title') }}</h1>

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
                    <PvInputText
                        id="email"
                        v-model="email"
                        class="w-full"
                        :class="{ 'p-invalid': hasError('email') }"
                        aria-required="true"
                    />
                    <label for="email">{{ t('auth.email') }}</label>
                </PvFloatLabel>
                <small
                    v-if="hasError('email')"
                    class="text-red-500"
                >{{ fieldError('email') }}</small>
            </div>

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
                :label="loading ? t('auth.logging_in') : t('auth.login')"
            />

            <p class="text-center text-sm text-gray-600">
                {{ t('auth.register_link') }}
                <router-link
                    :to="{ name: 'register' }"
                    class="text-blue-600 hover:underline"
                >{{ t('auth.register') }}</router-link>
            </p>
        </form>
    </main>
</template>

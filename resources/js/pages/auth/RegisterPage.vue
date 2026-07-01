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

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)

async function handleSubmit() {
    clearErrors()
    loading.value = true

    try {
        const { email: registeredEmail } = await auth.register(name.value, email.value, password.value, passwordConfirmation.value)
        router.push({ name: 'verify-email', query: { email: registeredEmail } })
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
        :label="t('auth.register_title')"
    >
        <form @submit.prevent="handleSubmit" class="space-y-7 text-left">
            <div>
                <PvFloatLabel>
                    <PvInputText id="name" v-model="name" class="w-full" :class="{ 'p-invalid': hasError('name') }" />
                    <label for="name">{{ t('auth.name') }}</label>
                </PvFloatLabel>
                <small v-if="hasError('name')" class="text-[#dc2626]">{{ fieldError('name') }}</small>
            </div>

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

            <div>
                <PvFloatLabel>
                    <PvPassword input-id="password-confirmation" v-model="passwordConfirmation" class="w-full" :class="{ 'p-invalid': hasError('password') }" :feedback="false" toggle-mask />
                    <label for="password-confirmation">{{ t('auth.password_confirm') }}</label>
                </PvFloatLabel>
                <small v-if="hasError('password')" class="text-[#dc2626]">{{ fieldError('password') }}</small>
            </div>

            <PvButton type="submit" :loading="loading" class="w-full" :label="loading ? t('auth.registering') : t('auth.register')" />
        </form>

        <template #footer>
            {{ t('auth.login_link') }}
            <router-link :to="{ name: 'login' }" class="font-semibold text-[#111] underline hover:text-[#333]">{{ t('auth.login') }}</router-link>
        </template>
    </MinimalismCard>
</template>

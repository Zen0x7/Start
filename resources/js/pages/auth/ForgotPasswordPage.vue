<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '@/services/api'
import { Icons } from '@/components/icons'

const { t } = useI18n()

const email = ref('')
const sent = ref(false)
const loading = ref(false)
const error = ref('')

async function handleSubmit() {
    error.value = ''
    loading.value = true

    try {
        await api.post('/auth/password/email', { email: email.value })
        sent.value = true
    } catch (err: unknown) {
        const e = err as Error & { data?: { message?: string } }
        error.value = e.data?.message || e.message || t('errors.generic')
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <template v-if="sent">
        <MinimalismCard
            :icon="Icons.mail"
            :label="t('password.sent_title')"
            :message="t('password.sent_desc', { email })"
        />
    </template>

    <template v-else>
        <MinimalismCard
            :icon="Icons.lock"
            :label="t('password.forgot_title')"
            :message="t('password.forgot_desc')"
        >
            <p v-if="error" class="mb-2 text-sm text-[#dc2626]">
                {{ error }}
            </p>

            <form class="space-y-4" @submit.prevent="handleSubmit">
                <PvFloatLabel>
                    <PvInputText id="fp-email" v-model="email" type="email" class="w-full" />
                    <label for="fp-email">{{ t('auth.email') }}</label>
                </PvFloatLabel>

                <PvButton
                    type="submit"
                    :loading="loading"
                    class="w-full"
                    :label="loading ? t('password.sending') : t('password.send')"
                />
            </form>

            <template #footer>
                <router-link
                    :to="{ name: 'login' }"
                    class="font-semibold text-[#111] underline hover:text-[#333]"
                >
                    {{ t('auth.login') }}
                </router-link>
            </template>
        </MinimalismCard>
    </template>
</template>

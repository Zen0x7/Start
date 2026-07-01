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

const tempToken = ref((route.query.temp_token as string) || '')
const email = ref((route.query.email as string) || '')
const step = ref<'init' | 'qr' | 'confirm'>('init')
const qrDataUrl = ref('')
const totpCode = ref('')
const deviceLabel = ref('')
const error = ref('')
const loading = ref(false)
const auth = useAuthStore()

const certId = ref<number | null>(null)
const secretBase32 = ref('')

function randomBase32(): string {
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'
    const bytes = new Uint8Array(20)
    crypto.getRandomValues(bytes)
    let bits = 0
    let bitCount = 0
    let result = ''
    for (const b of bytes) {
        bits = (bits << 8) | b
        bitCount += 8
        while (bitCount >= 5) {
            bitCount -= 5
            result += alphabet[(bits >> bitCount) & 0x1f]
        }
    }
    if (bitCount > 0) {
        result += alphabet[(bits << (5 - bitCount)) & 0x1f]
    }
    return result
}

async function generateQR(text: string) {
    const { toDataURL } = await import('qrcode')
    return toDataURL(text)
}

onMounted(async () => {
    if (!crypto.subtle) {
        error.value = t('errors.generic')
        loading.value = false
        return
    }

    loading.value = true
    try {
        const body: Record<string, string> = {}
        if (tempToken.value) body.temp_token = tempToken.value

        const res = await api.post<{ cert_id: number; public_key: string }>(
            '/auth/totp/setup/init',
            body,
        )

        const resData = res as unknown as {
            cert_id: number
            public_key_jwk: Record<string, unknown>
        }
        certId.value = resData.cert_id
        const jwk = resData.public_key_jwk

        const secret = randomBase32()
        secretBase32.value = secret

        const key = await crypto.subtle.importKey(
            'jwk',
            jwk,
            { name: 'RSA-OAEP', hash: { name: 'SHA-256' } },
            false,
            ['encrypt'],
        )

        const encoder = new TextEncoder()
        const encrypted = await crypto.subtle.encrypt(
            { name: 'RSA-OAEP' },
            key,
            encoder.encode(secret),
        )

        const encryptedBase64 = btoa(String.fromCharCode(...new Uint8Array(encrypted)))

        const label = email.value || 'user'
        const provisioningUri = `otpauth://totp/Laravel:${encodeURIComponent(label)}?secret=${secret}&issuer=Laravel`

        qrDataUrl.value = await generateQR(provisioningUri)

        ;(window as unknown as Record<string, unknown>).__totp_encrypted = encryptedBase64

        step.value = 'qr'
    } catch (err: unknown) {
        const e = err as Error & { data?: { message?: string } }
        const msg = e.data?.message || e.message || t('errors.generic')
        error.value = msg
    } finally {
        loading.value = false
    }
})

async function confirmSetup() {
    error.value = ''
    loading.value = true

    try {
        const encryptedBase64 = (window as unknown as Record<string, unknown>)
            .__totp_encrypted as string

        const res = await api.post<{
            token: string
            user: { id: number; name: string; email: string }
        }>('/auth/totp/setup/confirm', {
            temp_token: tempToken.value,
            cert_id: certId.value,
            encrypted_secret: encryptedBase64,
            totp_code: totpCode.value,
            label: deviceLabel.value || null,
        })
        const data = res as unknown as {
            token: string
            user: { id: number; name: string; email: string }
        }
        auth.setSession(data.token, data.user)

        step.value = 'confirm'
        setTimeout(() => router.push({ name: 'dashboard' }), 2000)
    } catch (err: unknown) {
        const e = err as Error & { data?: { message?: string } }
        error.value = e.data?.message || e.message || t('errors.generic')
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <template v-if="error && step === 'init'">
        <MinimalismCard :icon="Icons.lock" :label="t('totp.setup_title')" :message="error" />
    </template>

    <template v-else-if="step === 'qr'">
        <MinimalismCard
            :icon="Icons.qr"
            :label="t('totp.setup_title')"
            :message="t('totp.setup_desc')"
        >
            <img
                v-if="qrDataUrl"
                :src="qrDataUrl"
                alt="TOTP QR"
                class="mx-auto mb-4 w-48 border-2 border-[#111]"
            />

            <div class="mb-6 border-2 border-[#ddd] bg-[#f5f5f0] p-3 text-xs text-[#555] break-all">
                <p class="font-mono text-[#111]">
                    {{ secretBase32 }}
                </p>
            </div>

            <p v-if="error" class="mb-2 text-sm text-[#dc2626]">
                {{ error }}
            </p>

            <div class="mb-6">
                <PvFloatLabel class="text-left">
                    <PvInputText id="device-label" v-model="deviceLabel" class="w-full" />
                    <label for="device-label">{{ t('settings.device_name') }}</label>
                </PvFloatLabel>
            </div>

            <div class="mb-6 flex justify-center">
                <PvInputOtp
                    v-model="totpCode"
                    :length="6"
                    integer-only
                    :aria-label="t('totp.verify_code')"
                />
            </div>

            <PvButton
                :loading="loading"
                :disabled="loading || totpCode.length !== 6"
                class="w-full"
                :label="loading ? t('totp.verifying') : t('totp.confirm')"
                @click="confirmSetup"
            />
        </MinimalismCard>
    </template>

    <template v-else-if="step === 'confirm'">
        <MinimalismCard :icon="Icons.check" :label="t('totp.configured')">
            <p class="text-sm text-[#555]">
                {{ t('totp.redirecting') }}
            </p>
        </MinimalismCard>
    </template>
</template>

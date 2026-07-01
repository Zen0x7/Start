<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { api } from '@/services/api'

const route = useRoute()
const router = useRouter()

const tempToken = ref((route.query.temp_token as string) || '')
const email = ref((route.query.email as string) || '')
const step = ref<'init' | 'qr' | 'confirm'>('init')
const qrDataUrl = ref('')
const totpCode = ref('')
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
    if (!tempToken.value) {
        error.value = 'Token de sesión no encontrado.'
        return
    }

    if (!crypto.subtle) {
        error.value = 'El navegador no soporta Web Crypto API (requiere HTTPS o localhost).'
        loading.value = false
        return
    }

    loading.value = true
    try {
        const res = await api.post<{ cert_id: number; public_key: string }>(
            '/auth/totp/setup/init',
            { temp_token: tempToken.value },
        )

        const resData = res as unknown as { cert_id: number; public_key_jwk: JsonWebKey }
        certId.value = resData.cert_id
        const jwk = resData.public_key_jwk

        const secret = randomBase32()
        secretBase32.value = secret

        console.log('[TOTP] Importing JWK key...', JSON.stringify(jwk))
        const key = await crypto.subtle.importKey(
            'jwk',
            jwk,
            { name: 'RSA-OAEP', hash: { name: 'SHA-256' } },
            false,
            ['encrypt'],
        )
        console.log('[TOTP] Key imported successfully')

        const encoder = new TextEncoder()
        console.log('[TOTP] Encrypting secret...')
        const encrypted = await crypto.subtle.encrypt(
            { name: 'RSA-OAEP' },
            key,
            encoder.encode(secret),
        )
        console.log('[TOTP] Secret encrypted, size:', encrypted.byteLength)

        const encryptedBase64 = btoa(
            String.fromCharCode(...new Uint8Array(encrypted)),
        )

        const label = email.value || 'user'
        const provisioningUri = `otpauth://totp/Laravel:${encodeURIComponent(label)}?secret=${secret}&issuer=Laravel`

        qrDataUrl.value = await generateQR(provisioningUri)

        ;(window as unknown as Record<string, unknown>).__totp_encrypted = encryptedBase64

        step.value = 'qr'
    } catch (err: unknown) {
        const e = err as Error & { data?: { message?: string } }
        const msg = e.data?.message || e.message || 'Error desconocido'
        console.error('[TOTP Setup]', msg, e)
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

        const res = await api.post<{ token: string; user: { id: number; name: string; email: string } }>(
            '/auth/totp/setup/confirm',
            {
                temp_token: tempToken.value,
                cert_id: certId.value,
                encrypted_secret: encryptedBase64,
                totp_code: totpCode.value,
            },
        )
        const data = res as unknown as { token: string; user: { id: number; name: string; email: string } }
        auth.setSession(data.token, data.user)

        step.value = 'confirm'
        setTimeout(() => router.push({ name: 'dashboard' }), 2000)
    } catch (err: unknown) {
        const e = err as Error & { data?: { message?: string } }
        error.value = e.data?.message || e.message || 'Error al confirmar TOTP.'
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <main class="mx-auto flex min-h-screen max-w-md items-center px-4">
        <template v-if="error && step === 'init'">
            <div class="w-full text-center space-y-4">
                <p class="rounded-lg bg-red-50 p-4 text-red-600">{{ error }}</p>
            </div>
        </template>

        <template v-else-if="step === 'qr'">
            <div class="w-full space-y-6 text-center">
                <h1 class="text-2xl font-bold">Configurar TOTP</h1>

                <p class="text-gray-600">
                    Escanea este código QR con tu aplicación
                    autenticadora (Google Authenticator, Authy, etc.).
                </p>

                <img
                    v-if="qrDataUrl"
                    :src="qrDataUrl"
                    alt="TOTP QR Code"
                    class="mx-auto w-48 h-48"
                />

                <div class="rounded-lg bg-gray-50 p-3 text-xs text-gray-500 break-all">
                    <p class="font-mono">{{ secretBase32 }}</p>
                </div>

                <p
                    v-if="error"
                    class="rounded-lg bg-red-50 p-3 text-sm text-red-600"
                >
                    {{ error }}
                </p>

                <div>
                    <label
                        for="totp-code"
                        class="mb-1 block text-sm font-medium text-gray-700"
                    >Código de verificación</label>
                    <input
                        id="totp-code"
                        v-model="totpCode"
                        type="text"
                        maxlength="6"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 text-center text-2xl tracking-widest focus:border-blue-500 focus:outline-none"
                        placeholder="000000"
                        @keyup.enter="confirmSetup"
                    />
                </div>

                <button
                    :disabled="loading || totpCode.length !== 6"
                    class="w-full rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
                    @click="confirmSetup"
                >
                    {{ loading ? 'Verificando...' : 'Confirmar' }}
                </button>
            </div>
        </template>

        <template v-else-if="step === 'confirm'">
            <div class="w-full space-y-4 text-center">
                <div class="rounded-full bg-green-100 p-4 mx-auto w-16 h-16 flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">
                    TOTP Configurado
                </h1>
                <p class="text-gray-600">Redirigiendo...</p>
            </div>
        </template>
    </main>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { api } from '@/services/api'
import { useToast } from 'primevue/usetoast'
import { Icons } from '@/components/icons'
import AppLogo from '@/components/AppLogo.vue'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const toast = useToast()

const section = ref<'profile' | 'totp' | 'danger' | 'activity'>('profile')

const name = ref('')
const email = ref('')
const avatar = ref('')
const saving = ref(false)

const totpDevices = ref<{ id: number; label: string; created_at: string; last_used_at: string | null }[]>([])
const deleteCode = ref('')
const deviceCode = ref('')
const deviceId = ref<number | null>(null)
const showDeleteConfirm = ref(false)

interface ActivityItem {
    type: 'login' | 'totp'
    successful: boolean
    email?: string
    action?: string
    device?: string
    ip_address: string | null
    user_agent: string | null
    created_at: string
}
const activity = ref<ActivityItem[]>([])

function formatDate(dateStr: string): string {
    const d = new Date(dateStr)
    return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

onMounted(async () => {
    try {
        const [profileRes, activityRes] = await Promise.all([
            api.get<{ user: { name: string; email: string; avatar: string }; totp_devices: typeof totpDevices.value }>('/auth/profile'),
            api.get<{ activity: ActivityItem[] }>('/auth/activity'),
        ])
        const data = profileRes as unknown as { user: { name: string; email: string; avatar: string }; totp_devices: typeof totpDevices.value }
        const act = activityRes as unknown as { activity: ActivityItem[] }
        name.value = data.user.name
        email.value = data.user.email
        avatar.value = data.user.avatar
        totpDevices.value = data.totp_devices
        activity.value = act.activity
    } catch {
        toast.add({ severity: 'error', summary: t('errors.generic'), life: 5000 })
    }
})

async function saveProfile() {
    saving.value = true
    try {
        const res = await api.put<{ email_changed: boolean; user: { name: string; email: string; avatar: string } }>('/auth/profile', { name: name.value, email: email.value })
        const data = res as unknown as { email_changed: boolean; user: { name: string; email: string; avatar: string } }
        auth.setSession(auth.token!, { id: auth.currentUser!.id, name: data.user.name, email: data.user.email })
        toast.add({ severity: 'success', summary: t('auth.profile_updated'), life: 3000 })
        if (data.email_changed) {
            auth.clearSession()
            router.push({ name: 'verify-email', query: { email: data.user.email } })
        }
    } catch (err: unknown) {
        const e = err as Error & { data?: { message?: string } }
        toast.add({ severity: 'error', summary: e.data?.message || t('errors.generic'), life: 5000 })
    } finally {
        saving.value = false
    }
}

async function handlePhoto(e: Event) {
    const input = e.target as HTMLInputElement
    if (!input.files?.length) return

    const form = new FormData()
    form.append('photo', input.files[0])

    try {
        const res = await api.post<{ avatar: string }>('/auth/profile/photo', form as unknown as Record<string, unknown>)
        const data = res as unknown as { avatar: string }
        avatar.value = data.avatar
        toast.add({ severity: 'success', summary: t('auth.photo_updated'), life: 3000 })
    } catch {
        toast.add({ severity: 'error', summary: t('errors.generic'), life: 5000 })
    }
}

async function removeDevice(device: { id: number }) {
    deviceId.value = device.id
    try {
        const res = await api.post('/auth/totp/devices/delete', { device_id: device.id, totp_code: deviceCode.value })
        const data = res as { message?: string }
        totpDevices.value = totpDevices.value.filter(d => d.id !== device.id)
        deviceCode.value = ''
        deviceId.value = null
        toast.add({ severity: 'success', summary: (data as unknown as { message: string }).message || t('totp.device_removed'), life: 3000 })
    } catch {
        toast.add({ severity: 'error', summary: t('errors.generic'), life: 5000 })
    }
}

async function deleteAccount() {
    try {
        await api.post('/auth/profile/delete', { totp_code: deleteCode.value })
        auth.clearSession()
        router.push({ name: 'login' })
    } catch {
        toast.add({ severity: 'error', summary: t('errors.generic'), life: 5000 })
    }
}

function handleLogout() {
    auth.logout()
    router.push({ name: 'login' })
}
</script>

<template>
    <div class="min-h-screen bg-[#fcfcf8]">
        <header class="border-b-2 border-[#111] bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <AppLogo class="h-8 w-8 text-[#111]" />
                    <h1 class="text-lg font-bold text-[#111]">{{ t('settings.title') }}</h1>
                </div>
                <div class="flex items-center gap-4">
                    <LanguageSwitcher />
                    <span class="text-sm text-[#555]">{{ auth.currentUser?.name }}</span>
                    <router-link :to="{ name: 'dashboard' }" class="text-sm font-semibold text-[#111] underline hover:text-[#333]">{{ t('settings.dashboard') }}</router-link>
                    <PvButton severity="secondary" @click="handleLogout">{{ t('auth.logout') }}</PvButton>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8">
            <div class="mb-6 flex gap-2 border-b-2 border-[#ddd] pb-4">
                <button v-for="sec in ['profile', 'totp', 'activity', 'danger']" :key="sec"
                    class="px-4 py-2 text-sm font-semibold transition-colors focus:outline-none"
                    :class="section === sec ? 'bg-[#111] text-white' : 'text-[#555] hover:bg-[#f5f5f0]'"
                    @click="section = sec as typeof section"
                >
                    {{ t('settings.' + (sec === 'profile' ? 'profile' : sec === 'totp' ? 'totp_devices' : sec === 'activity' ? 'activity' : 'danger')) }}
                </button>
            </div>

            <!-- Profile Section -->
            <div v-if="section === 'profile'" class="border-2 border-[#111] bg-white p-8" style="box-shadow: 10px 10px 0 rgba(0,0,0,0.06)">
                <h2 class="mb-1 text-[0.6875rem] font-semibold uppercase tracking-[0.15em] text-[#999]">{{ t('settings.profile') }}</h2>

                <div class="mb-10 flex items-center gap-4">
                    <img :src="avatar" alt="Avatar" class="h-16 w-16 border-2 border-[#111] object-cover" />
                    <label class="cursor-pointer border-2 border-[#111] px-4 py-2 text-sm font-semibold text-[#555] hover:bg-[#f5f5f0]">
                        {{ t('settings.upload_photo') }}
                        <input type="file" accept="image/*" class="hidden" @change="handlePhoto" />
                    </label>
                </div>

                <div class="space-y-8">
                    <div>
                        <PvFloatLabel>
                            <PvInputText id="s-name" v-model="name" class="w-full" />
                            <label for="s-name">{{ t('auth.name') }}</label>
                        </PvFloatLabel>
                    </div>
                    <div>
                        <PvFloatLabel>
                            <PvInputText id="s-email" v-model="email" type="email" class="w-full" />
                            <label for="s-email">{{ t('auth.email') }}</label>
                        </PvFloatLabel>
                    </div>
                    <PvButton :loading="saving" class="w-full" @click="saveProfile">{{ saving ? t('settings.saving') : t('settings.save') }}</PvButton>
                </div>
            </div>

            <!-- TOTP Devices Section -->
            <div v-if="section === 'totp'" class="border-2 border-[#111] bg-white p-8" style="box-shadow: 10px 10px 0 rgba(0,0,0,0.06)">
                <h2 class="mb-1 text-[0.6875rem] font-semibold uppercase tracking-[0.15em] text-[#999]">{{ t('settings.totp_devices') }}</h2>

                <div v-if="totpDevices.length === 0" class="py-8 text-center text-sm text-[#555]">
                    {{ t('settings.no_devices') }}
                </div>

                <div v-for="device in totpDevices" :key="device.id" class="mb-3 flex items-center justify-between border-2 border-[#ddd] p-4">
                    <div>
                        <p class="font-semibold text-[#111]">{{ device.label }}</p>
                        <p class="text-xs text-[#999]">{{ t('settings.added') }} {{ new Date(device.created_at).toLocaleDateString() }}</p>
                    </div>
                    <div v-if="deviceId !== device.id" class="flex items-center gap-2">
                        <PvInputOtp v-model="deviceCode" :length="6" integer-only class="mr-2" />
                        <PvButton severity="secondary" @click="removeDevice(device)">{{ t('settings.remove') }}</PvButton>
                    </div>
                </div>

                <router-link :to="{ name: 'totp-setup' }" class="mt-4 inline-block border-2 border-[#111] px-4 py-2 text-sm font-semibold text-[#555] hover:bg-[#f5f5f0]">
                    {{ t('settings.add_device') }}
                </router-link>
            </div>

            <!-- Danger Zone -->
            <div v-if="section === 'danger'" class="border-2 border-[#dc2626] bg-white p-8" style="box-shadow: 10px 10px 0 rgba(0,0,0,0.06)">
                <h2 class="mb-1 text-[0.6875rem] font-semibold uppercase tracking-[0.15em] text-[#dc2626]">{{ t('settings.danger') }}</h2>
                <p class="mb-4 text-sm text-[#555]">{{ t('auth.delete_confirm') }}</p>

                <div v-if="!showDeleteConfirm">
                    <PvButton severity="secondary" class="border-2 border-[#dc2626] text-[#dc2626]" @click="showDeleteConfirm = true">{{ t('settings.delete_account') }}</PvButton>
                </div>

                <div v-else class="space-y-4">
                    <PvInputOtp v-model="deleteCode" :length="6" integer-only />
                    <div class="flex gap-3">
                        <PvButton severity="secondary" @click="showDeleteConfirm = false">{{ t('settings.cancel') }}</PvButton>
                        <PvButton class="border-2 border-[#dc2626] bg-[#dc2626] text-white" @click="deleteAccount">{{ t('settings.confirm_deletion') }}</PvButton>
                    </div>
                </div>
            </div>

            <!-- Activity -->
            <div v-if="section === 'activity'" class="border-2 border-[#111] bg-white p-8" style="box-shadow: 10px 10px 0 rgba(0,0,0,0.06)">
                <h2 class="mb-1 text-[0.6875rem] font-semibold uppercase tracking-[0.15em] text-[#999]">{{ t('activity.recent') }}</h2>

                <div v-if="activity.length === 0" class="py-8 text-center text-sm text-[#555]">{{ t('settings.no_activity') }}</div>

                <div v-for="(item, i) in activity" :key="i" class="flex items-center gap-3 border-b border-[#eee] py-2.5 text-sm">
                    <span class="flex h-6 w-6 items-center justify-center rounded text-xs font-bold" :class="item.successful ? 'bg-[#f5f5f0] text-green-700' : 'bg-red-50 text-red-600'">
                        {{ item.successful ? '✓' : '✗' }}
                    </span>
                    <span class="w-12 text-xs font-semibold text-[#555]">{{ item.type === 'login' ? t('activity.login') : t('activity.totp') }}</span>
                    <span class="w-6 text-center text-[#999]">·</span>
                    <span class="flex-1 text-[#111]">{{ item.device || item.ip_address || '-' }}</span>
                    <span class="text-xs text-[#999]">{{ formatDate(item.created_at) }}</span>
                </div>
            </div>
        </main>
    </div>
</template>

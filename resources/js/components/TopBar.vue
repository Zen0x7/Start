<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useStorage } from '@vueuse/core'
import { useAuthStore } from '@/stores/auth'
import AppLogo from '@/components/AppLogo.vue'
import { api } from '@/services/api'
import { Settings, Globe, LogOut, ChevronDown, User } from '@lucide/vue'

const { t, locale } = useI18n({ useScope: 'global' })
const saved = useStorage('app_locale', '')
const router = useRouter()
const auth = useAuthStore()
const avatar = ref(auth.currentUser?.avatar_thumb ?? '')

onMounted(async () => {
    try {
        const res = await api.get('/auth/profile')
        const data = res as { user?: { avatar_thumb?: string } }
        if (data.user?.avatar_thumb) avatar.value = data.user.avatar_thumb
    } catch {
        // ignore
    }
})

const open = ref(false)
const langOpen = ref(false)

function toggle() {
    langOpen.value = false
    open.value = !open.value
}

function close() {
    open.value = false
    langOpen.value = false
}

function go(path: string) {
    close()
    router.push(path)
}

function handleLogout() {
    close()
    auth.logout()
    router.push({ name: 'login' })
}

async function setLang(code: string) {
    locale.value = code as 'en' | 'es'
    saved.value = code
    document.querySelector('html')?.setAttribute('lang', code)
    langOpen.value = false
    try {
        await api.put('/auth/profile', { locale: code })
    } catch {
        // ignore
    }
}

const languages = [
    { code: 'en', label: 'English' },
    { code: 'es', label: 'Español' },
]
</script>

<template>
    <header class="border-b-2 border-[#111] bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <AppLogo class="h-8 w-8 text-[#111]" />
                <router-link
                    :to="{ name: 'dashboard' }"
                    class="text-lg font-bold text-[#111] hover:text-[#333]"
                >
                    Start
                </router-link>
            </div>

            <div class="relative">
                <button
                    class="flex items-center gap-2 rounded-sm border-2 border-transparent p-1 transition-colors hover:border-[#111] focus:outline-none focus:ring-2 focus:ring-[#111]"
                    aria-haspopup="true"
                    :aria-expanded="open"
                    :aria-label="t('auth.login')"
                    @click="toggle"
                    @keydown.escape="close"
                >
                    <img
                        v-if="avatar"
                        :src="avatar"
                        alt="Avatar"
                        class="h-8 w-8 border border-[#ddd] object-cover"
                    />
                    <div
                        v-else
                        class="flex h-8 w-8 items-center justify-center border border-[#ddd] bg-[#f5f5f0] text-[#555]"
                    >
                        <User class="h-4 w-4" />
                    </div>
                    <ChevronDown
                        class="h-4 w-4 text-[#555] transition-transform"
                        :class="{ 'rotate-180': open }"
                    />
                </button>

                <transition name="fade">
                    <div
                        v-if="open"
                        class="absolute right-0 top-full z-50 mt-1 w-48 border-2 border-[#111] bg-white shadow"
                        style="box-shadow: 10px 10px 0 rgba(0, 0, 0, 0.06)"
                        role="menu"
                    >
                        <div class="border-b border-[#eee] px-4 py-3">
                            <p class="text-sm font-semibold text-[#111]">
                                {{ auth.currentUser?.name }}
                            </p>
                            <p class="text-xs text-[#555]">
                                {{ auth.currentUser?.email }}
                            </p>
                        </div>

                        <button
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-[#555] transition-colors hover:bg-[#f5f5f0] focus:outline-none"
                            role="menuitem"
                            @click="go('/settings')"
                        >
                            <Settings class="h-4 w-4" />
                            {{ t('settings.title') }}
                        </button>

                        <div class="relative">
                            <button
                                class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-[#555] transition-colors hover:bg-[#f5f5f0] focus:outline-none"
                                role="menuitem"
                                @click.stop="langOpen = !langOpen"
                            >
                                <Globe class="h-4 w-4" />
                                <span class="flex-1 text-left">{{
                                    locale === 'es' ? 'Idioma' : 'Language'
                                }}</span>
                                <ChevronDown class="h-3 w-3" :class="{ 'rotate-180': langOpen }" />
                            </button>

                            <div v-if="langOpen" class="border-t border-[#eee]">
                                <button
                                    v-for="lang in languages"
                                    :key="lang.code"
                                    class="flex w-full items-center gap-3 px-4 py-2.5 pl-10 text-sm transition-colors hover:bg-[#f5f5f0] focus:outline-none"
                                    :class="
                                        locale === lang.code
                                            ? 'font-semibold text-[#111]'
                                            : 'text-[#555]'
                                    "
                                    role="menuitem"
                                    @click="setLang(lang.code)"
                                >
                                    {{ lang.label }}
                                </button>
                            </div>
                        </div>

                        <div class="border-t border-[#eee]">
                            <button
                                class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-[#dc2626] transition-colors hover:bg-red-50 focus:outline-none"
                                role="menuitem"
                                @click="handleLogout"
                            >
                                <LogOut class="h-4 w-4" />
                                {{ t('auth.logout') }}
                            </button>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </header>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>

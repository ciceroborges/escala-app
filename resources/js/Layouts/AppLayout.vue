<script setup>
import { ref, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import FlashToast from '@/Components/FlashToast.vue';
import { useNav } from '@/composables/useNav';

const page = usePage();
const { items, isActive } = useNav();
const darkMode = ref(false);
const menuOpen = ref(false);

onMounted(() => (darkMode.value = document.documentElement.classList.contains('dark')));

const toggleDark = () => {
    darkMode.value = !darkMode.value;
    document.documentElement.classList.toggle('dark', darkMode.value);
    try { localStorage.setItem('darkMode', JSON.stringify(darkMode.value)); } catch { /* sem storage */ }
};

const logout = () => router.post(route('logout'));
const initial = (name) => (name ?? '?').trim().charAt(0).toUpperCase();
</script>

<template>
    <div class="flex min-h-dvh">
        <!-- Sidebar (desktop) -->
        <aside class="sticky top-0 hidden h-dvh w-64 shrink-0 flex-col border-r border-gray-200 bg-white lg:flex dark:border-gray-800 dark:bg-gray-800">
            <div class="flex h-16 items-center gap-2 border-b border-gray-200 px-5 dark:border-gray-700">
                <img src="/icons/icon-192.png" alt="" class="size-8 rounded-lg">
                <span class="text-lg font-bold text-brand-500">Escala</span>
            </div>

            <Link :href="route('ministries.index')" class="mx-3 mt-4 flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-left hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold">{{ page.props.ministry?.name }}</p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ page.props.ministry?.church_name || 'Trocar ministério' }}</p>
                </div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400"><path d="m7 15 5 5 5-5M7 9l5-5 5 5" /></svg>
            </Link>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Menu</p>
                <Link v-for="item in items" :key="item.route" :href="route(item.route)"
                      :class="['menu-item', isActive(item) ? 'menu-item-active' : 'menu-item-inactive']">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0" v-html="item.icon" />
                    {{ item.label }}
                </Link>
            </nav>

            <div class="flex items-center gap-3 border-t border-gray-200 p-4 dark:border-gray-700">
                <img v-if="page.props.auth.user?.avatar_url" :src="page.props.auth.user.avatar_url" alt="" class="size-8 rounded-full" referrerpolicy="no-referrer">
                <div v-else class="flex size-8 items-center justify-center rounded-full bg-brand-500 text-sm font-semibold text-white">{{ initial(page.props.auth.user?.name) }}</div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">{{ page.props.auth.user?.name }}</p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ page.props.auth.user?.email }}</p>
                </div>
                <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700" :title="darkMode ? 'Modo claro' : 'Modo escuro'" @click="toggleDark">
                    {{ darkMode ? '☀️' : '🌙' }}
                </button>
                <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700" title="Sair" @click="logout">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" /></svg>
                </button>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Header (celular) -->
            <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-gray-200 bg-white/90 px-4 backdrop-blur lg:hidden dark:border-gray-800 dark:bg-gray-900/90" style="padding-top: env(safe-area-inset-top)">
                <Link :href="route('ministries.index')" class="flex min-w-0 items-center gap-2">
                    <img src="/icons/icon-192.png" alt="" class="size-7 rounded-lg">
                    <span class="truncate text-sm font-semibold">{{ page.props.ministry?.name }}</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-gray-400"><path d="m6 9 6 6 6-6" /></svg>
                </Link>
                <button type="button" class="-mr-2 rounded-full p-1" aria-label="Conta" @click="menuOpen = !menuOpen">
                    <img v-if="page.props.auth.user?.avatar_url" :src="page.props.auth.user.avatar_url" alt="" class="size-8 rounded-full" referrerpolicy="no-referrer">
                    <div v-else class="flex size-8 items-center justify-center rounded-full bg-brand-500 text-sm font-semibold text-white">{{ initial(page.props.auth.user?.name) }}</div>
                </button>
                <div v-if="menuOpen" class="absolute right-3 top-14 w-56 rounded-xl border border-gray-200 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-800" @click="menuOpen = false">
                    <p class="truncate px-3 pb-1 pt-2 text-xs text-gray-500">{{ page.props.auth.user?.email }}</p>
                    <Link :href="route('ministries.index')" class="flex min-h-11 items-center rounded-lg px-3 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">🔁 Trocar ministério</Link>
                    <button type="button" class="flex min-h-11 w-full items-center rounded-lg px-3 text-sm hover:bg-gray-100 dark:hover:bg-gray-700" @click="toggleDark">{{ darkMode ? '☀️ Modo claro' : '🌙 Modo escuro' }}</button>
                    <button type="button" class="flex min-h-11 w-full items-center rounded-lg px-3 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700" @click="logout">Sair</button>
                </div>
            </header>

            <main class="mx-auto w-full max-w-5xl flex-1 px-4 pb-28 pt-5 sm:px-6 lg:pb-10 lg:pt-8">
                <slot />
            </main>
        </div>

        <!-- Abas (celular) -->
        <nav class="pb-safe fixed inset-x-0 bottom-0 z-30 grid border-t border-gray-200 bg-white/95 pt-1 backdrop-blur lg:hidden dark:border-gray-800 dark:bg-gray-900/95"
             :style="{ gridTemplateColumns: `repeat(${items.length}, minmax(0, 1fr))` }">
            <Link v-for="item in items" :key="item.route" :href="route(item.route)"
                  :class="['flex min-h-12 flex-col items-center justify-center gap-0.5 text-[11px] font-medium',
                      isActive(item) ? 'text-brand-500' : 'text-gray-500 dark:text-gray-400']">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" v-html="item.icon" />
                {{ item.label }}
            </Link>
        </nav>

        <FlashToast />
    </div>
</template>

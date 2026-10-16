<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import UiButton from '@/Components/UiButton.vue';

defineProps({
    devLogin: Boolean,
    googleConfigured: Boolean,
});

const dev = useForm({ email: '', name: '' });
const submitDev = () => dev.post(route('dev.login'));
</script>

<template>
    <Head title="Entrar" />

    <div class="card p-6 sm:p-8">
        <h1 class="text-xl font-bold">Escala do ministério, sem planilha</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Veja quando você está escalado, o repertório de cada culto e avise quando não pode — tudo pelo celular.
        </p>

        <a :href="route('auth.google')"
           :class="['mt-6 flex min-h-12 w-full items-center justify-center gap-3 rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100',
               !googleConfigured && 'pointer-events-none opacity-50']">
            <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>
            Entrar com Google
        </a>
        <p v-if="!googleConfigured" class="hint text-center">Login com Google ainda não configurado (GOOGLE_CLIENT_ID).</p>

        <p class="mt-6 text-center text-xs text-gray-500 dark:text-gray-400">
            Use o mesmo e-mail que o líder do seu ministério cadastrou.
        </p>
    </div>

    <form v-if="devLogin" class="card mt-4 space-y-3 border-dashed p-5" @submit.prevent="submitDev">
        <p class="text-xs font-semibold uppercase tracking-wider text-amber-600">Só em desenvolvimento</p>
        <input v-model="dev.email" type="email" class="input" placeholder="email@exemplo.com" required>
        <input v-model="dev.name" type="text" class="input" placeholder="Nome (opcional)">
        <p v-if="dev.errors.email" class="error">{{ dev.errors.email }}</p>
        <UiButton type="submit" variant="secondary" block :loading="dev.processing">Entrar sem Google</UiButton>
    </form>
</template>

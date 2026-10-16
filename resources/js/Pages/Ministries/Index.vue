<script setup>
import { ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import UiButton from '@/Components/UiButton.vue';
import UiField from '@/Components/UiField.vue';

const props = defineProps({ ministries: Array });
const page = usePage();
const creating = ref(props.ministries.length === 0);
const form = useForm({ name: '', church_name: '' });

const enter = (id) => router.post(route('ministries.switch', id));
const create = () => form.post(route('ministries.store'));
</script>

<template>
    <Head title="Ministérios" />

    <div class="space-y-4">
        <div>
            <h1 class="text-xl font-bold">Olá, {{ page.props.auth.user?.name?.split(' ')[0] }}! 👋</h1>
            <p v-if="ministries.length" class="mt-1 text-sm text-gray-500 dark:text-gray-400">Escolha o ministério que você quer abrir.</p>
            <p v-else class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Você ainda não está em nenhum ministério. Se o seu líder já te cadastrou, confira se entrou com o mesmo e-mail.
                Se você é o líder, crie o ministério abaixo.
            </p>
        </div>

        <button v-for="m in ministries" :key="m.id" type="button"
                class="card flex min-h-16 w-full items-center gap-3 p-4 text-left hover:border-brand-300 dark:hover:border-brand-700"
                @click="enter(m.id)">
            <div class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-lg dark:bg-brand-950">🎶</div>
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold">{{ m.name }}</p>
                <p v-if="m.church_name" class="truncate text-sm text-gray-500 dark:text-gray-400">{{ m.church_name }}</p>
            </div>
            <span v-if="m.current" class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">atual</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400"><path d="m9 18 6-6-6-6" /></svg>
        </button>

        <UiButton v-if="!creating" variant="secondary" block @click="creating = true">+ Criar um novo ministério</UiButton>

        <form v-else class="card space-y-4 p-5" @submit.prevent="create">
            <h2 class="font-semibold">Criar ministério</h2>
            <UiField label="Nome do ministério" :error="form.errors.name" id="name">
                <input id="name" v-model="form.name" class="input" placeholder="Ministério de Louvor" required autofocus>
            </UiField>
            <UiField label="Igreja (opcional)" :error="form.errors.church_name" id="church">
                <input id="church" v-model="form.church_name" class="input" placeholder="Igreja Batista Central">
            </UiField>
            <p class="hint">Você será o administrador. As funções (vocal, teclado, bateria…) já vêm prontas — dá para ajustar depois.</p>
            <UiButton type="submit" block :loading="form.processing">Criar e começar</UiButton>
        </form>

        <button type="button" class="w-full py-3 text-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400" @click="router.post(route('logout'))">Sair</button>
    </div>
</template>

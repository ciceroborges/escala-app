<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import UiButton from '@/Components/UiButton.vue';
import HelpTip from '@/Components/HelpTip.vue';

const props = defineProps({
    schedule: Object,
    scheduleText: String,
    repertoireText: String,
});

const texts = ref({ schedule: props.scheduleText, repertoire: props.repertoireText });
const copied = ref(null);

const copy = async (key) => {
    try {
        await navigator.clipboard.writeText(texts.value[key]);
        copied.value = key;
        setTimeout(() => (copied.value = null), 2500);
    } catch {
        alert('Não consegui copiar automaticamente. Selecione o texto e copie.');
    }
};
const share = async (key) => {
    const text = texts.value[key];
    if (navigator.share) {
        try { await navigator.share({ text }); return; } catch { /* cancelado */ }
    }
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank', 'noopener');
};
</script>

<template>
    <Head :title="`Mensagens · ${schedule.label}`" />

    <Link :href="route('schedules.show', schedule.id)" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">← {{ schedule.label }}</Link>
    <h1 class="mt-1 text-xl font-bold sm:text-2xl">Mensagens para o grupo</h1>
    <p class="mb-5 mt-1 text-sm text-gray-500 dark:text-gray-400">Prontas no formato do WhatsApp. Dá para ajustar o texto antes de mandar.</p>

    <HelpTip id="messages-intro">
        Toque em <strong>Enviar no WhatsApp</strong> e escolha o grupo do ministério. No computador, use <strong>Copiar</strong> e cole no WhatsApp Web.
    </HelpTip>

    <div class="grid gap-5 lg:grid-cols-2">
        <section v-for="item in [{ key: 'schedule', title: '📅 Escala' }, { key: 'repertoire', title: '🎶 Repertório' }]" :key="item.key" class="card p-4">
            <h2 class="mb-3 font-semibold">{{ item.title }}</h2>
            <textarea v-model="texts[item.key]" rows="16" class="input py-3 font-mono text-sm leading-relaxed"></textarea>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                <UiButton variant="success" @click="share(item.key)">Enviar no WhatsApp</UiButton>
                <UiButton variant="secondary" @click="copy(item.key)">{{ copied === item.key ? '✓ Copiado!' : 'Copiar' }}</UiButton>
            </div>
        </section>
    </div>
</template>

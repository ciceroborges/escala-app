<script setup>
import { ref } from 'vue';

// Dica que explica a tela em uma frase; o usuário pode fechar e ela não volta.
const props = defineProps({ id: { type: String, required: true } });
const key = `tip:${props.id}`;
const read = () => { try { return localStorage.getItem(key) === '1'; } catch { return false; } };
const hidden = ref(read());
const close = () => {
    hidden.value = true;
    try { localStorage.setItem(key, '1'); } catch { /* sem storage: só some nesta visita */ }
};
</script>

<template>
    <div v-if="!hidden" class="mb-5 flex gap-3 rounded-xl border border-brand-200 bg-brand-50 p-4 text-sm text-brand-900 dark:border-brand-800 dark:bg-brand-950/60 dark:text-brand-100">
        <span class="text-lg leading-none">💡</span>
        <div class="flex-1"><slot /></div>
        <button type="button" class="-m-1 h-8 shrink-0 rounded-md px-2 text-xs font-semibold text-brand-600 hover:bg-brand-100 dark:text-brand-300 dark:hover:bg-brand-900" @click="close">Entendi</button>
    </div>
</template>

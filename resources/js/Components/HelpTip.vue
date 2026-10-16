<script setup>
import { ref } from 'vue';

// Dica fixa da tela: nunca some. "Entendi" só recolhe; um toque reabre.
const props = defineProps({ id: { type: String, required: true } });
const key = `tip-collapsed:${props.id}`;
const read = () => { try { return localStorage.getItem(key) === '1'; } catch { return false; } };
const collapsed = ref(read());

const setCollapsed = (value) => {
    collapsed.value = value;
    try { localStorage.setItem(key, value ? '1' : '0'); } catch { /* sem storage: vale só nesta visita */ }
};
</script>

<template>
    <button v-if="collapsed" type="button"
            class="mb-5 inline-flex min-h-9 items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-3 text-sm font-medium text-brand-700 hover:bg-brand-100 dark:border-brand-800 dark:bg-brand-950/60 dark:text-brand-200"
            @click="setCollapsed(false)">
        💡 Ver dica
    </button>

    <div v-else class="mb-5 flex gap-3 rounded-xl border border-brand-200 bg-brand-50 p-4 text-sm text-brand-900 dark:border-brand-800 dark:bg-brand-950/60 dark:text-brand-100">
        <span class="text-lg leading-none">💡</span>
        <div class="flex-1"><slot /></div>
        <button type="button" class="-m-1 h-8 shrink-0 rounded-md px-2 text-xs font-semibold text-brand-600 hover:bg-brand-100 dark:text-brand-300 dark:hover:bg-brand-900" @click="setCollapsed(true)">Entendi</button>
    </div>
</template>

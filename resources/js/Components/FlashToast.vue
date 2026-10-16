<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const message = ref(null);
const kind = ref('success');
let timer;

watch(() => page.props.flash, (flash) => {
    const text = flash?.error || flash?.success;
    if (!text) return;
    kind.value = flash.error ? 'error' : 'success';
    message.value = text;
    clearTimeout(timer);
    timer = setTimeout(() => (message.value = null), 4500);
}, { immediate: true, deep: true });
</script>

<template>
    <Transition enter-from-class="opacity-0 -translate-y-2" leave-to-class="opacity-0" enter-active-class="transition" leave-active-class="transition">
        <div v-if="message" class="fixed inset-x-4 top-4 z-60 mx-auto max-w-md sm:top-6">
            <div :class="['flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-lg',
                kind === 'error' ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200'
                    : 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200']">
                <span>{{ kind === 'error' ? '⚠️' : '✅' }}</span>
                <p class="flex-1">{{ message }}</p>
                <button type="button" class="opacity-60 hover:opacity-100" aria-label="Fechar" @click="message = null">✕</button>
            </div>
        </div>
    </Transition>
</template>

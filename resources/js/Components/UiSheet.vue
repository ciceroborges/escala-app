<script setup>
import { watch, onBeforeUnmount } from 'vue';

// Bottom sheet no celular, modal centralizado no desktop.
const props = defineProps({
    open: Boolean,
    title: String,
    subtitle: String,
});
const emit = defineEmits(['close']);

const onKey = (e) => e.key === 'Escape' && emit('close');
watch(() => props.open, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
    open ? window.addEventListener('keydown', onKey) : window.removeEventListener('keydown', onKey);
});
onBeforeUnmount(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', onKey);
});
</script>

<template>
    <Teleport to="body">
        <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" enter-active-class="transition" leave-active-class="transition">
            <div v-if="open" class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 sm:items-center sm:p-4" @click.self="emit('close')">
                <div class="flex max-h-[92vh] w-full flex-col rounded-t-2xl bg-white shadow-xl sm:max-w-lg sm:rounded-2xl dark:bg-gray-800">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <div>
                            <h2 class="text-base font-semibold">{{ title }}</h2>
                            <p v-if="subtitle" class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ subtitle }}</p>
                        </div>
                        <button type="button" class="-m-2 rounded-lg p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="Fechar" @click="emit('close')">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="overflow-y-auto px-5 py-4">
                        <slot />
                    </div>
                    <div v-if="$slots.footer" class="pb-safe flex flex-col-reverse gap-2 border-t border-gray-200 px-5 pt-3 sm:flex-row sm:justify-end dark:border-gray-700">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

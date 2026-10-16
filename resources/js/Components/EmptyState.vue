<script setup>
defineProps({
    icon: { type: String, default: '🎵' },
    title: String,
    text: String,
});
</script>

<template>
    <div class="card flex flex-col items-center px-6 py-10 text-center">
        <div class="mb-3 text-4xl">{{ icon }}</div>
        <h3 class="text-base font-semibold">{{ title }}</h3>
        <p class="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">{{ text }}</p>
        <div v-if="$slots.default" class="mt-5 w-full sm:w-auto">
            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: String,
    subtitle: String,
});
</script>

<template>
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-xl font-bold sm:text-2xl">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.default" class="flex flex-col gap-2 sm:flex-row">
            <slot />
        </div>
    </div>
</template>

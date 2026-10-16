<script setup>
defineProps({
    label: String,
    hint: String,
    error: String,
    id: String,
});
</script>

<template>
    <div>
        <label v-if="label" :for="id" class="label">{{ label }}</label>
        <slot />
        <p v-if="error" class="error">{{ error }}</p>
        <p v-else-if="hint" class="hint">{{ hint }}</p>
    </div>
</template>

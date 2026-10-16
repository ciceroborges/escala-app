<script setup>
// Passo a passo numerado: feito ✓, atual destacado, futuros apagados.
defineProps({
    steps: { type: Array, required: true }, // [{ title, text, done, current }]
});
</script>

<template>
    <ol class="space-y-3">
        <li v-for="(step, i) in steps" :key="i" class="flex gap-3">
            <span :class="['flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                step.done ? 'bg-green-500 text-white' : step.current ? 'bg-brand-500 text-white' : 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400']">
                {{ step.done ? '✓' : i + 1 }}
            </span>
            <div class="min-w-0 flex-1 pt-0.5">
                <p :class="['text-sm font-semibold', step.done && 'text-gray-400 line-through dark:text-gray-500']">{{ step.title }}</p>
                <p v-if="step.text && !step.done" class="text-sm text-gray-500 dark:text-gray-400">{{ step.text }}</p>
                <div v-if="step.current && $slots[`step-${i}`]" class="mt-2"><slot :name="`step-${i}`" /></div>
            </div>
        </li>
    </ol>
</template>

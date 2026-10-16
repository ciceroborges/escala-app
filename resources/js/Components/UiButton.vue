<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    variant: { type: String, default: 'primary' },
    href: { type: String, default: null },
    type: { type: String, default: 'button' },
    loading: Boolean,
    block: Boolean,
    small: Boolean,
});

const variants = {
    primary: 'bg-brand-500 text-white hover:bg-brand-600 disabled:bg-brand-300',
    secondary: 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700',
    danger: 'bg-red-600 text-white hover:bg-red-700',
    ghost: 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
    success: 'bg-green-600 text-white hover:bg-green-700',
};

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-60',
    props.small ? 'min-h-9 px-3 text-sm' : 'min-h-11 px-4 text-sm',
    props.block ? 'w-full' : 'w-full sm:w-auto',
    variants[props.variant],
]);
</script>

<template>
    <Link v-if="href" :href="href" :class="classes"><slot /></Link>
    <button v-else :type="type" :class="classes" :disabled="loading">
        <svg v-if="loading" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25" />
            <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" />
        </svg>
        <slot />
    </button>
</template>

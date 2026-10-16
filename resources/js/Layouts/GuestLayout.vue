<script setup>
import FlashToast from '@/Components/FlashToast.vue';
</script>

<template>
    <div class="flex min-h-dvh flex-col items-center justify-center px-4 py-10">
        <div class="mb-8 flex items-center gap-3">
            <img src="/icons/icon-192.png" alt="" class="size-11 rounded-xl">
            <span class="text-2xl font-bold text-brand-500">Escala</span>
        </div>
        <div class="w-full max-w-md">
            <slot />
        </div>
        <FlashToast />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import UiButton from '@/Components/UiButton.vue';

const props = defineProps({
    checklist: Array,
    upcoming: Array,
    pendingAvailability: Array,
});

const page = usePage();
const firstName = computed(() => page.props.member?.name?.split(' ')[0]);
const doneCount = computed(() => props.checklist.filter((s) => s.done).length);
const nextStep = computed(() => props.checklist.find((s) => !s.done));
const showChecklist = computed(() => props.checklist.length && doneCount.value < props.checklist.length);
</script>

<template>
    <Head title="Início" />

    <h1 class="text-xl font-bold sm:text-2xl">Olá, {{ firstName }}!</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ page.props.ministry?.name }}</p>

    <!-- Primeiros passos (líder) -->
    <section v-if="showChecklist" class="card mt-5 p-5">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold">Primeiros passos</h2>
            <span class="text-sm text-gray-500">{{ doneCount }} de {{ checklist.length }}</span>
        </div>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
            <div class="h-full rounded-full bg-brand-500 transition-all" :style="{ width: `${(doneCount / checklist.length) * 100}%` }" />
        </div>

        <ol class="mt-4 space-y-2">
            <li v-for="(step, i) in checklist" :key="step.key">
                <Link :href="route(step.route)"
                      :class="['flex items-start gap-3 rounded-xl p-3 transition-colors',
                          step === nextStep ? 'bg-brand-50 dark:bg-brand-950/60' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50']">
                    <span :class="['mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                        step.done ? 'bg-green-500 text-white' : step === nextStep ? 'bg-brand-500 text-white' : 'bg-gray-200 text-gray-500 dark:bg-gray-700']">
                        {{ step.done ? '✓' : i + 1 }}
                    </span>
                    <div class="flex-1">
                        <p :class="['text-sm font-semibold', step.done && 'text-gray-400 line-through']">{{ step.title }}</p>
                        <p v-if="!step.done" class="text-sm text-gray-500 dark:text-gray-400">{{ step.description }}</p>
                    </div>
                    <span v-if="step === nextStep" class="self-center text-sm font-semibold text-brand-600 dark:text-brand-400">Fazer →</span>
                </Link>
            </li>
        </ol>
    </section>

    <!-- Disponibilidade pendente -->
    <section v-for="s in pendingAvailability" :key="s.id" class="mt-5 rounded-2xl bg-brand-500 p-5 text-white">
        <p class="text-sm font-medium text-brand-100">Escala de {{ s.label }}</p>
        <h2 class="mt-1 text-lg font-bold">Tem algum domingo que você não pode?</h2>
        <p class="mt-1 text-sm text-brand-100">
            Marque agora para o líder montar a escala{{ s.deadline ? ` — prazo até ${s.deadline}` : '' }}.
        </p>
        <Link :href="route('schedules.show', s.id)"
              class="mt-4 flex min-h-11 w-full items-center justify-center rounded-lg bg-white px-4 text-sm font-semibold text-brand-600 sm:w-auto sm:inline-flex">
            Marcar minha disponibilidade
        </Link>
    </section>

    <!-- Próximas escalas -->
    <section class="mt-6">
        <h2 class="mb-3 font-semibold">Seus próximos cultos</h2>

        <div v-if="upcoming.length" class="space-y-3">
            <Link v-for="s in upcoming" :key="s.id" :href="route('services.show', s.id)"
                  class="card flex items-center gap-4 p-4 hover:border-brand-300 dark:hover:border-brand-700">
                <div class="flex w-14 shrink-0 flex-col items-center rounded-xl bg-brand-50 py-2 dark:bg-brand-950">
                    <span class="text-lg font-bold text-brand-600 dark:text-brand-300">{{ s.date.split('/')[0] }}</span>
                    <span class="text-xs text-brand-500">/{{ s.date.split('/')[1] }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">{{ s.weekday }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ s.positions.join(' · ') }}</p>
                    <p class="text-xs text-gray-400">{{ s.relative }}</p>
                </div>
                <span class="text-sm font-semibold text-brand-600 dark:text-brand-400">Repertório →</span>
            </Link>
        </div>

        <div v-else class="card p-6 text-center text-sm text-gray-500 dark:text-gray-400">
            Nenhuma escala publicada para você por enquanto.
            <div class="mt-4">
                <UiButton :href="route('schedules.index')" variant="secondary">Ver escalas</UiButton>
            </div>
        </div>
    </section>
</template>

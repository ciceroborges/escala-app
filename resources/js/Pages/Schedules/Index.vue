<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PageHeader from '@/Components/PageHeader.vue';
import UiButton from '@/Components/UiButton.vue';
import UiField from '@/Components/UiField.vue';
import UiSheet from '@/Components/UiSheet.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    schedules: Array,
    suggestedMonth: String,
});

const page = usePage();
const sheetOpen = ref(false);
const form = useForm({ month: props.suggestedMonth, availability_deadline: '' });
const open = () => form.post(route('schedules.store'), { onSuccess: () => (sheetOpen.value = false) });
</script>

<template>
    <Head title="Escalas" />

    <PageHeader title="Escalas" subtitle="Uma escala por mês, com todos os domingos.">
        <UiButton v-if="page.props.can.manageSchedule" @click="sheetOpen = true">+ Abrir novo mês</UiButton>
    </PageHeader>

    <EmptyState v-if="!schedules.length" icon="📅" title="Nenhum mês aberto"
                :text="page.props.can.manageSchedule
                    ? 'Abra o próximo mês: os domingos são criados sozinhos e a equipe já pode marcar quando não pode.'
                    : 'Quando o líder abrir o próximo mês, ele aparece aqui.'">
        <UiButton v-if="page.props.can.manageSchedule" @click="sheetOpen = true">Abrir o próximo mês</UiButton>
    </EmptyState>

    <ul v-else class="space-y-3">
        <li v-for="s in schedules" :key="s.id">
            <Link :href="route('schedules.show', s.id)" class="card flex items-center gap-4 p-4 hover:border-brand-300 dark:hover:border-brand-700">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-xl dark:bg-brand-950">📅</div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">{{ s.label }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ s.services_count }} domingo(s){{ s.deadline ? ` · disponibilidade até ${s.deadline}` : '' }}</p>
                </div>
                <span :class="['rounded-full px-2.5 py-1 text-xs font-medium',
                    s.status === 'published' ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300']">
                    {{ s.status_label }}
                </span>
            </Link>
        </li>
    </ul>

    <UiSheet :open="sheetOpen" title="Abrir novo mês" subtitle="Um culto por domingo é criado automaticamente." @close="sheetOpen = false">
        <form id="open-month" class="space-y-4" @submit.prevent="open">
            <UiField label="Mês" :error="form.errors.month" id="month">
                <input id="month" v-model="form.month" type="month" class="input" required>
            </UiField>
            <UiField label="Prazo para a equipe marcar quando não pode (opcional)" :error="form.errors.availability_deadline" id="deadline"
                     hint="Aparece para todos na tela inicial.">
                <input id="deadline" v-model="form.availability_deadline" type="date" class="input">
            </UiField>
        </form>
        <template #footer>
            <UiButton variant="secondary" @click="sheetOpen = false">Cancelar</UiButton>
            <UiButton type="submit" form="open-month" :loading="form.processing">Abrir mês</UiButton>
        </template>
    </UiSheet>
</template>

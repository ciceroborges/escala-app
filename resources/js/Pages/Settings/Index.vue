<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import PageHeader from '@/Components/PageHeader.vue';
import UiButton from '@/Components/UiButton.vue';
import UiField from '@/Components/UiField.vue';
import UiSheet from '@/Components/UiSheet.vue';

const props = defineProps({
    settings: Object,
    positions: Array,
    categories: Object,
    activity: Array,
});

const ministry = useForm({ ...props.settings, church_name: props.settings.church_name ?? '' });
const saveMinistry = () => ministry.put(route('settings.ministry'), { preserveScroll: true });

// Funções
const positionSheet = ref(false);
const editingPosition = ref(null);
const blankPosition = () => ({ name: '', emoji: '', uses_microphone: false, is_vocal: false, min_per_service: 0, max_per_service: 1 });
const position = useForm(blankPosition());

const openPosition = (p = null) => {
    editingPosition.value = p;
    position.defaults(p ? { ...blankPosition(), ...p, emoji: p.emoji ?? '' } : blankPosition()).reset();
    position.clearErrors();
    positionSheet.value = true;
};
const savePosition = () => {
    const options = { preserveScroll: true, onSuccess: () => (positionSheet.value = false) };
    editingPosition.value
        ? position.put(route('settings.positions.update', editingPosition.value.id), options)
        : position.post(route('settings.positions.store'), options);
};
const removePosition = () => {
    const p = editingPosition.value;
    const warn = p.members_count ? ` ${p.members_count} pessoa(s) perdem essa função e ela sai das escalas.` : '';
    if (!confirm(`Remover a função ${p.name}?${warn}`)) return;
    router.delete(route('settings.positions.destroy', p.id), { preserveScroll: true, onSuccess: () => (positionSheet.value = false) });
};
const slots = (p) => (p.min_per_service === p.max_per_service ? `${p.min_per_service}` : `${p.min_per_service} a ${p.max_per_service}`);

// Classificações das músicas
const cloneCategory = (c, fallback) => ({
    name: c?.name ?? fallback,
    active: c?.active ?? false,
    options: (c?.options ?? []).map((o) => ({ ...o, emoji: o.emoji ?? '' })),
});
const categories = useForm({
    primary: cloneCategory(props.categories.primary, 'Ritmo'),
    secondary: cloneCategory(props.categories.secondary, 'Tipo'),
});
const addOption = (level) => categories[level].options.push({ id: null, name: '', emoji: '' });
const removeOption = (level, i) => categories[level].options.splice(i, 1);
const saveCategories = () => categories.put(route('settings.categories'), { preserveScroll: true });
</script>

<template>
    <Head title="Ajustes" />
    <PageHeader title="Ajustes" subtitle="Configure uma vez e esqueça." />

    <div class="space-y-6">
        <!-- Ministério -->
        <section class="card p-5">
            <h2 class="font-semibold">Ministério</h2>
            <form class="mt-4 space-y-4" @submit.prevent="saveMinistry">
                <UiField label="Nome" :error="ministry.errors.name" id="s-name">
                    <input id="s-name" v-model="ministry.name" class="input" required>
                </UiField>
                <UiField label="Igreja" :error="ministry.errors.church_name" id="s-church">
                    <input id="s-church" v-model="ministry.church_name" class="input">
                </UiField>
                <UiButton type="submit" :loading="ministry.processing" :disabled="!ministry.isDirty">Salvar</UiButton>
            </form>
        </section>

        <!-- Funções -->
        <section class="card p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Funções e vagas por culto</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        O mínimo é o que a escala automática sempre tenta preencher; o máximo é o teto.
                        Ex.: vocal de 3 a 4 + DM até 1 = no máximo 5 microfones.
                    </p>
                </div>
            </div>

            <ul class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                <li v-for="p in positions" :key="p.id">
                    <button type="button" class="flex min-h-14 w-full items-center gap-3 py-2 text-left" @click="openPosition(p)">
                        <span class="w-7 text-center text-xl">{{ p.emoji || '•' }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ p.name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ slots(p) }} por culto · {{ p.members_count }} pessoa(s)
                                <span v-if="p.is_vocal"> · canta</span>
                                <span v-if="p.uses_microphone"> · 🎙️ microfone</span>
                            </p>
                        </div>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400"><path d="m9 18 6-6-6-6" /></svg>
                    </button>
                </li>
            </ul>
            <UiButton variant="secondary" class="mt-3" @click="openPosition()">+ Nova função</UiButton>
        </section>

        <!-- Classificações -->
        <section class="card p-5">
            <h2 class="font-semibold">Como classificar as músicas</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                A primária aparece com o emoji no repertório (ex.: ritmo 🔴🟡🔵). A secundária é opcional.
            </p>

            <form class="mt-4 space-y-6" @submit.prevent="saveCategories">
                <div v-for="level in ['primary', 'secondary']" :key="level" class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">{{ level === 'primary' ? 'Primária' : 'Secundária' }}</p>
                        <label class="flex min-h-11 items-center gap-2 text-sm">
                            <input v-model="categories[level].active" type="checkbox" class="size-5 rounded text-brand-500">
                            Usar
                        </label>
                    </div>
                    <div v-if="categories[level].active" class="mt-2 space-y-3">
                        <UiField label="Nome" :error="categories.errors[`${level}.name`]">
                            <input v-model="categories[level].name" class="input" :placeholder="level === 'primary' ? 'Ritmo' : 'Tipo'">
                        </UiField>
                        <p class="label">Opções</p>
                        <div v-for="(option, i) in categories[level].options" :key="i" class="flex gap-2">
                            <input v-model="option.emoji" class="input w-16 shrink-0 text-center" placeholder="🙂" maxlength="4" aria-label="Emoji">
                            <input v-model="option.name" class="input" placeholder="Nome da opção">
                            <button type="button" class="size-11 shrink-0 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="Remover" @click="removeOption(level, i)">✕</button>
                        </div>
                        <button type="button" class="text-sm font-semibold text-brand-600 dark:text-brand-400" @click="addOption(level)">+ Adicionar opção</button>
                    </div>
                </div>
                <UiButton type="submit" :loading="categories.processing">Salvar classificações</UiButton>
            </form>
        </section>

        <!-- Atividade -->
        <section class="card p-5">
            <h2 class="font-semibold">Atividade recente</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Quem fez o quê no ministério.</p>
            <p v-if="!activity.length" class="mt-4 text-sm text-gray-500">Nada registrado ainda.</p>
            <ul v-else class="mt-4 divide-y divide-gray-100 text-sm dark:divide-gray-700">
                <li v-for="log in activity" :key="log.id" class="flex gap-3 py-2.5">
                    <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold dark:bg-gray-700">{{ log.who.charAt(0) }}</div>
                    <div class="min-w-0 flex-1">
                        <p><strong>{{ log.who }}</strong> {{ log.what }}</p>
                        <p class="text-xs text-gray-400" :title="log.at">{{ log.when }}</p>
                    </div>
                </li>
            </ul>
        </section>
    </div>

    <UiSheet :open="positionSheet" :title="editingPosition ? editingPosition.name : 'Nova função'" @close="positionSheet = false">
        <form id="position-form" class="space-y-4" @submit.prevent="savePosition">
            <div class="flex gap-3">
                <UiField label="Emoji" class="w-20" :error="position.errors.emoji">
                    <input v-model="position.emoji" class="input text-center" maxlength="4" placeholder="🎸">
                </UiField>
                <UiField label="Nome" class="flex-1" :error="position.errors.name">
                    <input v-model="position.name" class="input" placeholder="Saxofone" required>
                </UiField>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <UiField label="Mínimo por culto" :error="position.errors.min_per_service">
                    <input v-model.number="position.min_per_service" type="number" min="0" max="20" inputmode="numeric" class="input">
                </UiField>
                <UiField label="Máximo por culto" :error="position.errors.max_per_service">
                    <input v-model.number="position.max_per_service" type="number" min="0" max="20" inputmode="numeric" class="input">
                </UiField>
            </div>
            <p class="hint -mt-2">Mínimo 0 = opcional (só entra se tiver gente).</p>
            <label class="flex min-h-11 items-center gap-3 text-sm">
                <input v-model="position.is_vocal" type="checkbox" class="size-5 rounded text-brand-500">
                É vocal (pode puxar música no repertório)
            </label>
            <label class="flex min-h-11 items-center gap-3 text-sm">
                <input v-model="position.uses_microphone" type="checkbox" class="size-5 rounded text-brand-500">
                Usa microfone (entra no total da mensagem)
            </label>
        </form>
        <template #footer>
            <UiButton v-if="editingPosition" variant="ghost" class="text-red-600 sm:mr-auto" @click="removePosition">Remover</UiButton>
            <UiButton variant="secondary" @click="positionSheet = false">Cancelar</UiButton>
            <UiButton type="submit" form="position-form" :loading="position.processing">Salvar</UiButton>
        </template>
    </UiSheet>
</template>

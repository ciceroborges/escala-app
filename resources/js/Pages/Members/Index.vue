<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import PageHeader from '@/Components/PageHeader.vue';
import UiButton from '@/Components/UiButton.vue';
import UiField from '@/Components/UiField.vue';
import UiSheet from '@/Components/UiSheet.vue';
import EmptyState from '@/Components/EmptyState.vue';
import HelpTip from '@/Components/HelpTip.vue';

const props = defineProps({
    members: Array,
    positions: Array,
    roles: Array,
    permissions: Array,
});

const search = ref('');
const editing = ref(null);
const sheetOpen = ref(false);

const blank = () => ({
    name: '', email: '', phone: '', role: 'member', permissions: [], positions: [],
    fixed_position_id: null, monthly_limit: 2, unlimited: false, active: true,
});
const form = useForm(blank());

const positionName = (id) => {
    const p = props.positions.find((x) => x.id === id);
    return p ? `${p.emoji ?? ''} ${p.name}`.trim() : '';
};

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return q ? props.members.filter((m) => m.name.toLowerCase().includes(q) || m.email.includes(q)) : props.members;
});
const withoutPositions = computed(() => props.members.filter((m) => m.active && !m.positions.length).length);

const openNew = () => {
    editing.value = null;
    form.defaults(blank()).reset();
    form.clearErrors();
    sheetOpen.value = true;
};

const openEdit = (m) => {
    editing.value = m;
    form.defaults({
        ...blank(),
        ...m,
        phone: m.phone ?? '',
        unlimited: m.monthly_limit === null,
        monthly_limit: m.monthly_limit ?? 2,
    }).reset();
    form.clearErrors();
    sheetOpen.value = true;
};

const togglePosition = (id) => {
    const i = form.positions.indexOf(id);
    i === -1 ? form.positions.push(id) : form.positions.splice(i, 1);
    if (!form.positions.includes(form.fixed_position_id)) form.fixed_position_id = null;
};

const chosenPositions = computed(() => props.positions.filter((p) => form.positions.includes(p.id)));

const save = () => {
    const options = { preserveScroll: true, onSuccess: () => (sheetOpen.value = false) };
    editing.value ? form.put(route('members.update', editing.value.id), options) : form.post(route('members.store'), options);
};

const remove = () => {
    if (!confirm(`Remover ${editing.value.name} do ministério?`)) return;
    router.delete(route('members.destroy', editing.value.id), { preserveScroll: true, onSuccess: () => (sheetOpen.value = false) });
};
</script>

<template>
    <Head title="Equipe" />

    <PageHeader title="Equipe" :subtitle="`${members.length} pessoa(s) no ministério`">
        <UiButton @click="openNew">+ Adicionar pessoa</UiButton>
    </PageHeader>

    <HelpTip id="members-intro">
        Cadastre cada pessoa com o <strong>e-mail do Google</strong> dela. Quando ela entrar no app com esse e-mail,
        já cai direto no ministério. Dá para escalar mesmo quem ainda não entrou.
    </HelpTip>

    <div v-if="withoutPositions" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-200">
        ⚠️ {{ withoutPositions }} pessoa(s) sem função — elas não entram na escala automática. Toque nelas para dizer o que fazem.
    </div>

    <input v-if="members.length > 6" v-model="search" type="search" class="input mb-4" placeholder="Buscar por nome ou e-mail">

    <EmptyState v-if="members.length <= 1" icon="👥" title="Só você por aqui"
                text="Adicione os músicos, vocais e quem fica na mesa. Leva uns 20 segundos por pessoa.">
        <UiButton @click="openNew">+ Adicionar a primeira pessoa</UiButton>
    </EmptyState>

    <ul class="space-y-2" :class="members.length <= 1 && 'mt-4'">
        <li v-for="m in filtered" :key="m.id">
            <button type="button" class="card flex w-full items-center gap-3 p-4 text-left hover:border-brand-300 dark:hover:border-brand-700"
                    :class="!m.active && 'opacity-50'" @click="openEdit(m)">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 font-semibold text-brand-600 dark:bg-brand-950 dark:text-brand-300">
                    {{ m.name.charAt(0).toUpperCase() }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="flex flex-wrap items-center gap-x-2 font-semibold">
                        {{ m.name }}
                        <span v-if="m.is_me" class="text-xs font-normal text-gray-400">(você)</span>
                        <span v-if="m.role === 'admin'" class="rounded-full bg-brand-100 px-2 py-0.5 text-[11px] font-medium text-brand-700 dark:bg-brand-900 dark:text-brand-200">Admin</span>
                        <span v-if="!m.joined" class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">ainda não entrou</span>
                        <span v-if="!m.active" class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-700">inativo</span>
                    </p>
                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                        {{ m.positions.length ? m.positions.map(positionName).join(' · ') : 'Sem função definida' }}
                    </p>
                    <p class="text-xs text-gray-400">
                        {{ m.fixed_position_id ? `Fixo em ${positionName(m.fixed_position_id)}` : m.monthly_limit === null ? 'Sem limite por mês' : `Até ${m.monthly_limit}x por mês` }}
                    </p>
                </div>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-gray-400"><path d="m9 18 6-6-6-6" /></svg>
            </button>
        </li>
    </ul>

    <UiSheet :open="sheetOpen" :title="editing ? editing.name : 'Adicionar pessoa'"
             :subtitle="editing && !editing.joined ? 'Ainda não entrou no app' : null" @close="sheetOpen = false">
        <form id="member-form" class="space-y-5" @submit.prevent="save">
            <UiField label="Nome" :error="form.errors.name" id="m-name">
                <input id="m-name" v-model="form.name" class="input" placeholder="Maria Souza" required>
            </UiField>

            <UiField label="E-mail do Google" :error="form.errors.email" hint="É com ele que a pessoa entra no app." id="m-email">
                <input id="m-email" v-model="form.email" type="email" class="input" placeholder="maria@gmail.com" required>
            </UiField>

            <div>
                <p class="label">O que essa pessoa faz?</p>
                <div class="flex flex-wrap gap-2">
                    <button v-for="p in positions" :key="p.id" type="button" @click="togglePosition(p.id)"
                            :class="['min-h-10 rounded-full border px-3 text-sm font-medium transition-colors',
                                form.positions.includes(p.id) ? 'border-brand-500 bg-brand-500 text-white'
                                    : 'border-gray-300 text-gray-700 hover:border-brand-300 dark:border-gray-600 dark:text-gray-200']">
                        {{ p.emoji }} {{ p.name }}
                    </button>
                </div>
                <p class="hint">Pode marcar mais de uma (ex.: toca teclado e canta).</p>
            </div>

            <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-900/60">
                <p class="label">Quantas vezes por mês pode ser escalado?</p>
                <div class="flex items-center gap-3">
                    <div class="flex items-center rounded-lg border border-gray-300 dark:border-gray-600" :class="form.unlimited && 'opacity-40'">
                        <button type="button" class="size-11 text-lg" :disabled="form.unlimited" @click="form.monthly_limit = Math.max(1, form.monthly_limit - 1)">−</button>
                        <span class="w-8 text-center font-semibold">{{ form.monthly_limit }}</span>
                        <button type="button" class="size-11 text-lg" :disabled="form.unlimited" @click="form.monthly_limit++">+</button>
                    </div>
                    <label class="flex min-h-11 items-center gap-2 text-sm">
                        <input v-model="form.unlimited" type="checkbox" class="size-5 rounded text-brand-500">
                        Sem limite
                    </label>
                </div>
                <p v-if="form.errors.monthly_limit" class="error">{{ form.errors.monthly_limit }}</p>

                <div v-if="chosenPositions.length" class="mt-4">
                    <label for="m-fixed" class="label">Vai em todos os cultos?</label>
                    <select id="m-fixed" v-model="form.fixed_position_id" class="input">
                        <option :value="null">Não, entra no rodízio</option>
                        <option v-for="p in chosenPositions" :key="p.id" :value="p.id">Sim, sempre em {{ p.emoji }} {{ p.name }}</option>
                    </select>
                    <p class="hint">Use para o líder ou para quem é o único na função.</p>
                </div>
            </div>

            <details class="group rounded-xl border border-gray-200 dark:border-gray-700" :open="form.role === 'admin' || form.permissions.length > 0">
                <summary class="flex min-h-11 cursor-pointer items-center justify-between px-4 text-sm font-medium">
                    Permissões e mais opções
                    <span class="text-gray-400 group-open:rotate-180">⌄</span>
                </summary>
                <div class="space-y-4 border-t border-gray-200 p-4 dark:border-gray-700">
                    <UiField label="Papel" :error="form.errors.role" id="m-role">
                        <select id="m-role" v-model="form.role" class="input">
                            <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                        </select>
                    </UiField>
                    <p v-if="form.role === 'admin'" class="hint -mt-2">Administrador pode tudo: escalas, repertório, equipe e ajustes.</p>

                    <div v-else>
                        <p class="label">Pode também…</p>
                        <label v-for="perm in permissions" :key="perm.value" class="flex min-h-11 items-center gap-3 text-sm">
                            <input v-model="form.permissions" type="checkbox" :value="perm.value" class="size-5 rounded text-brand-500">
                            {{ perm.label }}
                        </label>
                    </div>

                    <UiField label="Celular (opcional)" :error="form.errors.phone" hint="Para lembretes no WhatsApp, em breve." id="m-phone">
                        <input id="m-phone" v-model="form.phone" type="tel" inputmode="tel" class="input" placeholder="(51) 99999-0000">
                    </UiField>

                    <label v-if="editing" class="flex min-h-11 items-center gap-3 text-sm">
                        <input v-model="form.active" type="checkbox" class="size-5 rounded text-brand-500">
                        Ativo (desmarque para tirar da escala sem apagar)
                    </label>
                </div>
            </details>

            <p v-if="form.errors.member" class="error">{{ form.errors.member }}</p>
        </form>

        <template #footer>
            <UiButton v-if="editing && !editing.is_me" variant="ghost" class="text-red-600 sm:mr-auto" @click="remove">Remover</UiButton>
            <UiButton variant="secondary" @click="sheetOpen = false">Cancelar</UiButton>
            <UiButton type="submit" form="member-form" :loading="form.processing">{{ editing ? 'Salvar' : 'Adicionar' }}</UiButton>
        </template>
    </UiSheet>
</template>

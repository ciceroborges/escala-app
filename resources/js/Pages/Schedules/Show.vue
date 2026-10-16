<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import UiButton from '@/Components/UiButton.vue';
import UiSheet from '@/Components/UiSheet.vue';
import StepList from '@/Components/StepList.vue';

const props = defineProps({
    schedule: Object,
    services: Array,
    positions: Array,
    members: Array,
    generalWarnings: Array,
    myUnavailable: Array,
    myResponded: Boolean,
    teamVisible: Boolean,
});

const page = usePage();
const manager = computed(() => page.props.can.manageSchedule);
const published = computed(() => props.schedule.status === 'published');
const memberName = (id) => props.members.find((m) => m.id === id)?.name ?? '—';
const hasAssignments = computed(() => props.services.some((s) => s.assignments.length));
const respondedCount = computed(() => props.members.filter((m) => m.responded).length);
const totalWarnings = computed(() => props.generalWarnings.length + props.services.reduce((n, s) => n + s.warnings.length, 0));

// Passo a passo do mês (líder)
const steps = computed(() => {
    const generated = hasAssignments.value;
    return [
        { title: 'Equipe marca quando não pode', text: `${respondedCount.value} de ${props.members.length} já responderam.`, done: generated, current: !generated },
        { title: 'Gerar a escala', text: 'O app monta respeitando funções, limites e disponibilidade.', done: generated, current: false },
        { title: 'Conferir e ajustar', text: 'Toque em um domingo para trocar alguém.', done: published.value, current: generated && !published.value },
        { title: 'Publicar e mandar no WhatsApp', text: 'A equipe passa a ver a escala no app.', done: published.value, current: false },
    ];
});

// Minha disponibilidade
const availability = useForm({ member_id: null, unavailable: [...props.myUnavailable] });
const toggleMine = (id) => {
    const i = availability.unavailable.indexOf(id);
    i === -1 ? availability.unavailable.push(id) : availability.unavailable.splice(i, 1);
};
const saveMine = () => availability.put(route('schedules.availability', props.schedule.id), { preserveScroll: true });

// Disponibilidade de outra pessoa (líder marca por quem não entrou)
const otherSheet = ref(false);
const other = useForm({ member_id: null, unavailable: [] });
const openOther = (m) => {
    other.member_id = m.id;
    other.unavailable = props.services.filter((s) => s.unavailable.includes(m.id)).map((s) => s.id);
    otherSheet.value = true;
};
const toggleOther = (id) => {
    const i = other.unavailable.indexOf(id);
    i === -1 ? other.unavailable.push(id) : other.unavailable.splice(i, 1);
};
const saveOther = () => other.put(route('schedules.availability', props.schedule.id), { preserveScroll: true, onSuccess: () => (otherSheet.value = false) });

// Gerar / publicar
const generating = ref(false);
const generate = () => {
    if (hasAssignments.value && !confirm('Gerar de novo substitui a escala atual (inclusive seus ajustes). Continuar?')) return;
    router.post(route('schedules.generate', props.schedule.id), {}, {
        preserveScroll: true,
        onStart: () => (generating.value = true),
        onFinish: () => (generating.value = false),
    });
};
const publish = () => {
    if (totalWarnings.value && !confirm(`Ainda há ${totalWarnings.value} aviso(s). Publicar mesmo assim?`)) return;
    router.post(route('schedules.publish', props.schedule.id));
};
const unpublish = () => router.post(route('schedules.unpublish', props.schedule.id), {}, { preserveScroll: true });
const destroy = () => confirm(`Excluir a escala de ${props.schedule.label}? Isso apaga escalas e repertórios do mês.`)
    && router.delete(route('schedules.destroy', props.schedule.id));

// Edição de um domingo
const editSheet = ref(false);
const editing = ref(null);
const team = useForm({ assignments: [], notes: '' });
const openService = (s) => {
    if (!manager.value) return router.visit(route('services.show', s.id));
    editing.value = s;
    team.assignments = s.assignments.map((a) => ({ ...a }));
    team.notes = s.notes ?? '';
    editSheet.value = true;
};
const inPosition = (positionId) => team.assignments.filter((a) => a.position_id === positionId).map((a) => a.member_id);
const candidates = (positionId) => props.members.filter((m) => m.positions.includes(positionId) && !inPosition(positionId).includes(m.id));
const addTo = (positionId, event) => {
    const memberId = Number(event.target.value);
    if (memberId) team.assignments.push({ member_id: memberId, position_id: positionId });
    event.target.value = '';
};
const removeFrom = (positionId, memberId) => {
    team.assignments = team.assignments.filter((a) => !(a.position_id === positionId && a.member_id === memberId));
};
const countFor = (memberId) => props.services.filter((s) => (s.id === editing.value?.id ? team.assignments : s.assignments).some((a) => a.member_id === memberId)).length;
const candidateLabel = (m) => {
    const tags = [];
    if (editing.value?.unavailable.includes(m.id)) tags.push('🚫 não pode');
    if (m.monthly_limit !== null && countFor(m.id) >= m.monthly_limit && !team.assignments.some((a) => a.member_id === m.id)) tags.push('no limite');
    tags.push(`${countFor(m.id)}x no mês`);
    return `${m.name} (${tags.join(', ')})`;
};
const saveService = () => team.put(route('services.team', editing.value.id), { preserveScroll: true, onSuccess: () => (editSheet.value = false) });

const lineFor = (service, position) => service.assignments.filter((a) => a.position_id === position.id).map((a) => memberName(a.member_id));
const iAmIn = (service) => service.assignments.some((a) => a.member_id === page.props.member?.id);
</script>

<template>
    <Head :title="schedule.label" />

    <div class="mb-5 flex items-start justify-between gap-3">
        <div>
            <Link :href="route('schedules.index')" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">← Escalas</Link>
            <h1 class="mt-1 text-xl font-bold sm:text-2xl">{{ schedule.label }}</h1>
        </div>
        <span :class="['mt-6 rounded-full px-2.5 py-1 text-xs font-medium',
            published ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300']">
            {{ schedule.status_label }}
        </span>
    </div>

    <!-- Minha disponibilidade (todos, enquanto rascunho) -->
    <section v-if="!published" class="card mb-5 p-5">
        <h2 class="font-semibold">{{ myResponded ? 'Sua disponibilidade' : 'Tem algum domingo que você não pode?' }}</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Toque nos dias em que você <strong>não pode</strong>{{ schedule.deadline ? ` — até ${schedule.deadline}` : '' }}. Se pode em todos, é só confirmar.
        </p>
        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-5">
            <button v-for="s in services" :key="s.id" type="button" @click="toggleMine(s.id)"
                    :class="['flex min-h-16 flex-col items-center justify-center rounded-xl border-2 text-sm font-semibold transition-colors',
                        availability.unavailable.includes(s.id)
                            ? 'border-red-400 bg-red-50 text-red-700 dark:border-red-700 dark:bg-red-950 dark:text-red-300'
                            : 'border-green-300 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950 dark:text-green-300']">
                <span class="text-base">{{ s.date }}</span>
                <span class="text-xs font-medium">{{ availability.unavailable.includes(s.id) ? '🚫 Não posso' : '✓ Posso' }}</span>
            </button>
        </div>
        <UiButton class="mt-4" :loading="availability.processing" @click="saveMine">
            {{ availability.unavailable.length ? `Salvar (${availability.unavailable.length} dia(s) que não posso)` : 'Posso em todos — confirmar' }}
        </UiButton>
        <p v-if="myResponded && !availability.isDirty" class="hint">✓ Você já respondeu. Pode mudar até a escala sair.</p>
    </section>

    <!-- Passo a passo (líder) -->
    <section v-if="manager && !published" class="card mb-5 p-5">
        <h2 class="mb-4 font-semibold">Como fechar este mês</h2>
        <StepList :steps="steps">
            <template #step-0>
                <div class="flex flex-wrap gap-1.5">
                    <button v-for="m in members" :key="m.id" type="button" @click="openOther(m)"
                            :class="['min-h-8 rounded-full px-2.5 text-xs font-medium', m.responded
                                ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                                : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300']">
                        {{ m.responded ? '✓' : '…' }} {{ m.name }}
                    </button>
                </div>
                <p class="hint">Toque em alguém para marcar por ele (útil para quem ainda não usa o app).</p>
                <UiButton class="mt-3" :loading="generating" @click="generate">✨ Gerar escala agora</UiButton>
            </template>
            <template #step-2>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <UiButton variant="success" @click="publish">Publicar escala</UiButton>
                    <UiButton variant="secondary" :loading="generating" @click="generate">Gerar de novo</UiButton>
                </div>
            </template>
        </StepList>
    </section>

    <section v-if="manager && published" class="card mb-5 flex flex-col gap-2 p-4 sm:flex-row sm:items-center">
        <p class="flex-1 text-sm text-gray-600 dark:text-gray-300">✅ Publicada — a equipe já vê no app.</p>
        <UiButton :href="route('schedules.messages', schedule.id)">📲 Mensagens do WhatsApp</UiButton>
        <UiButton variant="secondary" @click="unpublish">Voltar para rascunho</UiButton>
    </section>

    <div v-if="manager && generalWarnings.length" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-200">
        <p v-for="w in generalWarnings" :key="w">⚠️ {{ w }}</p>
    </div>

    <!-- Domingos -->
    <div v-if="hasAssignments || published" class="space-y-3">
        <h2 v-if="!teamVisible" class="font-semibold">Seus domingos</h2>
        <button v-for="s in services" :key="s.id" type="button" class="card block w-full p-4 text-left hover:border-brand-300 dark:hover:border-brand-700"
                :class="!teamVisible && !iAmIn(s) && 'hidden'" @click="openService(s)">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="font-semibold">{{ s.date }} <span class="font-normal text-gray-500 dark:text-gray-400">· {{ s.weekday }}</span></p>
                </div>
                <span v-if="iAmIn(s)" class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-700 dark:bg-brand-900 dark:text-brand-200">você</span>
            </div>
            <dl class="mt-3 space-y-1 text-sm">
                <div v-for="p in positions" :key="p.id" v-show="lineFor(s, p).length || (manager && p.min_per_service)" class="flex gap-2">
                    <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ p.emoji }} {{ p.name }}</dt>
                    <dd :class="!lineFor(s, p).length && 'text-red-500'">{{ lineFor(s, p).join(', ') || 'vaga' }}</dd>
                </div>
            </dl>
            <p v-if="s.notes" class="mt-2 text-sm text-gray-600 dark:text-gray-300">📝 {{ s.notes }}</p>
            <div v-if="s.warnings.length" class="mt-3 space-y-0.5 text-xs text-amber-700 dark:text-amber-300">
                <p v-for="w in s.warnings" :key="w">⚠️ {{ w }}</p>
            </div>
            <div class="mt-3 flex items-center justify-between text-sm">
                <Link :href="route('services.show', s.id)" class="font-semibold text-brand-600 dark:text-brand-400" @click.stop>
                    🎶 Repertório ({{ s.setlist_count }})
                </Link>
                <span v-if="manager" class="text-gray-400">Toque para editar</span>
            </div>
        </button>
    </div>

    <button v-if="manager" type="button" class="mt-8 w-full py-3 text-center text-sm text-red-600" @click="destroy">Excluir este mês</button>

    <!-- Editar domingo -->
    <UiSheet :open="editSheet" :title="editing ? `${editing.date} · ${editing.weekday}` : ''" subtitle="Quem vai neste culto" @close="editSheet = false">
        <div v-if="editing" class="space-y-4">
            <div v-for="p in positions" :key="p.id">
                <p class="label">{{ p.emoji }} {{ p.name }}
                    <span class="font-normal text-gray-400">
                        ({{ inPosition(p.id).length }}{{ p.max_per_service ? ` de ${p.max_per_service}` : '' }}{{ p.min_per_service ? `, mín. ${p.min_per_service}` : '' }})
                    </span>
                </p>
                <div class="flex flex-wrap gap-2">
                    <span v-for="id in inPosition(p.id)" :key="id"
                          :class="['inline-flex min-h-9 items-center gap-1 rounded-full pl-3 pr-1 text-sm',
                              editing.unavailable.includes(id) ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' : 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-200']">
                        {{ memberName(id) }}{{ editing.unavailable.includes(id) ? ' 🚫' : '' }}
                        <button type="button" class="flex size-7 items-center justify-center rounded-full hover:bg-black/10" :aria-label="`Tirar ${memberName(id)}`" @click="removeFrom(p.id, id)">✕</button>
                    </span>
                    <select v-if="candidates(p.id).length && inPosition(p.id).length < Math.max(p.max_per_service, 1)" class="input h-9 min-h-9 w-auto py-0 text-sm" @change="addTo(p.id, $event)">
                        <option value="">+ adicionar</option>
                        <option v-for="m in candidates(p.id)" :key="m.id" :value="m.id">{{ candidateLabel(m) }}</option>
                    </select>
                </div>
            </div>
            <div>
                <label for="notes" class="label">Observação do culto</label>
                <textarea id="notes" v-model="team.notes" rows="2" class="input py-2" placeholder="Ex.: Santa Ceia, coral canta 1 música, ensaio sábado 16h"></textarea>
            </div>
        </div>
        <template #footer>
            <UiButton variant="secondary" @click="editSheet = false">Cancelar</UiButton>
            <UiButton :loading="team.processing" @click="saveService">Salvar domingo</UiButton>
        </template>
    </UiSheet>

    <!-- Disponibilidade de outra pessoa -->
    <UiSheet :open="otherSheet" :title="memberName(other.member_id)" subtitle="Toque nos dias em que essa pessoa não pode." @close="otherSheet = false">
        <div class="grid grid-cols-2 gap-2">
            <button v-for="s in services" :key="s.id" type="button" @click="toggleOther(s.id)"
                    :class="['flex min-h-14 flex-col items-center justify-center rounded-xl border-2 text-sm font-semibold',
                        other.unavailable.includes(s.id) ? 'border-red-400 bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300' : 'border-green-300 bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300']">
                {{ s.date }}
                <span class="text-xs font-medium">{{ other.unavailable.includes(s.id) ? '🚫 Não pode' : '✓ Pode' }}</span>
            </button>
        </div>
        <template #footer>
            <UiButton variant="secondary" @click="otherSheet = false">Cancelar</UiButton>
            <UiButton :loading="other.processing" @click="saveOther">Salvar</UiButton>
        </template>
    </UiSheet>
</template>

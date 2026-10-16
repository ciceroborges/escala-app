<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import UiButton from '@/Components/UiButton.vue';
import UiSheet from '@/Components/UiSheet.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    service: Object,
    team: Array,
    vocalists: Array,
    setlist: Array,
    songs: Array,
});

const page = usePage();
const canEdit = computed(() => page.props.can.manageRepertoire);
const keyText = (s) => (s.musical_key ? (s.starting_key ? `${s.musical_key}, mas começa em ${s.starting_key}` : s.musical_key) : null);

// Adicionar música
const addSheet = ref(false);
const search = ref('');
const add = useForm({ song_id: null, singers: [], notes: '' });
const results = computed(() => {
    const q = search.value.trim().toLowerCase();
    const used = props.setlist.map((i) => i.song.id);
    return props.songs.filter((s) => !used.includes(s.id) && (!q || s.title.toLowerCase().includes(q))).slice(0, 30);
});
const pick = (song) => {
    add.song_id = song.id;
    // sugere quem já tem tom cadastrado e está escalado
    add.singers = props.vocalists.filter((v) => song.singer_ids.includes(v.id)).slice(0, 1).map((v) => v.id);
};
const selectedSong = computed(() => props.songs.find((s) => s.id === add.song_id));
const toggleSinger = (id) => {
    const i = add.singers.indexOf(id);
    i === -1 ? add.singers.push(id) : add.singers.splice(i, 1);
};
const openAdd = () => {
    add.reset();
    search.value = '';
    addSheet.value = true;
};
const saveAdd = () => add.post(route('setlist.store', props.service.id), { preserveScroll: true, onSuccess: () => (addSheet.value = false) });

// Editar item
const editSheet = ref(false);
const editingItem = ref(null);
const edit = useForm({ singers: [], notes: '' });
const openEdit = (item) => {
    editingItem.value = item;
    edit.singers = item.singers.map((s) => ({ member_id: s.member_id, musical_key: s.musical_key ?? '', starting_key: s.starting_key ?? '' }));
    edit.notes = item.notes ?? '';
    edit.clearErrors();
    editSheet.value = true;
};
const vocalistName = (id) => props.vocalists.find((v) => v.id === id)?.name ?? editingItem.value?.singers.find((s) => s.member_id === id)?.name ?? '?';
const addEditSinger = (event) => {
    const id = Number(event.target.value);
    if (id) edit.singers.push({ member_id: id, musical_key: '', starting_key: '' });
    event.target.value = '';
};
const saveEdit = () => edit.put(route('setlist.update', [props.service.id, editingItem.value.id]), { preserveScroll: true, onSuccess: () => (editSheet.value = false) });
const removeItem = () => confirm('Tirar esta música do culto?')
    && router.delete(route('setlist.destroy', [props.service.id, editingItem.value.id]), { preserveScroll: true, onSuccess: () => (editSheet.value = false) });
const move = (item, direction) => router.post(route('setlist.move', [props.service.id, item.id]), { direction }, { preserveScroll: true });
</script>

<template>
    <Head :title="`${service.date} · Repertório`" />

    <Link :href="route('schedules.show', service.schedule_id)" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">← {{ service.schedule_label }}</Link>
    <h1 class="mt-1 text-xl font-bold sm:text-2xl">{{ service.date }} · {{ service.weekday }}</h1>
    <p v-if="service.notes" class="mt-1 text-sm text-gray-600 dark:text-gray-300">📝 {{ service.notes }}</p>

    <section v-if="team.length" class="card mt-5 p-4">
        <h2 class="mb-2 font-semibold">Equipe</h2>
        <dl class="space-y-1 text-sm">
            <div v-for="row in team" :key="row.position" class="flex gap-2">
                <dt class="w-28 shrink-0 text-gray-500 dark:text-gray-400">{{ row.position }}</dt>
                <dd>{{ row.people.join(', ') }}</dd>
            </div>
        </dl>
    </section>

    <section class="mt-6">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="font-semibold">Repertório</h2>
            <UiButton v-if="canEdit && setlist.length" small class="w-auto!" @click="openAdd">+ Música</UiButton>
        </div>

        <EmptyState v-if="!setlist.length" icon="🎶" title="Repertório ainda não definido"
                    :text="canEdit ? 'Escolha as músicas e quem puxa cada uma. O tom vem sozinho do cadastro.' : 'Assim que o líder montar, as músicas aparecem aqui.'">
            <UiButton v-if="canEdit" @click="openAdd">+ Adicionar música</UiButton>
        </EmptyState>

        <ol v-else class="space-y-2">
            <li v-for="(item, i) in setlist" :key="item.id" class="card p-4">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 w-5 shrink-0 text-center text-sm font-bold text-gray-400">{{ i + 1 }}</span>
                    <button type="button" class="min-w-0 flex-1 text-left" :class="!canEdit && 'cursor-default'" @click="canEdit && openEdit(item)">
                        <p class="font-semibold">{{ item.song.tag_emoji }} {{ item.song.title }}</p>
                        <p v-if="item.song.artist" class="text-xs text-gray-500">{{ item.song.artist }}</p>
                        <p v-for="s in item.singers" :key="s.member_id" class="mt-1 text-sm">
                            🎤 {{ s.name }}: <strong :class="!keyText(s) && 'text-amber-600'">{{ keyText(s) ?? 'tom?' }}</strong>
                        </p>
                        <p v-if="item.notes" class="mt-1 text-sm text-gray-600 dark:text-gray-300">📝 {{ item.notes }}</p>
                    </button>
                    <div class="flex shrink-0 flex-col items-end gap-1">
                        <div class="flex gap-1">
                            <a v-if="item.song.youtube_url" :href="item.song.youtube_url" target="_blank" rel="noopener" class="flex size-10 items-center justify-center rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-950" title="YouTube">▶</a>
                            <a v-if="item.song.chords_url" :href="item.song.chords_url" target="_blank" rel="noopener" class="flex size-10 items-center justify-center rounded-lg text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-950" title="Cifra">🎸</a>
                        </div>
                        <div v-if="canEdit" class="flex gap-1">
                            <button type="button" class="size-9 rounded-lg text-gray-400 hover:bg-gray-100 disabled:opacity-30 dark:hover:bg-gray-700" :disabled="i === 0" aria-label="Subir" @click="move(item, 'up')">↑</button>
                            <button type="button" class="size-9 rounded-lg text-gray-400 hover:bg-gray-100 disabled:opacity-30 dark:hover:bg-gray-700" :disabled="i === setlist.length - 1" aria-label="Descer" @click="move(item, 'down')">↓</button>
                        </div>
                    </div>
                </div>
            </li>
        </ol>
    </section>

    <!-- Adicionar música -->
    <UiSheet :open="addSheet" title="Adicionar música" :subtitle="selectedSong ? selectedSong.title : 'Escolha do repertório do ministério'" @close="addSheet = false">
        <template v-if="!selectedSong">
            <input v-model="search" type="search" class="input mb-3" placeholder="Buscar música" autofocus>
            <p v-if="!songs.length" class="hint">Nenhuma música cadastrada. <Link :href="route('songs.index')" class="text-brand-600">Cadastrar músicas →</Link></p>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                <li v-for="s in results" :key="s.id">
                    <button type="button" class="flex min-h-12 w-full items-center gap-2 py-2 text-left" @click="pick(s)">
                        <span class="w-6 text-center">{{ s.tag_emoji }}</span>
                        <span class="flex-1">{{ s.title }} <span v-if="s.artist" class="text-sm text-gray-400">· {{ s.artist }}</span></span>
                    </button>
                </li>
            </ul>
        </template>
        <template v-else>
            <p class="label">Quem puxa? <span class="font-normal text-gray-400">(vocais deste culto)</span></p>
            <p v-if="!vocalists.length" class="hint">Ninguém escalado no vocal ainda — dá para definir depois.</p>
            <div class="flex flex-wrap gap-2">
                <button v-for="v in vocalists" :key="v.id" type="button" @click="toggleSinger(v.id)"
                        :class="['min-h-10 rounded-full border px-3 text-sm font-medium',
                            add.singers.includes(v.id) ? 'border-brand-500 bg-brand-500 text-white' : 'border-gray-300 dark:border-gray-600']">
                    {{ v.name }}{{ selectedSong.singer_ids.includes(v.id) ? '' : ' (sem tom)' }}
                </button>
            </div>
            <label for="add-notes" class="label mt-4">Observação (opcional)</label>
            <input id="add-notes" v-model="add.notes" class="input" placeholder="Ex.: só o refrão, ministração no final">
            <button type="button" class="mt-4 text-sm text-gray-500" @click="add.song_id = null">← Escolher outra música</button>
        </template>
        <template #footer>
            <UiButton variant="secondary" @click="addSheet = false">Cancelar</UiButton>
            <UiButton :disabled="!add.song_id" :loading="add.processing" @click="saveAdd">Adicionar</UiButton>
        </template>
    </UiSheet>

    <!-- Editar item -->
    <UiSheet :open="editSheet" :title="editingItem?.song.title" subtitle="Vozes e tom para este culto" @close="editSheet = false">
        <div v-if="editingItem" class="space-y-4">
            <div v-for="(s, i) in edit.singers" :key="s.member_id" class="rounded-xl bg-gray-50 p-3 dark:bg-gray-900/60">
                <div class="mb-2 flex items-center justify-between">
                    <p class="font-medium">🎤 {{ vocalistName(s.member_id) }}</p>
                    <button type="button" class="text-sm text-red-600" @click="edit.singers.splice(i, 1)">Tirar</button>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input v-model="s.musical_key" class="input" placeholder="Tom" maxlength="4">
                    <input v-model="s.starting_key" class="input" placeholder="Começa em" maxlength="4">
                </div>
                <p v-if="edit.errors[`singers.${i}.musical_key`] || edit.errors[`singers.${i}.starting_key`]" class="error">
                    {{ edit.errors[`singers.${i}.musical_key`] || edit.errors[`singers.${i}.starting_key`] }}
                </p>
            </div>
            <select v-if="vocalists.some((v) => !edit.singers.some((s) => s.member_id === v.id))" class="input" @change="addEditSinger">
                <option value="">+ Adicionar voz</option>
                <option v-for="v in vocalists.filter((v) => !edit.singers.some((s) => s.member_id === v.id))" :key="v.id" :value="v.id">{{ v.name }}</option>
            </select>
            <div>
                <label for="edit-notes" class="label">Observação</label>
                <input id="edit-notes" v-model="edit.notes" class="input">
            </div>
            <p class="hint">Mudar o tom aqui vale só para este culto.</p>
        </div>
        <template #footer>
            <UiButton variant="ghost" class="text-red-600 sm:mr-auto" @click="removeItem">Tirar do culto</UiButton>
            <UiButton variant="secondary" @click="editSheet = false">Cancelar</UiButton>
            <UiButton :loading="edit.processing" @click="saveEdit">Salvar</UiButton>
        </template>
    </UiSheet>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PageHeader from '@/Components/PageHeader.vue';
import UiButton from '@/Components/UiButton.vue';
import UiField from '@/Components/UiField.vue';
import UiSheet from '@/Components/UiSheet.vue';
import EmptyState from '@/Components/EmptyState.vue';
import HelpTip from '@/Components/HelpTip.vue';

const props = defineProps({
    songs: Array,
    categories: Object,
    singers: Array,
});

const page = usePage();
const canEdit = computed(() => page.props.can.manageRepertoire);
const search = ref('');
const tagFilter = ref(null);
const sheetOpen = ref(false);
const editing = ref(null);

const options = computed(() => [...(props.categories.primary?.options ?? []), ...(props.categories.secondary?.options ?? [])]);
const option = (id) => options.value.find((o) => o.id === id);
const singerName = (id) => props.singers.find((s) => s.id === id)?.name ?? 'Cantor removido';

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.songs.filter((s) =>
        (!q || s.title.toLowerCase().includes(q) || (s.artist ?? '').toLowerCase().includes(q))
        && (!tagFilter.value || s.primary_option_id === tagFilter.value));
});

const keyText = (k) => (k.musical_key ? (k.starting_key ? `${k.musical_key}, começa em ${k.starting_key}` : k.musical_key) : 'tom?');

const blank = () => ({ title: '', artist: '', youtube_url: '', chords_url: '', primary_option_id: null, secondary_option_id: null, keys: [] });
const form = useForm(blank());

const open = (song = null) => {
    if (!canEdit.value) return;
    editing.value = song;
    form.defaults(song ? {
        ...blank(), ...song,
        artist: song.artist ?? '', youtube_url: song.youtube_url ?? '', chords_url: song.chords_url ?? '',
        keys: song.keys.map((k) => ({ ...k, musical_key: k.musical_key ?? '', starting_key: k.starting_key ?? '' })),
    } : blank()).reset();
    form.clearErrors();
    sheetOpen.value = true;
};

const addKey = () => form.keys.push({ member_id: '', musical_key: '', starting_key: '' });
const availableSingers = (current) => props.singers.filter((s) => s.id === current || !form.keys.some((k) => k.member_id === s.id));

const youtubeSearch = computed(() => `https://www.youtube.com/results?search_query=${encodeURIComponent(`${form.title} ${form.artist}`.trim())}`);
const chordsSearch = computed(() => `https://www.cifraclub.com.br/?q=${encodeURIComponent(`${form.title} ${form.artist}`.trim())}`);

const save = () => {
    const opts = { preserveScroll: true, onSuccess: () => (sheetOpen.value = false) };
    editing.value ? form.put(route('songs.update', editing.value.id), opts) : form.post(route('songs.store'), opts);
};
const remove = () => {
    if (!confirm(`Remover "${editing.value.title}" do repertório?`)) return;
    router.delete(route('songs.destroy', editing.value.id), { preserveScroll: true, onSuccess: () => (sheetOpen.value = false) });
};
</script>

<template>
    <Head title="Músicas" />

    <PageHeader title="Músicas" :subtitle="`${songs.length} no repertório`">
        <UiButton v-if="canEdit" @click="open()">+ Nova música</UiButton>
    </PageHeader>

    <HelpTip v-if="canEdit" id="songs-intro">
        Para cada música, diga o <strong>tom de cada cantor</strong>. Na hora de montar o repertório do culto,
        o tom já vem preenchido de acordo com quem vai cantar.
    </HelpTip>

    <EmptyState v-if="!songs.length" icon="🎼" title="Nenhuma música ainda"
                :text="canEdit ? 'Comece pelas que vocês mais tocam. Nome, tom de quem canta e o link do YouTube já bastam.' : 'O líder ainda não cadastrou o repertório.'">
        <UiButton v-if="canEdit" @click="open()">+ Cadastrar a primeira música</UiButton>
    </EmptyState>

    <template v-else>
        <input v-model="search" type="search" class="input mb-3" placeholder="Buscar música ou artista">
        <div v-if="categories.primary?.options?.length" class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1">
            <button type="button" :class="['min-h-9 shrink-0 rounded-full border px-3 text-sm', !tagFilter ? 'border-brand-500 bg-brand-500 text-white' : 'border-gray-300 dark:border-gray-600']" @click="tagFilter = null">Todas</button>
            <button v-for="o in categories.primary.options" :key="o.id" type="button"
                    :class="['min-h-9 shrink-0 rounded-full border px-3 text-sm', tagFilter === o.id ? 'border-brand-500 bg-brand-500 text-white' : 'border-gray-300 dark:border-gray-600']"
                    @click="tagFilter = tagFilter === o.id ? null : o.id">{{ o.emoji }} {{ o.name }}</button>
        </div>

        <ul class="space-y-2">
            <li v-for="s in filtered" :key="s.id" class="card p-4">
                <div class="flex items-start gap-3">
                    <button type="button" class="min-w-0 flex-1 text-left" :class="!canEdit && 'cursor-default'" @click="open(s)">
                        <p class="font-semibold">
                            <span v-if="option(s.primary_option_id)?.emoji">{{ option(s.primary_option_id).emoji }} </span>{{ s.title }}
                        </p>
                        <p v-if="s.artist || option(s.secondary_option_id)" class="text-sm text-gray-500 dark:text-gray-400">
                            {{ [s.artist, option(s.secondary_option_id)?.name].filter(Boolean).join(' · ') }}
                        </p>
                        <p v-if="s.keys.length" class="mt-1 text-sm">
                            <span v-for="(k, i) in s.keys" :key="k.member_id">
                                {{ singerName(k.member_id) }}: <strong :class="!k.musical_key && 'text-amber-600'">{{ keyText(k) }}</strong><span v-if="i < s.keys.length - 1"> · </span>
                            </span>
                        </p>
                    </button>
                    <div class="flex shrink-0 gap-1">
                        <a v-if="s.youtube_url" :href="s.youtube_url" target="_blank" rel="noopener" class="flex size-10 items-center justify-center rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-950" title="YouTube">▶</a>
                        <a v-if="s.chords_url" :href="s.chords_url" target="_blank" rel="noopener" class="flex size-10 items-center justify-center rounded-lg text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-950" title="Cifra">🎸</a>
                    </div>
                </div>
            </li>
        </ul>
        <p v-if="!filtered.length" class="py-8 text-center text-sm text-gray-500">Nada encontrado.</p>
    </template>

    <UiSheet :open="sheetOpen" :title="editing ? editing.title : 'Nova música'" @close="sheetOpen = false">
        <form id="song-form" class="space-y-4" @submit.prevent="save">
            <UiField label="Nome da música" :error="form.errors.title" id="song-title">
                <input id="song-title" v-model="form.title" class="input" placeholder="Filho do Deus Vivo" required>
            </UiField>
            <UiField label="Artista (opcional)" :error="form.errors.artist" id="song-artist">
                <input id="song-artist" v-model="form.artist" class="input" placeholder="Drops INA">
            </UiField>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <UiField v-if="categories.primary" :label="categories.primary.name">
                    <select v-model="form.primary_option_id" class="input">
                        <option :value="null">—</option>
                        <option v-for="o in categories.primary.options" :key="o.id" :value="o.id">{{ o.emoji }} {{ o.name }}</option>
                    </select>
                </UiField>
                <UiField v-if="categories.secondary" :label="categories.secondary.name">
                    <select v-model="form.secondary_option_id" class="input">
                        <option :value="null">—</option>
                        <option v-for="o in categories.secondary.options" :key="o.id" :value="o.id">{{ o.emoji }} {{ o.name }}</option>
                    </select>
                </UiField>
            </div>

            <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-900/60">
                <p class="label">Tom de cada cantor</p>
                <p v-if="!singers.length" class="hint">Ninguém da equipe tem a função de vocal ainda.</p>
                <div v-for="(k, i) in form.keys" :key="i" class="mb-3 space-y-2 border-b border-gray-200 pb-3 last:border-0 dark:border-gray-700">
                    <div class="flex gap-2">
                        <select v-model="k.member_id" class="input" required>
                            <option value="" disabled>Quem canta?</option>
                            <option v-for="s in availableSingers(k.member_id)" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <button type="button" class="size-11 shrink-0 rounded-lg text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700" aria-label="Remover" @click="form.keys.splice(i, 1)">✕</button>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <input v-model="k.musical_key" class="input" placeholder="Tom (ex.: G)" maxlength="4" autocapitalize="characters">
                        <input v-model="k.starting_key" class="input" placeholder="Começa em (opcional)" maxlength="4">
                    </div>
                    <p v-if="form.errors[`keys.${i}.musical_key`] || form.errors[`keys.${i}.starting_key`]" class="error">
                        {{ form.errors[`keys.${i}.musical_key`] || form.errors[`keys.${i}.starting_key`] }}
                    </p>
                </div>
                <button v-if="singers.length > form.keys.length" type="button" class="text-sm font-semibold text-brand-600 dark:text-brand-400" @click="addKey">+ Adicionar cantor</button>
                <p class="hint">"G, mas começa em C" → Tom G, começa em C.</p>
            </div>

            <UiField label="Link do YouTube" :error="form.errors.youtube_url" id="song-yt">
                <input id="song-yt" v-model="form.youtube_url" type="url" class="input" placeholder="https://youtube.com/…">
                <a v-if="form.title && !form.youtube_url" :href="youtubeSearch" target="_blank" rel="noopener" class="hint inline-block text-brand-600 dark:text-brand-400">Procurar no YouTube ↗</a>
            </UiField>
            <UiField label="Link da cifra" :error="form.errors.chords_url" id="song-chords">
                <input id="song-chords" v-model="form.chords_url" type="url" class="input" placeholder="https://www.cifraclub.com.br/…">
                <a v-if="form.title && !form.chords_url" :href="chordsSearch" target="_blank" rel="noopener" class="hint inline-block text-brand-600 dark:text-brand-400">Procurar no Cifra Club ↗</a>
            </UiField>
        </form>
        <template #footer>
            <UiButton v-if="editing" variant="ghost" class="text-red-600 sm:mr-auto" @click="remove">Remover</UiButton>
            <UiButton variant="secondary" @click="sheetOpen = false">Cancelar</UiButton>
            <UiButton type="submit" form="song-form" :loading="form.processing">Salvar</UiButton>
        </template>
    </UiSheet>
</template>

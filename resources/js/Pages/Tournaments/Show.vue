<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    tournament: Object,
    entries: Array,
    rounds: Object,
    standings: Array,
    isRegistered: Boolean,
    isOrganizer: Boolean,
    canJoin: Boolean,
});

const joinForm = useForm({ partner_id: null });
const leaveForm = useForm({});
const startForm = useForm({});

// Partner search state
const partnerQuery = ref('');
const partnerResults = ref([]);
const selectedPartner = ref(null);
const showResults = ref(false);
const searching = ref(false);
let searchTimeout = null;

watch(partnerQuery, (value) => {
    clearTimeout(searchTimeout);
    if (value.length < 3) {
        partnerResults.value = [];
        showResults.value = false;
        return;
    }
    searching.value = true;
    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(route('users.search') + '?q=' + encodeURIComponent(value));
            if (response.ok) {
                partnerResults.value = await response.json();
                showResults.value = partnerResults.value.length > 0;
            }
        } catch (e) {
            partnerResults.value = [];
        } finally {
            searching.value = false;
        }
    }, 300);
});

const selectPartner = (user) => {
    selectedPartner.value = user;
    joinForm.partner_id = user.id;
    partnerQuery.value = '';
    partnerResults.value = [];
    showResults.value = false;
};

const clearPartner = () => {
    selectedPartner.value = null;
    joinForm.partner_id = null;
    partnerQuery.value = '';
};

const formatLabel = (value) => {
    if (!value) return '';
    return value.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
};

const join = () => joinForm.post(route('tournaments.join', props.tournament.id));
const leave = () => leaveForm.delete(route('tournaments.leave', props.tournament.id));

const startTournament = () => {
    if (confirm('Start the tournament? This will generate the bracket and close registration. This cannot be undone.')) {
        startForm.post(route('tournaments.start', props.tournament.id));
    }
};

const setWinner = (roundId, winnerEntryId) => {
    router.post(route('tournaments.set-winner', [props.tournament.id, roundId]), {
        winner_entry_id: winnerEntryId,
    });
};

const joinDisabled = () => {
    if (joinForm.processing) return true;
    if (props.tournament.players_count >= props.tournament.max_players) return true;
    if (props.tournament.is_doubles && !joinForm.partner_id) return true;
    return false;
};

const isInProgress = computed(() => props.tournament.status === 'in_progress');
const isCompleted = computed(() => props.tournament.status === 'completed');
const isRegistration = computed(() => props.tournament.status === 'registration');
const hasBracket = computed(() => isInProgress.value || isCompleted.value);

// Get max round number for a bracket side
const maxRound = (bracket) => {
    if (!props.rounds[bracket]) return 0;
    return Math.max(...Object.keys(props.rounds[bracket]).map(Number));
};
</script>

<template>
    <Head :title="tournament.name" />

    <AuthenticatedLayout :back-href="route('tournaments.index')">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ tournament.name }}</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

                <!-- Tournament Info Bar -->
                <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">
                                    {{ tournament.format_label ?? formatLabel(tournament.format) }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800">
                                    {{ formatLabel(tournament.bracket_type) }}
                                </span>
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': isRegistration,
                                        'bg-green-100 text-green-800': isInProgress,
                                        'bg-gray-100 text-gray-800': isCompleted,
                                    }"
                                >
                                    {{ formatLabel(tournament.status) }}
                                </span>
                                <span class="text-sm text-gray-500">
                                    {{ tournament.players_count }}/{{ tournament.max_players }} players
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span v-if="tournament.city || tournament.state" class="text-sm text-gray-500">
                                    {{ [tournament.city, tournament.state].filter(Boolean).join(', ') }}
                                </span>
                                <span class="text-sm text-gray-500">
                                    {{ new Date(tournament.starts_at).toLocaleDateString() }}
                                </span>
                            </div>
                        </div>
                        <p v-if="tournament.description" class="mt-3 text-sm text-gray-600">{{ tournament.description }}</p>
                    </div>
                </div>

                <!-- Start Tournament Button (Organizer Only) -->
                <div v-if="isOrganizer && isRegistration" class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between p-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Ready to start?</h3>
                            <p class="text-sm text-gray-500">{{ tournament.players_count }} players registered. Starting will generate the bracket randomly.</p>
                        </div>
                        <PrimaryButton @click="startTournament" :disabled="startForm.processing || tournament.players_count < 2">
                            Start Tournament
                        </PrimaryButton>
                    </div>
                    <p v-if="startForm.errors?.tournament" class="px-6 pb-4 text-sm text-red-600">{{ startForm.errors.tournament }}</p>
                </div>

                <!-- ═══ BRACKET VIEW ═══ -->
                <div v-if="hasBracket">

                    <!-- Single Elimination Bracket -->
                    <div v-if="tournament.bracket_type === 'single_elimination' && rounds.winners" class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Bracket</h3>
                            <div class="overflow-x-auto">
                                <div class="flex gap-8" :style="{ minWidth: (maxRound('winners') * 220) + 'px' }">
                                    <div v-for="roundNum in maxRound('winners')" :key="roundNum" class="flex w-48 shrink-0 flex-col">
                                        <div class="mb-3 text-center text-xs font-semibold uppercase text-gray-400">
                                            {{ roundNum === maxRound('winners') ? 'Final' : roundNum === maxRound('winners') - 1 ? 'Semifinal' : 'Round ' + roundNum }}
                                        </div>
                                        <div class="flex flex-1 flex-col justify-around gap-4">
                                            <div
                                                v-for="match in (rounds.winners[roundNum] || [])"
                                                :key="match.id"
                                                class="rounded-lg border border-gray-200 bg-gray-50 overflow-hidden"
                                            >
                                                <!-- Entry 1 -->
                                                <div
                                                    class="flex items-center justify-between border-b border-gray-200 px-3 py-2 text-sm"
                                                    :class="{
                                                        'bg-green-50 font-semibold text-green-800': match.winner_entry_id === match.entry_1?.id,
                                                        'text-gray-400 line-through': match.winner_entry_id && match.winner_entry_id !== match.entry_1?.id,
                                                    }"
                                                >
                                                    <span>{{ match.entry_1?.name || 'TBD' }}</span>
                                                    <button
                                                        v-if="isOrganizer && isInProgress && !match.winner_entry_id && match.entry_1 && match.entry_2"
                                                        @click="setWinner(match.id, match.entry_1.id)"
                                                        class="ml-2 rounded bg-indigo-600 px-2 py-0.5 text-xs text-white hover:bg-indigo-700"
                                                        title="Set as winner"
                                                    >W</button>
                                                </div>
                                                <!-- Entry 2 -->
                                                <div
                                                    class="flex items-center justify-between px-3 py-2 text-sm"
                                                    :class="{
                                                        'bg-green-50 font-semibold text-green-800': match.winner_entry_id === match.entry_2?.id,
                                                        'text-gray-400 line-through': match.winner_entry_id && match.winner_entry_id !== match.entry_2?.id,
                                                    }"
                                                >
                                                    <span>{{ match.entry_2?.name || (match.entry_1 && !match.entry_2 ? 'BYE' : 'TBD') }}</span>
                                                    <button
                                                        v-if="isOrganizer && isInProgress && !match.winner_entry_id && match.entry_1 && match.entry_2"
                                                        @click="setWinner(match.id, match.entry_2.id)"
                                                        class="ml-2 rounded bg-indigo-600 px-2 py-0.5 text-xs text-white hover:bg-indigo-700"
                                                        title="Set as winner"
                                                    >W</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Double Elimination Bracket -->
                    <template v-if="tournament.bracket_type === 'double_elimination'">
                        <!-- Winners Bracket -->
                        <div v-if="rounds.winners" class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Winners Bracket</h3>
                                <div class="overflow-x-auto">
                                    <div class="flex gap-8" :style="{ minWidth: (maxRound('winners') * 220) + 'px' }">
                                        <div v-for="roundNum in maxRound('winners')" :key="roundNum" class="flex w-48 shrink-0 flex-col">
                                            <div class="mb-3 text-center text-xs font-semibold uppercase text-gray-400">Round {{ roundNum }}</div>
                                            <div class="flex flex-1 flex-col justify-around gap-4">
                                                <div
                                                    v-for="match in (rounds.winners[roundNum] || [])"
                                                    :key="match.id"
                                                    class="rounded-lg border border-gray-200 bg-gray-50 overflow-hidden"
                                                >
                                                    <div
                                                        class="flex items-center justify-between border-b border-gray-200 px-3 py-2 text-sm"
                                                        :class="{
                                                            'bg-green-50 font-semibold text-green-800': match.winner_entry_id === match.entry_1?.id,
                                                            'text-gray-400 line-through': match.winner_entry_id && match.winner_entry_id !== match.entry_1?.id,
                                                        }"
                                                    >
                                                        <span>{{ match.entry_1?.name || 'TBD' }}</span>
                                                        <button
                                                            v-if="isOrganizer && isInProgress && !match.winner_entry_id && match.entry_1 && match.entry_2"
                                                            @click="setWinner(match.id, match.entry_1.id)"
                                                            class="ml-2 rounded bg-indigo-600 px-2 py-0.5 text-xs text-white hover:bg-indigo-700"
                                                        >W</button>
                                                    </div>
                                                    <div
                                                        class="flex items-center justify-between px-3 py-2 text-sm"
                                                        :class="{
                                                            'bg-green-50 font-semibold text-green-800': match.winner_entry_id === match.entry_2?.id,
                                                            'text-gray-400 line-through': match.winner_entry_id && match.winner_entry_id !== match.entry_2?.id,
                                                        }"
                                                    >
                                                        <span>{{ match.entry_2?.name || (match.entry_1 && !match.entry_2 ? 'BYE' : 'TBD') }}</span>
                                                        <button
                                                            v-if="isOrganizer && isInProgress && !match.winner_entry_id && match.entry_1 && match.entry_2"
                                                            @click="setWinner(match.id, match.entry_2.id)"
                                                            class="ml-2 rounded bg-indigo-600 px-2 py-0.5 text-xs text-white hover:bg-indigo-700"
                                                        >W</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Losers Bracket -->
                        <div v-if="rounds.losers" class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Losers Bracket</h3>
                                <div class="overflow-x-auto">
                                    <div class="flex gap-8" :style="{ minWidth: (maxRound('losers') * 220) + 'px' }">
                                        <div v-for="roundNum in maxRound('losers')" :key="roundNum" class="flex w-48 shrink-0 flex-col">
                                            <div class="mb-3 text-center text-xs font-semibold uppercase text-gray-400">Round {{ roundNum }}</div>
                                            <div class="flex flex-1 flex-col justify-around gap-4">
                                                <div
                                                    v-for="match in (rounds.losers[roundNum] || [])"
                                                    :key="match.id"
                                                    class="rounded-lg border border-gray-200 bg-gray-50 overflow-hidden"
                                                >
                                                    <div
                                                        class="flex items-center justify-between border-b border-gray-200 px-3 py-2 text-sm"
                                                        :class="{
                                                            'bg-green-50 font-semibold text-green-800': match.winner_entry_id === match.entry_1?.id,
                                                            'text-gray-400 line-through': match.winner_entry_id && match.winner_entry_id !== match.entry_1?.id,
                                                        }"
                                                    >
                                                        <span>{{ match.entry_1?.name || 'TBD' }}</span>
                                                        <button
                                                            v-if="isOrganizer && isInProgress && !match.winner_entry_id && match.entry_1 && match.entry_2"
                                                            @click="setWinner(match.id, match.entry_1.id)"
                                                            class="ml-2 rounded bg-indigo-600 px-2 py-0.5 text-xs text-white hover:bg-indigo-700"
                                                        >W</button>
                                                    </div>
                                                    <div
                                                        class="flex items-center justify-between px-3 py-2 text-sm"
                                                        :class="{
                                                            'bg-green-50 font-semibold text-green-800': match.winner_entry_id === match.entry_2?.id,
                                                            'text-gray-400 line-through': match.winner_entry_id && match.winner_entry_id !== match.entry_2?.id,
                                                        }"
                                                    >
                                                        <span>{{ match.entry_2?.name || 'TBD' }}</span>
                                                        <button
                                                            v-if="isOrganizer && isInProgress && !match.winner_entry_id && match.entry_1 && match.entry_2"
                                                            @click="setWinner(match.id, match.entry_2.id)"
                                                            class="ml-2 rounded bg-indigo-600 px-2 py-0.5 text-xs text-white hover:bg-indigo-700"
                                                        >W</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Grand Finals -->
                        <div v-if="rounds.finals" class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Grand Finals</h3>
                                <div class="mx-auto max-w-xs">
                                    <div
                                        v-for="match in (rounds.finals[1] || [])"
                                        :key="match.id"
                                        class="rounded-lg border-2 border-yellow-400 bg-yellow-50 overflow-hidden"
                                    >
                                        <div
                                            class="flex items-center justify-between border-b border-yellow-300 px-4 py-3 text-sm"
                                            :class="{
                                                'bg-green-50 font-semibold text-green-800': match.winner_entry_id === match.entry_1?.id,
                                                'text-gray-400 line-through': match.winner_entry_id && match.winner_entry_id !== match.entry_1?.id,
                                            }"
                                        >
                                            <span>{{ match.entry_1?.name || 'TBD' }} <span v-if="match.entry_1" class="text-xs text-gray-400">(W)</span></span>
                                            <button
                                                v-if="isOrganizer && isInProgress && !match.winner_entry_id && match.entry_1 && match.entry_2"
                                                @click="setWinner(match.id, match.entry_1.id)"
                                                class="ml-2 rounded bg-indigo-600 px-2 py-0.5 text-xs text-white hover:bg-indigo-700"
                                            >W</button>
                                        </div>
                                        <div
                                            class="flex items-center justify-between px-4 py-3 text-sm"
                                            :class="{
                                                'bg-green-50 font-semibold text-green-800': match.winner_entry_id === match.entry_2?.id,
                                                'text-gray-400 line-through': match.winner_entry_id && match.winner_entry_id !== match.entry_2?.id,
                                            }"
                                        >
                                            <span>{{ match.entry_2?.name || 'TBD' }} <span v-if="match.entry_2" class="text-xs text-gray-400">(L)</span></span>
                                            <button
                                                v-if="isOrganizer && isInProgress && !match.winner_entry_id && match.entry_1 && match.entry_2"
                                                @click="setWinner(match.id, match.entry_2.id)"
                                                class="ml-2 rounded bg-indigo-600 px-2 py-0.5 text-xs text-white hover:bg-indigo-700"
                                            >W</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Round Robin -->
                    <div v-if="tournament.bracket_type === 'round_robin'" class="mb-6 space-y-6">
                        <!-- Matches Grid -->
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Matches</h3>
                                <div class="space-y-3">
                                    <div
                                        v-for="match in (rounds.winners?.[1] || [])"
                                        :key="match.id"
                                        class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3"
                                    >
                                        <div class="flex items-center gap-3 text-sm">
                                            <span :class="{ 'font-semibold text-green-700': match.winner_entry_id === match.entry_1?.id }">
                                                {{ match.entry_1?.name || 'TBD' }}
                                            </span>
                                            <span class="text-gray-400">vs</span>
                                            <span :class="{ 'font-semibold text-green-700': match.winner_entry_id === match.entry_2?.id }">
                                                {{ match.entry_2?.name || 'TBD' }}
                                            </span>
                                        </div>
                                        <div v-if="!match.winner_entry_id && isOrganizer && isInProgress" class="flex gap-2">
                                            <button
                                                @click="setWinner(match.id, match.entry_1.id)"
                                                class="rounded bg-indigo-600 px-3 py-1 text-xs text-white hover:bg-indigo-700"
                                            >{{ match.entry_1?.name?.split(' ')[0] }} wins</button>
                                            <button
                                                @click="setWinner(match.id, match.entry_2.id)"
                                                class="rounded bg-indigo-600 px-3 py-1 text-xs text-white hover:bg-indigo-700"
                                            >{{ match.entry_2?.name?.split(' ')[0] }} wins</button>
                                        </div>
                                        <span v-else-if="match.winner_entry_id" class="text-xs text-green-600">Completed</span>
                                        <span v-else class="text-xs text-gray-400">Pending</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Standings Table -->
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Standings</h3>
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200 text-left text-xs font-semibold uppercase text-gray-500">
                                            <th class="pb-2 pr-4">#</th>
                                            <th class="pb-2 pr-4">Player</th>
                                            <th class="pb-2 pr-4 text-center">W</th>
                                            <th class="pb-2 text-center">L</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(standing, index) in standings" :key="standing.entry_id" class="border-b border-gray-100">
                                            <td class="py-2 pr-4 font-medium text-gray-500">{{ index + 1 }}</td>
                                            <td class="py-2 pr-4">{{ standing.name }}</td>
                                            <td class="py-2 pr-4 text-center font-semibold text-green-600">{{ standing.wins }}</td>
                                            <td class="py-2 text-center text-red-600">{{ standing.losses }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Champion Banner -->
                    <div v-if="isCompleted" class="mb-6 overflow-hidden rounded-lg border-2 border-yellow-400 bg-yellow-50 shadow-sm">
                        <div class="p-6 text-center">
                            <div class="text-2xl font-bold text-yellow-800">Tournament Complete!</div>
                            <div v-for="entry in entries.filter(e => e.status === 'winner')" :key="entry.id" class="mt-2 text-lg text-yellow-700">
                                Champion: {{ entry.user.name }}<template v-if="entry.partner"> & {{ entry.partner.name }}</template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══ REGISTRATION VIEW ═══ -->
                <div v-if="isRegistration" class="grid gap-6 lg:grid-cols-3">
                    <!-- Participants -->
                    <div class="lg:col-span-2">
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900">
                                    Participants ({{ tournament.players_count }}/{{ tournament.max_players }})
                                </h3>
                                <ul v-if="entries.length" class="mt-4 divide-y divide-gray-200">
                                    <li v-for="entry in entries" :key="entry.id" class="flex items-center gap-3 py-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-medium text-indigo-700">
                                            {{ entry.user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <span class="text-sm text-gray-900">
                                            {{ entry.user.name }}
                                            <template v-if="entry.partner">
                                                <span class="text-gray-400">&</span> {{ entry.partner.name }}
                                            </template>
                                        </span>
                                    </li>
                                </ul>
                                <p v-else class="mt-4 text-sm text-gray-500">No participants yet. Be the first to join!</p>
                            </div>
                        </div>
                    </div>

                    <!-- Registration Sidebar -->
                    <div>
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900">Registration</h3>
                                <p class="mt-2 text-sm text-gray-500">
                                    {{ tournament.players_count }} of {{ tournament.max_players }} spots filled
                                </p>
                                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200">
                                    <div
                                        class="h-full rounded-full bg-indigo-600 transition-all"
                                        :style="{ width: Math.min(100, (tournament.players_count / tournament.max_players) * 100) + '%' }"
                                    />
                                </div>

                                <div class="mt-6">
                                    <div v-if="isRegistered" class="space-y-3">
                                        <div class="flex items-center gap-2 rounded-md bg-green-50 px-3 py-2">
                                            <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="text-sm font-medium text-green-800">Registered</span>
                                        </div>
                                        <button
                                            @click="leave"
                                            :disabled="leaveForm.processing"
                                            class="w-full rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50"
                                        >
                                            Leave Tournament
                                        </button>
                                    </div>
                                    <div v-else-if="canJoin" class="space-y-4">
                                        <!-- Partner Selection for Doubles -->
                                        <div v-if="tournament.is_doubles" class="relative">
                                            <InputLabel value="Partner (required)" />
                                            <p class="mt-0.5 text-xs text-gray-500">You must select a doubles partner before joining.</p>

                                            <div v-if="selectedPartner" class="mt-1 flex items-center justify-between rounded-md border border-gray-300 bg-gray-50 px-3 py-2">
                                                <div class="text-sm">
                                                    <span class="font-medium text-gray-900">{{ selectedPartner.name }}</span>
                                                    <span class="ml-1 text-gray-500">{{ selectedPartner.email }}</span>
                                                </div>
                                                <button @click="clearPartner" type="button" class="text-gray-400 hover:text-gray-600">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <div v-else>
                                                <TextInput
                                                    v-model="partnerQuery"
                                                    type="text"
                                                    class="mt-1 block w-full"
                                                    placeholder="Search by email address..."
                                                    @focus="showResults = partnerResults.length > 0"
                                                />
                                                <div
                                                    v-if="showResults"
                                                    class="absolute z-10 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg"
                                                >
                                                    <ul class="max-h-48 overflow-y-auto py-1">
                                                        <li
                                                            v-for="user in partnerResults"
                                                            :key="user.id"
                                                            @click="selectPartner(user)"
                                                            class="cursor-pointer px-3 py-2 hover:bg-gray-100"
                                                        >
                                                            <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                                            <div class="text-xs text-gray-500">{{ user.email }}</div>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <p v-if="searching" class="mt-1 text-xs text-gray-400">Searching...</p>
                                                <p v-else-if="partnerQuery.length > 0 && partnerQuery.length < 3" class="mt-1 text-xs text-gray-400">
                                                    Type at least 3 characters...
                                                </p>
                                            </div>
                                        </div>

                                        <PrimaryButton
                                            @click="join"
                                            :disabled="joinDisabled()"
                                            class="w-full justify-center"
                                        >
                                            <template v-if="tournament.players_count >= tournament.max_players">Tournament Full</template>
                                            <template v-else-if="tournament.is_doubles && !joinForm.partner_id">Select a Partner to Join</template>
                                            <template v-else>Join Tournament</template>
                                        </PrimaryButton>
                                    </div>
                                    <p v-else class="text-sm text-gray-500">Registration is not available.</p>
                                </div>

                                <p v-if="joinForm.errors.tournament" class="mt-3 text-sm text-red-600">{{ joinForm.errors.tournament }}</p>
                                <p v-if="joinForm.errors.partner_id" class="mt-3 text-sm text-red-600">{{ joinForm.errors.partner_id }}</p>
                                <p v-if="leaveForm.errors.tournament" class="mt-3 text-sm text-red-600">{{ leaveForm.errors.tournament }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Participants list for in-progress / completed -->
                <div v-if="hasBracket" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Participants</h3>
                        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                            <div
                                v-for="entry in entries"
                                :key="entry.id"
                                class="flex items-center gap-2 rounded-md px-3 py-2 text-sm"
                                :class="{
                                    'bg-yellow-50 text-yellow-800': entry.status === 'winner',
                                    'bg-red-50 text-red-400 line-through': entry.status === 'eliminated',
                                    'bg-gray-50 text-gray-700': entry.status === 'registered' || entry.status === 'checked_in',
                                }"
                            >
                                <span>{{ entry.user.name }}<template v-if="entry.partner"> & {{ entry.partner.name }}</template></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

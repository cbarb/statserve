<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DuprBadge from '@/Components/DuprBadge.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatCard from '@/Components/Stats/StatCard.vue';
import TimeRangeSelector from '@/Components/Stats/TimeRangeSelector.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    group: Object,
    members: Array,
    h2h: Object,
    player1: Number,
    player2: Number,
    range: String,
    canViewAllTime: Boolean,
});

const selectedPlayer1 = ref(props.player1);
const selectedPlayer2 = ref(props.player2);

const compare = () => {
    if (selectedPlayer1.value && selectedPlayer2.value && selectedPlayer1.value !== selectedPlayer2.value) {
        router.get(route('groups.stats.h2h', props.group.slug), {
            player1: selectedPlayer1.value,
            player2: selectedPlayer2.value,
            range: props.range,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

const playerName = (id) => props.members.find(m => m.id === id)?.name ?? 'Unknown';
</script>

<template>
    <Head :title="`${group.name} - Head to Head`" />

    <AuthenticatedLayout :back-href="route('groups.stats', group.slug)">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ group.name }} - Head to Head</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Player Selection -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Compare Players</h3>
                            <TimeRangeSelector :modelValue="range" :canViewAllTime="canViewAllTime" />
                        </div>
                        <div class="flex flex-wrap items-end gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Player 1</label>
                                <select v-model="selectedPlayer1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option :value="null" disabled>Select player</option>
                                    <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}{{ m.dupr_id ? ` (DUPR: ${m.dupr_id})` : '' }}</option>
                                </select>
                            </div>
                            <span class="pb-2 text-lg font-bold text-gray-400">vs</span>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Player 2</label>
                                <select v-model="selectedPlayer2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option :value="null" disabled>Select player</option>
                                    <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}{{ m.dupr_id ? ` (DUPR: ${m.dupr_id})` : '' }}</option>
                                </select>
                            </div>
                            <SecondaryButton @click="compare" :disabled="!selectedPlayer1 || !selectedPlayer2 || selectedPlayer1 === selectedPlayer2">
                                Compare
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Results -->
                <div v-if="h2h" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="h2h.games === 0" class="text-center text-sm text-gray-500">
                            These players haven't faced each other yet.
                        </div>

                        <template v-else>
                            <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
                                <StatCard label="Total Games" :value="h2h.games" />
                                <StatCard :label="playerName(player1) + ' Wins'" :value="h2h.player1_wins" />
                                <StatCard :label="playerName(player2) + ' Wins'" :value="h2h.player2_wins" />
                                <StatCard label="Point Diff" :value="`${h2h.player1_points} - ${h2h.player2_points}`" />
                            </div>

                            <!-- Format Breakdown -->
                            <div v-if="Object.keys(h2h.by_format).length" class="mt-6">
                                <h4 class="text-sm font-medium text-gray-700">By Format</h4>
                                <div class="mt-2 space-y-2">
                                    <div v-for="(data, format) in h2h.by_format" :key="format" class="flex items-center gap-4 text-sm">
                                        <span class="w-20 font-medium capitalize text-gray-900">{{ format }}</span>
                                        <span class="text-gray-500">{{ data.games }} games</span>
                                        <span class="text-gray-500">{{ playerName(player1) }}: {{ data.player1_wins }}W</span>
                                        <span class="text-gray-500">{{ playerName(player2) }}: {{ data.player2_wins }}W</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Matches -->
                            <div v-if="h2h.recent_matches.length" class="mt-6">
                                <h4 class="text-sm font-medium text-gray-700">Recent Matches</h4>
                                <div class="mt-2 divide-y divide-gray-100">
                                    <div v-for="match in h2h.recent_matches" :key="match.id" class="flex items-center justify-between py-2 text-sm">
                                        <span class="text-gray-500">{{ new Date(match.played_at).toLocaleDateString() }}</span>
                                        <span class="capitalize text-gray-400">{{ match.format }}</span>
                                        <span :class="match.player1_won ? 'font-medium text-green-600' : 'text-gray-500'">
                                            {{ match.player1_score }}
                                        </span>
                                        <span class="text-gray-300">-</span>
                                        <span :class="!match.player1_won ? 'font-medium text-green-600' : 'text-gray-500'">
                                            {{ match.player2_score }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

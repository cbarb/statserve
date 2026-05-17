<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DuprBadge from '@/Components/DuprBadge.vue';
import StatCard from '@/Components/Stats/StatCard.vue';
import TimeRangeSelector from '@/Components/Stats/TimeRangeSelector.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    group: Object,
    player: Object,
    stats: Object,
    bestPartner: Object,
    range: String,
    canViewAllTime: Boolean,
});

const formatStreak = (stats) => {
    if (!stats.current_streak) return '-';
    return `${stats.current_streak}${stats.current_streak_type}`;
};

const formatDiff = (val) => {
    if (val > 0) return `+${val}`;
    return String(val);
};
</script>

<template>
    <Head :title="`${group.name} - ${player.name}`" />

    <AuthenticatedLayout :back-href="route('groups.stats', group.slug)">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ player.name }} <DuprBadge :dupr-id="player.dupr_id" /></h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="flex justify-end">
                    <TimeRangeSelector :modelValue="range" :canViewAllTime="canViewAllTime" />
                </div>

                <!-- Overview Cards -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6">
                    <StatCard label="Games" :value="stats.games" />
                    <StatCard label="Record" :value="`${stats.wins}W - ${stats.losses}L`" />
                    <StatCard label="Win Rate" :value="`${stats.win_rate}%`" />
                    <StatCard label="Streak" :value="formatStreak(stats)" />
                    <StatCard label="Point Diff" :value="formatDiff(stats.point_diff)" :subtitle="`${stats.points_scored} scored / ${stats.points_conceded} conceded`" />
                    <StatCard label="Best Win Streak" :value="stats.longest_win_streak" />
                </div>

                <!-- Format Breakdown -->
                <div v-if="Object.keys(stats.by_format).length" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">By Format</h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div v-for="(data, format) in stats.by_format" :key="format" class="rounded-lg border border-gray-200 p-4">
                                <h4 class="font-medium capitalize text-gray-900">{{ format }}</h4>
                                <div class="mt-2 grid grid-cols-3 gap-2 text-sm">
                                    <div>
                                        <p class="text-gray-500">Games</p>
                                        <p class="font-medium">{{ data.games }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500">Record</p>
                                        <p class="font-medium">{{ data.wins }}W - {{ data.losses }}L</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500">Win%</p>
                                        <p class="font-medium">{{ data.games > 0 ? ((data.wins / data.games) * 100).toFixed(1) : 0 }}%</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Best Partner -->
                <div v-if="bestPartner" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">Best Partner</h3>
                        <div class="flex items-center gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-sm font-medium text-indigo-600">
                                {{ bestPartner.partner_name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ bestPartner.partner_name }} <DuprBadge :dupr-id="bestPartner.partner_dupr_id" /></p>
                                <p class="text-sm text-gray-500">
                                    {{ bestPartner.games }} games together &middot;
                                    {{ bestPartner.wins }}W - {{ bestPartner.losses }}L &middot;
                                    {{ bestPartner.win_rate }}% win rate
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Games State -->
                <div v-if="stats.games === 0" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-sm text-gray-500">
                        {{ player.name }} hasn't played any matches in this group yet.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

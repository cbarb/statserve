<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LeaderboardTable from '@/Components/Stats/LeaderboardTable.vue';
import TimeRangeSelector from '@/Components/Stats/TimeRangeSelector.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    group: Object,
    leaderboard: Array,
    range: String,
    canViewAllTime: Boolean,
});
</script>

<template>
    <Head :title="`${group.name} - Leaderboard`" />

    <AuthenticatedLayout :back-href="route('groups.show', group.slug)">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ group.name }} - Leaderboard</h2>
                <div class="flex items-center gap-2">
                    <Link :href="route('groups.stats.h2h', group.slug)">
                        <SecondaryButton>Head-to-Head</SecondaryButton>
                    </Link>
                    <Link :href="route('groups.stats.partnerships', group.slug)">
                        <SecondaryButton>Partnerships</SecondaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Leaderboard</h3>
                            <TimeRangeSelector :modelValue="range" :canViewAllTime="canViewAllTime" />
                        </div>
                        <LeaderboardTable :rows="leaderboard" :groupSlug="group.slug" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

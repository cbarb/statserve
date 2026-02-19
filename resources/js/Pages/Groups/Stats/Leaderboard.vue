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
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-base font-semibold leading-tight text-gray-800 sm:text-xl">{{ group.name }} - Leaderboard</h2>
                <div class="flex items-center gap-2">
                    <Link :href="route('groups.stats.h2h', group.slug)" class="flex-1 sm:flex-none">
                        <SecondaryButton class="w-full justify-center px-3 py-1.5 text-[10px] sm:px-4 sm:py-2 sm:text-xs">
                            Head-to-Head
                        </SecondaryButton>
                    </Link>
                    <Link :href="route('groups.stats.partnerships', group.slug)" class="flex-1 sm:flex-none">
                        <SecondaryButton class="w-full justify-center px-3 py-1.5 text-[10px] sm:px-4 sm:py-2 sm:text-xs">
                            Partnerships
                        </SecondaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6">
                        <div class="mb-4 flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
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

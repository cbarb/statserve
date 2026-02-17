<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RoleBadge from '@/Components/RoleBadge.vue';
import StatCard from '@/Components/Stats/StatCard.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
    groups: Array,
    personalStats: Object,
    levelData: Object,
    pinnedBadges: Array,
});

const TIER_COLORS = {
    bronze: 'bg-amber-100 text-amber-800 ring-amber-400',
    silver: 'bg-gray-100 text-gray-600 ring-gray-400',
    gold: 'bg-yellow-100 text-yellow-700 ring-yellow-500',
    platinum: 'bg-purple-100 text-purple-700 ring-purple-500',
};

const page = usePage();
const isPro = page.props.subscription?.is_pro;

const formatStreak = (stats) => {
    if (!stats.current_streak) return '-';
    return `${stats.current_streak}${stats.current_streak_type}`;
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Dashboard</h2>
                <Link :href="route('play.index')">
                    <PrimaryButton class="!bg-emerald-600 hover:!bg-emerald-500 focus:!bg-emerald-500 active:!bg-emerald-700 focus:!ring-emerald-500">Play</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- XP / Level Card -->
                <div v-if="levelData" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    Level {{ levelData.current_level }}
                                    <span class="ml-1 text-indigo-600">&mdash; {{ levelData.level_name }}</span>
                                </h3>
                                <p class="mt-0.5 text-sm text-gray-500">{{ levelData.total_xp.toLocaleString() }} total XP</p>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <span class="text-sm font-medium text-gray-700">
                                    {{ levelData.xp_current }} / {{ levelData.xp_needed }} XP
                                </span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="h-2.5 w-full rounded-full bg-gray-200">
                                <div
                                    class="h-2.5 rounded-full bg-indigo-600 transition-all duration-300"
                                    :style="{ width: Math.min(100, (levelData.xp_current / levelData.xp_needed) * 100) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pinned Badges -->
                <div v-if="pinnedBadges && pinnedBadges.length" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Pinned Badges</h3>
                            <Link :href="route('badges.index')" class="text-sm text-indigo-600 hover:text-indigo-500">
                                View All
                            </Link>
                        </div>
                        <div class="mt-3 flex gap-4">
                            <div
                                v-for="badge in pinnedBadges"
                                :key="badge.id"
                                class="flex items-center gap-2 rounded-lg border px-3 py-2"
                                :class="TIER_COLORS[badge.tier] || TIER_COLORS.bronze"
                            >
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full ring-2"
                                    :class="TIER_COLORS[badge.tier] || TIER_COLORS.bronze"
                                >
                                    <template v-if="badge.icon">{{ badge.icon }}</template>
                                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-sm font-semibold">{{ badge.name }}</span>
                                    <span class="ml-1 text-xs capitalize opacity-75">{{ badge.tier }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personal Stats -->
                <div v-if="personalStats.games > 0" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Your Stats (30 days)</h3>
                        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <StatCard label="Games" :value="personalStats.games" />
                            <StatCard label="Record" :value="`${personalStats.wins}W - ${personalStats.losses}L`" />
                            <StatCard label="Win Rate" :value="`${personalStats.win_rate}%`" />
                            <StatCard label="Streak" :value="formatStreak(personalStats)" />
                        </div>
                    </div>
                </div>
                <p v-if="personalStats.games > 0 && !isPro" class="px-2 text-sm text-gray-500">
                    Your stats are limited to 30 days.
                    <Link :href="route('billing.index')" class="text-emerald-600 hover:text-emerald-500">Unlock all-time stats with Pro &rarr;</Link>
                </p>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">My Groups</h3>
                            <Link :href="route('groups.index')" class="text-sm text-indigo-600 hover:text-indigo-500">
                                View All
                            </Link>
                        </div>

                        <div v-if="groups.length" class="mt-4 divide-y divide-gray-100">
                            <Link
                                v-for="group in groups"
                                :key="group.id"
                                :href="route('groups.show', group.slug)"
                                class="flex items-center justify-between py-3 hover:bg-gray-50 -mx-2 px-2 rounded"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="font-medium text-gray-900">{{ group.name }}</span>
                                    <RoleBadge :role="group.role" />
                                </div>
                                <span class="text-sm text-gray-500">
                                    {{ group.members_count }} {{ group.members_count === 1 ? 'member' : 'members' }}
                                </span>
                            </Link>
                        </div>

                        <div v-else class="mt-4 text-center">
                            <p class="text-gray-500">You're not in any groups yet.</p>
                            <Link :href="route('groups.create')" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-500">
                                Create your first group
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

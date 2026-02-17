<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    badges: Array,
});

const TIER_COLORS = {
    bronze: { bg: 'bg-amber-100', text: 'text-amber-800', border: 'border-amber-300', ring: 'ring-amber-400' },
    silver: { bg: 'bg-gray-100', text: 'text-gray-600', border: 'border-gray-300', ring: 'ring-gray-400' },
    gold: { bg: 'bg-yellow-100', text: 'text-yellow-700', border: 'border-yellow-400', ring: 'ring-yellow-500' },
    platinum: { bg: 'bg-purple-100', text: 'text-purple-700', border: 'border-purple-400', ring: 'ring-purple-500' },
};

const CATEGORY_LABELS = {
    wins: 'Wins',
    volume: 'Play Volume',
    social: 'Social',
    partnership: 'Partnership',
    secret: 'Secret',
};

const groupedBadges = computed(() => {
    const groups = {};
    for (const badge of props.badges) {
        const cat = badge.category;
        if (!groups[cat]) {
            groups[cat] = [];
        }
        groups[cat].push(badge);
    }
    return groups;
});

const pinnedCount = computed(() => props.badges.filter(b => b.is_pinned).length);

function togglePin(badge) {
    router.put(route('badges.pin', badge.id), {}, {
        preserveScroll: true,
    });
}

function tierColor(tier) {
    return TIER_COLORS[tier] || TIER_COLORS.bronze;
}

function progressPercent(badge) {
    if (badge.target === 0) return 100;
    return Math.min(100, Math.round((badge.current / badge.target) * 100));
}
</script>

<template>
    <Head title="Badges" />

    <AuthenticatedLayout :back-href="route('dashboard')">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Badges</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                <template v-for="(categoryBadges, category) in groupedBadges" :key="category">
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="mb-4 text-lg font-semibold text-gray-900">
                                {{ CATEGORY_LABELS[category] || category }}
                            </h3>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <div
                                    v-for="badge in categoryBadges"
                                    :key="badge.id"
                                    class="relative rounded-lg border p-4 transition-all"
                                    :class="[
                                        badge.earned
                                            ? `${tierColor(badge.tier).border} ${tierColor(badge.tier).bg}`
                                            : 'border-gray-200 bg-gray-50',
                                    ]"
                                >
                                    <!-- Pin button (earned only) -->
                                    <button
                                        v-if="badge.earned"
                                        @click="togglePin(badge)"
                                        class="absolute right-2 top-2 rounded-full p-1 transition-colors"
                                        :class="badge.is_pinned
                                            ? 'text-indigo-600 hover:text-indigo-800'
                                            : 'text-gray-400 hover:text-gray-600'"
                                        :title="badge.is_pinned ? 'Unpin badge' : (pinnedCount >= 3 ? 'Max 3 pinned badges' : 'Pin badge')"
                                    >
                                        <svg class="h-5 w-5" :fill="badge.is_pinned ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                        </svg>
                                    </button>

                                    <div class="flex items-start gap-3">
                                        <!-- Badge icon/placeholder -->
                                        <div
                                            class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full text-lg"
                                            :class="badge.earned
                                                ? `${tierColor(badge.tier).bg} ${tierColor(badge.tier).text} ring-2 ${tierColor(badge.tier).ring}`
                                                : 'bg-gray-200 text-gray-400'"
                                        >
                                            <template v-if="badge.is_secret && !badge.earned">?</template>
                                            <template v-else-if="badge.icon">{{ badge.icon }}</template>
                                            <template v-else>
                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                                </svg>
                                            </template>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <h4
                                                    class="font-medium"
                                                    :class="badge.earned ? 'text-gray-900' : 'text-gray-500'"
                                                >
                                                    {{ badge.name }}
                                                </h4>
                                                <span
                                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold capitalize"
                                                    :class="`${tierColor(badge.tier).bg} ${tierColor(badge.tier).text}`"
                                                >
                                                    {{ badge.tier }}
                                                </span>
                                            </div>

                                            <p class="mt-0.5 text-sm" :class="badge.earned ? 'text-gray-700' : 'text-gray-400'">
                                                {{ badge.description }}
                                            </p>

                                            <!-- Progress bar (unearned only) -->
                                            <div v-if="!badge.earned && !badge.is_secret" class="mt-2">
                                                <div class="flex items-center justify-between text-xs text-gray-500">
                                                    <span>{{ badge.current }} / {{ badge.target }}</span>
                                                    <span>{{ progressPercent(badge) }}%</span>
                                                </div>
                                                <div class="mt-1 h-1.5 w-full rounded-full bg-gray-200">
                                                    <div
                                                        class="h-1.5 rounded-full bg-indigo-500 transition-all duration-300"
                                                        :style="{ width: progressPercent(badge) + '%' }"
                                                    ></div>
                                                </div>
                                            </div>

                                            <!-- Earned date -->
                                            <p v-if="badge.earned && badge.earned_at" class="mt-1 text-xs text-gray-500">
                                                Earned {{ new Date(badge.earned_at).toLocaleDateString() }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-if="!badges.length" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-500">
                        No badges available yet. Start playing to earn badges!
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

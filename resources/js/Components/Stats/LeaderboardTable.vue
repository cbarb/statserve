<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import DuprBadge from '@/Components/DuprBadge.vue';

const props = defineProps({
    rows: Array,
    groupSlug: String,
});

const page = usePage();
const currentUserId = computed(() => page.props.auth.user.id);

const sortKey = ref('win_rate');
const sortDir = ref('desc');

const columns = [
    { key: 'rank', label: '#', sortable: false },
    { key: 'name', label: 'Player', sortable: true },
    { key: 'games', label: 'Games', sortable: true },
    { key: 'wins', label: 'W', sortable: true },
    { key: 'losses', label: 'L', sortable: true },
    { key: 'win_rate', label: 'Win%', sortable: true },
    { key: 'current_streak', label: 'Streak', sortable: true },
    { key: 'point_diff', label: 'Pt Diff', sortable: true },
];

const sortedRows = computed(() => {
    const sorted = [...props.rows].sort((a, b) => {
        let aVal = a[sortKey.value];
        let bVal = b[sortKey.value];
        if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }
        if (sortDir.value === 'asc') return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
    });
    return sorted.map((row, i) => ({ ...row, rank: i + 1 }));
});

const toggleSort = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'desc';
    }
};

const formatStreak = (row) => {
    if (!row.current_streak) return '-';
    return `${row.current_streak}${row.current_streak_type}`;
};

const formatDiff = (val) => {
    if (val > 0) return `+${val}`;
    return String(val);
};
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        :class="[
                            'px-2 py-2 text-left text-[10px] font-medium uppercase tracking-wider text-gray-500 sm:px-3 sm:text-xs',
                            col.sortable ? 'cursor-pointer select-none hover:text-gray-700' : '',
                        ]"
                        @click="col.sortable && toggleSort(col.key)"
                    >
                        {{ col.label }}
                        <span v-if="col.sortable && sortKey === col.key" class="ml-1">
                            {{ sortDir === 'asc' ? '&#9650;' : '&#9660;' }}
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr
                    v-for="row in sortedRows"
                    :key="row.user_id"
                    :class="row.user_id === currentUserId ? 'bg-indigo-50' : 'hover:bg-gray-50'"
                >
                    <td class="whitespace-nowrap px-2 py-2 text-gray-500 sm:px-3">{{ row.rank }}</td>
                    <td class="px-2 py-2 font-medium text-gray-900 sm:px-3">
                        <div class="gap-0.5 sm:flex-row sm:items-center sm:gap-1.5">
                            <Link
                                v-if="groupSlug"
                                :href="route('groups.stats.player', [groupSlug, row.user_id])"
                                class="truncate text-indigo-600 hover:text-indigo-500"
                            >
                                {{ row.name }}
                            </Link>
                            <span v-else class="truncate">{{ row.name }}</span>
                            <DuprBadge :dupr-id="row.dupr_id" /> <br>
                            <span
                                v-if="row.level"
                                class="inline-flex items-center rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-600"
                            >
                                Lv. {{ row.level }}
                            </span>
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-2 py-2 text-gray-500 sm:px-3">{{ row.games }}</td>
                    <td class="whitespace-nowrap px-2 py-2 text-gray-500 sm:px-3">{{ row.wins }}</td>
                    <td class="whitespace-nowrap px-2 py-2 text-gray-500 sm:px-3">{{ row.losses }}</td>
                    <td class="whitespace-nowrap px-2 py-2 font-medium text-gray-900 sm:px-3">{{ row.win_rate }}%</td>
                    <td class="whitespace-nowrap px-2 py-2 text-gray-500 sm:px-3">{{ formatStreak(row) }}</td>
                    <td :class="[
                        'whitespace-nowrap px-2 py-2 font-medium sm:px-3',
                        row.point_diff > 0 ? 'text-green-600' : row.point_diff < 0 ? 'text-red-600' : 'text-gray-500',
                    ]">
                        {{ formatDiff(row.point_diff) }}
                    </td>
                </tr>
                <tr v-if="!sortedRows.length">
                    <td colspan="8" class="px-2 py-6 text-center text-gray-500 sm:px-3">No matches played yet.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

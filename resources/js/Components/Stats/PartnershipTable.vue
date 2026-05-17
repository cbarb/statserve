<script setup>
import DuprBadge from '@/Components/DuprBadge.vue';

defineProps({
    partnerships: Array,
});
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Player 1</th>
                    <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Player 2</th>
                    <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Games</th>
                    <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">W</th>
                    <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">L</th>
                    <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Win%</th>
                    <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Avg Diff</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="p in partnerships" :key="`${p.player1_id}-${p.player2_id}`" class="hover:bg-gray-50">
                    <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-900">{{ p.player1_name }} <DuprBadge :dupr-id="p.player1_dupr_id" /></td>
                    <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-900">{{ p.player2_name }} <DuprBadge :dupr-id="p.player2_dupr_id" /></td>
                    <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-500">{{ p.games }}</td>
                    <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-500">{{ p.wins }}</td>
                    <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-500">{{ p.losses }}</td>
                    <td class="whitespace-nowrap px-3 py-2 text-sm font-medium text-gray-900">{{ p.win_rate }}%</td>
                    <td :class="[
                        'whitespace-nowrap px-3 py-2 text-sm font-medium',
                        p.avg_point_diff > 0 ? 'text-green-600' : p.avg_point_diff < 0 ? 'text-red-600' : 'text-gray-500',
                    ]">
                        {{ p.avg_point_diff > 0 ? '+' : '' }}{{ p.avg_point_diff }}
                    </td>
                </tr>
                <tr v-if="!partnerships.length">
                    <td colspan="7" class="px-3 py-6 text-center text-sm text-gray-500">No doubles matches played yet.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

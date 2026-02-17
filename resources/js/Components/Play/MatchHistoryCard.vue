<script setup>
const props = defineProps({
    match: Object,
});

function teamPlayers(teamNum) {
    return props.match.players
        .filter(p => p.team === teamNum)
        .map(p => p.name)
        .join(' & ');
}
</script>

<template>
    <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-white p-3">
        <div class="flex-1 text-right">
            <p class="text-sm font-medium" :class="match.winning_team === 1 ? 'text-green-700' : 'text-gray-600'">
                {{ teamPlayers(1) }}
            </p>
        </div>

        <div class="mx-4 flex items-center gap-2">
            <span class="text-xl font-bold" :class="match.winning_team === 1 ? 'text-green-700' : 'text-gray-400'">
                {{ match.team_1_score }}
            </span>
            <span class="text-gray-300">-</span>
            <span class="text-xl font-bold" :class="match.winning_team === 2 ? 'text-green-700' : 'text-gray-400'">
                {{ match.team_2_score }}
            </span>
        </div>

        <div class="flex-1">
            <p class="text-sm font-medium" :class="match.winning_team === 2 ? 'text-green-700' : 'text-gray-600'">
                {{ teamPlayers(2) }}
            </p>
        </div>
    </div>
</template>

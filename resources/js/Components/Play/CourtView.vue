<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    format: String,
    team1: Array,
    team2: Array,
    sittingOut: Array,
    editable: Boolean,
    allPlayers: Array,
    scoring: Boolean,
    team1Score: Number,
    team2Score: Number,
});

const emit = defineEmits([
    'update:team1', 'update:team2', 'update:sittingOut',
    'update:team1Score', 'update:team2Score',
]);

const isDoubles = computed(() => props.format === 'doubles');
const pickingSlot = ref(null);

function getPlayerName(userId) {
    return props.allPlayers?.find(p => p.id === userId)?.name || '?';
}

const assignedIds = computed(() => {
    const t1Ids = (props.team1 || []).map(p => p.user_id);
    const t2Ids = (props.team2 || []).map(p => p.user_id);
    return [...t1Ids, ...t2Ids];
});

const availablePlayers = computed(() => {
    return (props.allPlayers || []).filter(p => !assignedIds.value.includes(p.id));
});

function getSlotPlayer(team, position) {
    const players = team === 1 ? props.team1 : props.team2;
    return (players || []).find(p => p.position === position);
}

function openPicker(team, position) {
    if (!props.editable) return;
    pickingSlot.value = { team, position };
}

function assignPlayer(playerId) {
    if (!pickingSlot.value) return;
    const { team, position } = pickingSlot.value;
    const newEntry = { user_id: playerId, position };
    if (team === 1) {
        emit('update:team1', [...(props.team1 || []).filter(p => p.position !== position), newEntry]);
    } else {
        emit('update:team2', [...(props.team2 || []).filter(p => p.position !== position), newEntry]);
    }
    emit('update:sittingOut', (props.sittingOut || []).filter(id => id !== playerId));
    pickingSlot.value = null;
}

function removePlayer(team, position) {
    if (!props.editable) return;
    const players = team === 1 ? props.team1 : props.team2;
    const player = (players || []).find(p => p.position === position);
    if (!player) return;
    const updated = (players || []).filter(p => p.position !== position);
    if (team === 1) emit('update:team1', updated);
    else emit('update:team2', updated);
    emit('update:sittingOut', [...(props.sittingOut || []), player.user_id]);
}

function handleSlotClick(team, position) {
    if (!props.editable) return;
    const player = getSlotPlayer(team, position);
    if (player) removePlayer(team, position);
    else openPicker(team, position);
}

function closePicker() {
    pickingSlot.value = null;
}

function incrementScore(team) {
    if (team === 1) emit('update:team1Score', Math.min(99, (props.team1Score || 0) + 1));
    else emit('update:team2Score', Math.min(99, (props.team2Score || 0) + 1));
}
function decrementScore(team) {
    if (team === 1) emit('update:team1Score', Math.max(0, (props.team1Score || 0) - 1));
    else emit('update:team2Score', Math.max(0, (props.team2Score || 0) - 1));
}
function onScoreInput(team, e) {
    const val = parseInt(e.target.value, 10);
    if (!isNaN(val) && val >= 0 && val <= 99) {
        if (team === 1) emit('update:team1Score', val);
        else emit('update:team2Score', val);
    }
}
</script>

<template>
    <div class="relative mx-auto w-full max-w-sm">
        <!-- SVG Court lines only -->
        <svg viewBox="0 0 300 460" class="w-full" xmlns="http://www.w3.org/2000/svg">
            <!-- Court background -->
            <rect x="10" y="10" width="280" height="440" rx="6" fill="#4a7c59" stroke="#3d6b4a" stroke-width="2" />

            <!-- Outer boundary -->
            <rect x="30" y="25" width="240" height="410" fill="none" stroke="white" stroke-width="2" />

            <!-- Team 1 area tint -->
            <rect x="31" y="26" width="238" height="140" fill="rgba(59,130,246,0.08)" />
            <!-- Team 2 area tint -->
            <rect x="31" y="295" width="238" height="139" fill="rgba(239,68,68,0.08)" />

            <!-- NVZ / Kitchen zones -->
            <rect x="30" y="166" width="240" height="64" fill="rgba(255,255,255,0.06)" stroke="white" stroke-width="1.5" />
            <rect x="30" y="230" width="240" height="64" fill="rgba(255,255,255,0.06)" stroke="white" stroke-width="1.5" />

            <!-- Net -->
            <line x1="25" y1="230" x2="275" y2="230" stroke="white" stroke-width="3" />
            <circle cx="22" cy="230" r="4" fill="#999" />
            <circle cx="278" cy="230" r="4" fill="#999" />

            <!-- Center service line (doubles) -->
            <line v-if="isDoubles" x1="150" y1="26" x2="150" y2="166" stroke="white" stroke-width="1.5" />
            <line v-if="isDoubles" x1="150" y1="294" x2="150" y2="434" stroke="white" stroke-width="1.5" />
        </svg>

        <!--
            HTML overlay: positioned absolutely on top of the SVG.
            Uses a CSS grid that mirrors the court layout:
              Row 1: Team label
              Row 2: Team 1 quadrants (player area)
              Row 3: Kitchen / scores (Team 1 side)
              Row 4: Kitchen / scores (Team 2 side)
              Row 5: Team 2 quadrants (player area)
              Row 6: Team label
        -->
        <div class="absolute mt-[22px] inset-0 grid"
             style="grid-template-rows: 4% 31% 13% 13% 31% 4%; padding: 2.2% 10%;">

            <!-- Row 1: Team 1 label -->
            <div class="flex items-center justify-center">
                <span class="text-[10px] font-bold uppercase tracking-widest text-blue-300/80">Team 1</span>
            </div>

            <!-- Row 2: Team 1 player quadrants -->
            <div v-if="!isDoubles" class="flex items-center justify-center px-2">
                <!-- Singles: single player card centered -->
                <button
                    type="button"
                    @click="handleSlotClick(1, 'solo')"
                    class="flex max-w-[100px] flex-col items-center justify-center rounded-xl px-6 py-4 transition"
                    :class="getSlotPlayer(1, 'solo')
                        ? 'bg-blue-500/90 text-white shadow-lg shadow-blue-900/30'
                        : editable
                            ? 'border-2 border-dashed border-white/40 bg-white/10 text-white/60 hover:bg-white/20 cursor-pointer'
                            : 'border-2 border-dashed border-white/20 bg-white/5 text-white/30'"
                >
                    <template v-if="getSlotPlayer(1, 'solo')">
                        <span class="text-3xl font-bold leading-none">{{ getPlayerName(getSlotPlayer(1, 'solo').user_id).charAt(0).toUpperCase() }}</span>
                        <span class="mt-1.5 w-full truncate text-sm font-semibold leading-tight opacity-90">{{ getPlayerName(getSlotPlayer(1, 'solo').user_id) }}</span>
                    </template>
                    <template v-else>
                        <span class="text-3xl leading-none">+</span>
                        <span class="mt-1 text-xs">Add Player</span>
                    </template>
                </button>
            </div>
            <div v-else class="grid grid-cols-2 gap-2 px-2">
                <!-- Doubles: left and right quadrants -->
                <div v-for="pos in ['left', 'right']" :key="'t1-' + pos" class="flex items-center justify-center">
                    <button
                        type="button"
                        @click="handleSlotClick(1, pos)"
                        class="flex w-full max-w-[100px] flex-col items-center justify-center rounded-xl px-4 py-3 transition"
                        :class="getSlotPlayer(1, pos)
                            ? 'bg-blue-500/90 text-white shadow-lg shadow-blue-900/30'
                            : editable
                                ? 'border-2 border-dashed border-white/40 bg-white/10 text-white/60 hover:bg-white/20 cursor-pointer'
                                : 'border-2 border-dashed border-white/20 bg-white/5 text-white/30'"
                    >
                        <template v-if="getSlotPlayer(1, pos)">
                            <span class="text-2xl font-bold leading-none">{{ getPlayerName(getSlotPlayer(1, pos).user_id).charAt(0).toUpperCase() }}</span>
                            <span class="mt-1 w-full truncate text-xs font-semibold leading-tight opacity-90">{{ getPlayerName(getSlotPlayer(1, pos).user_id) }}</span>
                        </template>
                        <template v-else>
                            <span class="text-2xl leading-none">+</span>
                            <span class="mt-0.5 text-[10px]">Add</span>
                        </template>
                    </button>
                </div>
            </div>

            <!-- Row 3: Team 1 Kitchen / Score -->
            <div class="flex items-center justify-center">
                <template v-if="scoring">
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="decrementScore(1)"
                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-white/20 text-base font-bold text-white hover:bg-white/30">-</button>
                        <input type="number" :value="team1Score" @input="onScoreInput(1, $event)" min="0" max="99"
                            class="h-12 w-20 rounded-lg border-2 border-blue-300/50 bg-white/95 text-center text-2xl font-bold text-blue-700 shadow-lg" />
                        <button type="button" @click="incrementScore(1)"
                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-white/20 text-base font-bold text-white hover:bg-white/30">+</button>
                    </div>
                </template>
                <span v-else class="text-xs font-medium uppercase tracking-wider text-white/50">Kitchen</span>
            </div>

            <!-- Row 4: Team 2 Kitchen / Score -->
            <div class="flex items-center justify-center">
                <template v-if="scoring">
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="decrementScore(2)"
                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-white/20 text-base font-bold text-white hover:bg-white/30">-</button>
                        <input type="number" :value="team2Score" @input="onScoreInput(2, $event)" min="0" max="99"
                            class="h-12 w-20 rounded-lg border-2 border-red-300/50 bg-white/95 text-center text-2xl font-bold text-red-700 shadow-lg" />
                        <button type="button" @click="incrementScore(2)"
                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-white/20 text-base font-bold text-white hover:bg-white/30">+</button>
                    </div>
                </template>
                <span v-else class="text-xs font-medium uppercase tracking-wider text-white/50">Kitchen</span>
            </div>

            <!-- Row 5: Team 2 player quadrants -->
            <div v-if="!isDoubles" class="flex items-center justify-center px-2">
                <button
                    type="button"
                    @click="handleSlotClick(2, 'solo')"
                    class="flex max-w-[100px] flex-col items-center justify-center rounded-xl px-6 py-4 transition"
                    :class="getSlotPlayer(2, 'solo')
                        ? 'bg-red-500/90 text-white shadow-lg shadow-red-900/30'
                        : editable
                            ? 'border-2 border-dashed border-white/40 bg-white/10 text-white/60 hover:bg-white/20 cursor-pointer'
                            : 'border-2 border-dashed border-white/20 bg-white/5 text-white/30'"
                >
                    <template v-if="getSlotPlayer(2, 'solo')">
                        <span class="text-3xl font-bold leading-none">{{ getPlayerName(getSlotPlayer(2, 'solo').user_id).charAt(0).toUpperCase() }}</span>
                        <span class="mt-1.5 w-full truncate text-sm font-semibold leading-tight opacity-90">{{ getPlayerName(getSlotPlayer(2, 'solo').user_id) }}</span>
                    </template>
                    <template v-else>
                        <span class="text-3xl leading-none">+</span>
                        <span class="mt-1 text-xs">Add Player</span>
                    </template>
                </button>
            </div>
            <div v-else class="grid grid-cols-2 gap-2 px-2">
                <div v-for="pos in ['left', 'right']" :key="'t2-' + pos" class="flex items-center justify-center">
                    <button
                        type="button"
                        @click="handleSlotClick(2, pos)"
                        class="flex w-full max-w-[100px] flex-col items-center justify-center rounded-xl px-4 py-3 transition"
                        :class="getSlotPlayer(2, pos)
                            ? 'bg-red-500/90 text-white shadow-lg shadow-red-900/30'
                            : editable
                                ? 'border-2 border-dashed border-white/40 bg-white/10 text-white/60 hover:bg-white/20 cursor-pointer'
                                : 'border-2 border-dashed border-white/20 bg-white/5 text-white/30'"
                    >
                        <template v-if="getSlotPlayer(2, pos)">
                            <span class="text-2xl font-bold leading-none">{{ getPlayerName(getSlotPlayer(2, pos).user_id).charAt(0).toUpperCase() }}</span>
                            <span class="mt-1 w-full truncate text-xs font-semibold leading-tight opacity-90">{{ getPlayerName(getSlotPlayer(2, pos).user_id) }}</span>
                        </template>
                        <template v-else>
                            <span class="text-2xl leading-none">+</span>
                            <span class="mt-0.5 text-[10px]">Add</span>
                        </template>
                    </button>
                </div>
            </div>

            <!-- Row 6: Team 2 label -->
            <div class="flex items-center justify-center">
                <span class="text-[10px] font-bold uppercase tracking-widest text-red-300/80">Team 2</span>
            </div>
        </div>

        <!-- Player picker overlay -->
        <div v-if="pickingSlot" class="absolute mt-[22px] inset-0 z-10 flex items-center justify-center rounded-lg bg-black/40" @click.self="closePicker">
            <div class="w-52 rounded-lg bg-white p-3 shadow-xl">
                <p class="mb-2 text-sm font-medium text-gray-700">Select Player</p>
                <div class="max-h-48 space-y-1 overflow-y-auto">
                    <button
                        v-for="player in availablePlayers"
                        :key="player.id"
                        @click="assignPlayer(player.id)"
                        class="flex w-full items-center gap-2 rounded p-2 text-left text-sm hover:bg-gray-100"
                    >
                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-200 text-xs font-medium">
                            {{ player.name.charAt(0).toUpperCase() }}
                        </div>
                        {{ player.name }}
                    </button>
                    <p v-if="!availablePlayers.length" class="py-2 text-center text-sm text-gray-400">No available players</p>
                </div>
                <button @click="closePicker" class="mt-2 w-full rounded bg-gray-100 py-1 text-xs text-gray-600 hover:bg-gray-200">Cancel</button>
            </div>
        </div>
    </div>
</template>

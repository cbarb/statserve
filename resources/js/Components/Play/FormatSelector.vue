<script setup>
const props = defineProps({
    modelValue: String,
    playerCount: Number,
});

const emit = defineEmits(['update:modelValue']);

const formats = [
    {
        value: 'singles',
        label: '1v1 Singles',
        description: 'One player per side',
        minPlayers: 2,
    },
    {
        value: 'doubles',
        label: '2v2 Doubles',
        description: 'Two players per side',
        minPlayers: 4,
    },
];
</script>

<template>
    <div class="grid grid-cols-2 gap-4">
        <button
            v-for="format in formats"
            :key="format.value"
            type="button"
            @click="playerCount >= format.minPlayers && emit('update:modelValue', format.value)"
            class="rounded-lg border-2 p-6 text-center transition"
            :class="{
                'border-indigo-500 bg-indigo-50': modelValue === format.value,
                'border-gray-200 hover:border-gray-300': modelValue !== format.value && playerCount >= format.minPlayers,
                'cursor-not-allowed border-gray-100 bg-gray-50 opacity-50': playerCount < format.minPlayers,
            }"
        >
            <div class="text-lg font-semibold text-gray-900">{{ format.label }}</div>
            <div class="mt-1 text-sm text-gray-500">{{ format.description }}</div>
            <div v-if="playerCount < format.minPlayers" class="mt-2 text-xs text-red-500">
                Need {{ format.minPlayers }}+ players
            </div>
        </button>
    </div>
</template>

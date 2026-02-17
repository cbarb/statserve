<script setup>
const props = defineProps({
    modelValue: {
        type: Number,
        default: 0,
    },
    teamLabel: String,
    teamColor: {
        type: String,
        default: 'gray', // 'blue' | 'red' | 'gray'
    },
});

const emit = defineEmits(['update:modelValue']);

function increment() {
    emit('update:modelValue', Math.min(99, props.modelValue + 1));
}

function decrement() {
    emit('update:modelValue', Math.max(0, props.modelValue - 1));
}

function onInput(e) {
    const val = parseInt(e.target.value, 10);
    if (!isNaN(val) && val >= 0 && val <= 99) {
        emit('update:modelValue', val);
    }
}

const colorClasses = {
    blue: 'border-blue-200 bg-blue-50',
    red: 'border-red-200 bg-red-50',
    gray: 'border-gray-200 bg-gray-50',
};
</script>

<template>
    <div class="rounded-lg border-2 p-4" :class="colorClasses[teamColor]">
        <p class="mb-2 text-center text-sm font-semibold uppercase tracking-wide text-gray-600">{{ teamLabel }}</p>
        <div class="flex items-center justify-center gap-3">
            <button
                type="button"
                @click="decrement"
                class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-lg font-bold text-gray-600 shadow transition hover:bg-gray-100"
            >
                -
            </button>
            <input
                type="number"
                :value="modelValue"
                @input="onInput"
                min="0"
                max="99"
                class="h-16 w-20 rounded-lg border-gray-300 bg-white text-center text-3xl font-bold text-gray-900 shadow-sm"
            />
            <button
                type="button"
                @click="increment"
                class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-lg font-bold text-gray-600 shadow transition hover:bg-gray-100"
            >
                +
            </button>
        </div>
    </div>
</template>

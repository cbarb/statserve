<script setup>
const props = defineProps({
    modelValue: String,
});

const emit = defineEmits(['update:modelValue']);

const modes = [
    {
        value: 'random',
        label: 'Randomize',
        description: 'Auto-assign teams with fair rotation',
    },
    {
        value: 'handpick',
        label: 'Hand Pick',
        description: 'Manually choose who plays where',
    },
];
</script>

<template>
    <div class="grid grid-cols-2 gap-4">
        <button
            v-for="mode in modes"
            :key="mode.value"
            type="button"
            @click="emit('update:modelValue', mode.value)"
            class="rounded-lg border-2 p-6 text-center transition"
            :class="modelValue === mode.value ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'"
        >
            <div class="text-lg font-semibold text-gray-900">{{ mode.label }}</div>
            <div class="mt-1 text-sm text-gray-500">{{ mode.description }}</div>
        </button>
    </div>
</template>

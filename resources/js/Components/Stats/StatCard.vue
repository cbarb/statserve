<script setup>
defineProps({
    label: String,
    value: [String, Number],
    subtitle: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-900/5">
        <p class="text-sm font-medium text-gray-500">{{ label }}</p>
        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ value }}</p>
        <p v-if="subtitle" class="mt-1 text-xs text-gray-400">{{ subtitle }}</p>
    </div>
</template>

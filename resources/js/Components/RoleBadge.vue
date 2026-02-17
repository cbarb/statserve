<script setup>
import { computed } from 'vue';

const props = defineProps({
    role: {
        type: String,
        required: true,
    },
});

const classes = computed(() => {
    const base = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium';
    switch (props.role) {
        case 'owner':
            return `${base} bg-amber-100 text-amber-800`;
        case 'admin':
            return `${base} bg-blue-100 text-blue-800`;
        default:
            return `${base} bg-gray-100 text-gray-800`;
    }
});

const label = computed(() => {
    return props.role.charAt(0).toUpperCase() + props.role.slice(1);
});
</script>

<template>
    <span :class="classes">{{ label }}</span>
</template>

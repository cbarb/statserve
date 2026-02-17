<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    modelValue: {
        type: String,
        default: 'all',
    },
    canViewAllTime: {
        type: Boolean,
        default: true,
    },
});

const ranges = [
    { value: 'daily', label: 'Today' },
    { value: 'weekly', label: 'This Week' },
    { value: 'monthly', label: 'This Month' },
    { value: 'all', label: 'All Time' },
];

const select = (range) => {
    if (range === 'all' && !props.canViewAllTime) {
        router.visit(route('billing.index'));
        return;
    }

    router.get(window.location.pathname, { range }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="inline-flex rounded-md shadow-sm">
        <button
            v-for="(r, i) in ranges"
            :key="r.value"
            @click="select(r.value)"
            :class="[
                'px-3 py-1.5 text-sm font-medium',
                r.value === 'all' && !canViewAllTime
                    ? 'bg-gray-100 text-gray-400 cursor-pointer'
                    : modelValue === r.value
                        ? 'bg-indigo-600 text-white'
                        : 'bg-white text-gray-700 hover:bg-gray-50',
                i === 0 ? 'rounded-l-md' : '',
                i === ranges.length - 1 ? 'rounded-r-md' : '',
                'border border-gray-300',
                i > 0 ? '-ml-px' : '',
            ]"
        >
            {{ r.label }}
            <span
                v-if="r.value === 'all' && !canViewAllTime"
                class="ml-1 inline-flex items-center rounded bg-indigo-100 px-1 py-0.5 text-xs font-semibold text-indigo-700"
            >
                Pro
            </span>
        </button>
    </div>
</template>

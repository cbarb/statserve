<script setup>
defineProps({
    duprId: String,
});
</script>

<template>
    <a
        v-if="duprId"
        :href="`https://dashboard.dupr.com/dashboard/player/${duprId}`"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center rounded bg-blue-100 px-1.5 py-0.5 text-xs font-medium text-blue-700 hover:bg-blue-200 transition"
        @click.stop
        title="View DUPR Profile"
    >DUPR</a>
</template>

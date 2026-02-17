<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    groups: Array,
});
</script>

<template>
    <Head title="Play — Select Group" />

    <AuthenticatedLayout :back-href="route('dashboard')">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Select a Group to Play</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div v-if="groups.length" class="space-y-3">
                    <Link
                        v-for="group in groups"
                        :key="group.id"
                        :href="route('play.setup', group.slug)"
                        class="block rounded-lg bg-white p-5 shadow-sm transition hover:shadow-md"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ group.name }}</h3>
                                <p class="text-sm text-gray-500">{{ group.members_count }} {{ group.members_count === 1 ? 'member' : 'members' }}</p>
                            </div>
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </Link>
                </div>

                <div v-else class="rounded-lg bg-white p-8 text-center shadow-sm">
                    <p class="text-gray-500">You need to join or create a group first.</p>
                    <Link :href="route('groups.create')" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-500">
                        Create a Group
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

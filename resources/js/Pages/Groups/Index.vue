<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RoleBadge from '@/Components/RoleBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    groups: Array,
});
</script>

<template>
    <Head title="My Groups" />

    <AuthenticatedLayout :back-href="route('dashboard')">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">My Groups</h2>
                <Link :href="route('groups.create')">
                    <PrimaryButton>Create Group</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="groups.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="group in groups"
                        :key="group.id"
                        :href="route('groups.show', group.slug)"
                        class="block overflow-hidden rounded-lg bg-white shadow-sm transition hover:shadow-md"
                    >
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">{{ group.name }}</h3>
                                <RoleBadge :role="group.role" />
                            </div>
                            <p class="mt-2 text-sm text-gray-500">
                                {{ group.members_count }} {{ group.members_count === 1 ? 'member' : 'members' }}
                            </p>
                        </div>
                    </Link>
                </div>

                <div v-else class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6 text-center">
                        <p class="text-gray-500">You're not in any groups yet.</p>
                        <div class="mt-4 flex justify-center gap-3">
                            <Link :href="route('groups.create')">
                                <PrimaryButton>Create a Group</PrimaryButton>
                            </Link>
                        </div>
                        <p class="mt-3 text-sm text-gray-400">Or join one with an invite link.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

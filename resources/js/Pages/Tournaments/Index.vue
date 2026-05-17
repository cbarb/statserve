<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    tournaments: Object,
    filters: Object,
});

const page = usePage();
const location = ref(props.filters.location);
const radius = ref(props.filters.radius);

const search = () => {
    router.get(route('tournaments.index'), {
        location: location.value,
        radius: radius.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatLabel = (value) => {
    return value.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
};
</script>

<template>
    <Head title="Tournaments" />

    <AuthenticatedLayout :back-href="route('dashboard')">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Tournaments</h2>
                <Link :href="route('tournaments.create')">
                    <PrimaryButton>Create Tournament</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Search Bar -->
                <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <form @submit.prevent="search" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700">Location</label>
                                <TextInput
                                    v-model="location"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="City, state, or address..."
                                />
                            </div>
                            <div class="w-full sm:w-40">
                                <label class="block text-sm font-medium text-gray-700">Radius</label>
                                <select
                                    v-model="radius"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option :value="10">10 miles</option>
                                    <option :value="25">25 miles</option>
                                    <option :value="50">50 miles</option>
                                    <option :value="100">100 miles</option>
                                </select>
                            </div>
                            <PrimaryButton type="submit" class="w-full sm:w-auto">Search</PrimaryButton>
                        </form>
                    </div>
                </div>

                <!-- Tournament Grid -->
                <div v-if="tournaments.data.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="tournament in tournaments.data"
                        :key="tournament.id"
                        :href="route('tournaments.show', tournament.id)"
                        class="block overflow-hidden rounded-lg bg-white shadow-sm transition hover:shadow-md"
                    >
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900">{{ tournament.name }}</h3>
                            <p v-if="tournament.city || tournament.state" class="mt-1 text-sm text-gray-500">
                                {{ [tournament.city, tournament.state].filter(Boolean).join(', ') }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">
                                    {{ formatLabel(tournament.format) }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800">
                                    {{ formatLabel(tournament.bracket_type) }}
                                </span>
                                <span v-if="tournament.entry_fee" class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">
                                    ${{ (tournament.entry_fee / 100).toFixed(2) }}
                                </span>
                                <span v-else class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                                    Free
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-sm text-gray-500">
                                <span>{{ tournament.players_count }}/{{ tournament.max_players }} spots</span>
                                <span>{{ new Date(tournament.starts_at).toLocaleDateString() }}</span>
                            </div>
                            <p v-if="tournament.distance != null" class="mt-1 text-xs text-gray-400">
                                {{ Math.round(tournament.distance) }} miles away
                            </p>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-else class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6 text-center">
                        <p class="text-gray-500">No tournaments found.</p>
                        <p v-if="filters.location" class="mt-1 text-sm text-gray-400">
                            Try a different location or increase the search radius.
                        </p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="tournaments.links && tournaments.last_page > 1" class="mt-6 flex justify-center gap-1">
                    <template v-for="link in tournaments.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded-md px-3 py-2 text-sm"
                            :class="link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                            preserve-state
                        >{{ link.label.replace(/&laquo;/g, '\u00AB').replace(/&raquo;/g, '\u00BB') }}</Link>
                        <span
                            v-else
                            class="rounded-md bg-white px-3 py-2 text-sm text-gray-400"
                        >{{ link.label.replace(/&laquo;/g, '\u00AB').replace(/&raquo;/g, '\u00BB') }}</span>
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

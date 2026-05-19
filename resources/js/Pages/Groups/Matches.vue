<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    group: Object,
    matches: Object,
    userRole: String,
});

const canManage = ['owner', 'admin'].includes(props.userRole);

// Edit
const editingMatch = ref(null);
const editForm = useForm({ team_1_score: 0, team_2_score: 0 });

const openEdit = (match) => {
    editingMatch.value = match;
    editForm.team_1_score = match.team_1_score;
    editForm.team_2_score = match.team_2_score;
};

const submitEdit = () => {
    editForm.patch(route('groups.matches.update', [props.group.slug, editingMatch.value.id]), {
        onSuccess: () => { editingMatch.value = null; },
        preserveScroll: true,
    });
};

// Delete
const matchToDelete = ref(null);
const deleteForm = useForm({});

const confirmDelete = (match) => { matchToDelete.value = match; };

const submitDelete = () => {
    deleteForm.delete(route('groups.matches.destroy', [props.group.slug, matchToDelete.value.id]), {
        onSuccess: () => { matchToDelete.value = null; },
        preserveScroll: true,
    });
};

const formatDate = (iso) => {
    return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const teamNames = (players) => players.map(p => p.name).join(' & ');
</script>

<template>
    <Head :title="`${group.name} — Match History`" />

    <AuthenticatedLayout :back-href="route('groups.show', group.slug)">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Match History</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">

                <div v-if="matches.data.length === 0" class="rounded-lg bg-white p-8 text-center shadow-sm">
                    <p class="text-gray-500">No matches played yet.</p>
                </div>

                <div v-else class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <ul class="divide-y divide-gray-100">
                        <li v-for="match in matches.data" :key="match.id" class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <!-- Match info -->
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                                        {{ match.format }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ formatDate(match.played_at) }}</span>
                                </div>
                                <div class="mt-2 flex items-center gap-3 text-sm">
                                    <!-- Team 1 -->
                                    <div :class="match.winning_team === 1 ? 'font-semibold text-gray-900' : 'text-gray-500'">
                                        {{ teamNames(match.team_1) }}
                                    </div>
                                    <!-- Score -->
                                    <div class="flex items-center gap-1 font-mono text-base">
                                        <span :class="match.winning_team === 1 ? 'font-bold text-gray-900' : 'text-gray-500'">{{ match.team_1_score }}</span>
                                        <span class="text-gray-300">–</span>
                                        <span :class="match.winning_team === 2 ? 'font-bold text-gray-900' : 'text-gray-500'">{{ match.team_2_score }}</span>
                                    </div>
                                    <!-- Team 2 -->
                                    <div :class="match.winning_team === 2 ? 'font-semibold text-gray-900' : 'text-gray-500'">
                                        {{ teamNames(match.team_2) }}
                                    </div>
                                </div>
                                <p v-if="match.logged_by_name" class="mt-1 text-xs text-gray-400">Logged by {{ match.logged_by_name }}</p>
                            </div>

                            <!-- Admin actions -->
                            <div v-if="canManage" class="flex shrink-0 gap-2">
                                <SecondaryButton class="text-xs" @click="openEdit(match)">Edit</SecondaryButton>
                                <DangerButton class="text-xs" @click="confirmDelete(match)">Delete</DangerButton>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Pagination -->
                <div v-if="matches.last_page > 1" class="flex justify-center gap-2">
                    <Link
                        v-for="link in matches.links"
                        :key="link.label"
                        :href="link.url ?? ''"
                        :class="[
                            'rounded px-3 py-1 text-sm',
                            link.active ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 hover:bg-gray-50',
                            !link.url ? 'pointer-events-none opacity-40' : '',
                        ]"
                        preserve-scroll
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <!-- Edit Modal -->
    <div v-if="editingMatch" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-900">Edit Score</h3>
            <p class="mt-1 text-sm text-gray-500">{{ teamNames(editingMatch.team_1) }} vs {{ teamNames(editingMatch.team_2) }}</p>

            <div class="mt-4 flex items-center gap-4">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700">{{ teamNames(editingMatch.team_1) }}</label>
                    <input
                        v-model.number="editForm.team_1_score"
                        type="number" min="0" max="99"
                        class="mt-1 block w-full rounded-md border-gray-300 text-center text-2xl font-bold shadow-sm"
                    />
                </div>
                <span class="mt-5 text-gray-400">–</span>
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700">{{ teamNames(editingMatch.team_2) }}</label>
                    <input
                        v-model.number="editForm.team_2_score"
                        type="number" min="0" max="99"
                        class="mt-1 block w-full rounded-md border-gray-300 text-center text-2xl font-bold shadow-sm"
                    />
                </div>
            </div>

            <p v-if="editForm.errors.team_2_score" class="mt-2 text-sm text-red-600">{{ editForm.errors.team_2_score }}</p>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton @click="editingMatch = null">Cancel</SecondaryButton>
                <PrimaryButton @click="submitEdit" :disabled="editForm.processing">Save</PrimaryButton>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation -->
    <ConfirmationModal :show="!!matchToDelete" @close="matchToDelete = null">
        <template #title>Delete Match</template>
        <template #content>
            Are you sure you want to delete this match? This cannot be undone and will affect all stats.
        </template>
        <template #footer>
            <SecondaryButton @click="matchToDelete = null">Cancel</SecondaryButton>
            <DangerButton class="ml-3" @click="submitDelete" :disabled="deleteForm.processing">Delete</DangerButton>
        </template>
    </ConfirmationModal>
</template>

<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DuprBadge from '@/Components/DuprBadge.vue';
import RoleBadge from '@/Components/RoleBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    group: Object,
    members: Array,
    userRole: String,
    leaderboardPreview: Array,
    myStats: Object,
    hasBoost: Boolean,
    weeklyMatchCount: Number,
});

const isOwnerOrAdmin = ['owner', 'admin'].includes(props.userRole);
const isOwner = props.userRole === 'owner';

const page = usePage();
const isPro = page.props.subscription?.is_pro;
const showMatchCounter = !props.hasBoost && !isPro;

const inviteUrl = `${window.location.origin}/join/${props.group.invite_code}`;
const copied = ref(false);

const copyInvite = () => {
    navigator.clipboard.writeText(inviteUrl);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
};

const regenerateForm = useForm({});
const regenerateInvite = () => {
    regenerateForm.post(route('groups.invite.regenerate', props.group.slug), {
        preserveScroll: true,
    });
};

// Leave group
const showLeaveModal = ref(false);
const leaveForm = useForm({});
const leaveGroup = () => {
    leaveForm.delete(route('groups.leave', props.group.slug));
};

// Remove member
const memberToRemove = ref(null);
const removeForm = useForm({});
const confirmRemove = (member) => {
    memberToRemove.value = member;
};
const removeMember = () => {
    removeForm.delete(route('groups.members.destroy', [props.group.slug, memberToRemove.value.id]), {
        preserveScroll: true,
        onSuccess: () => (memberToRemove.value = null),
    });
};

// Promote/demote
const promoteForm = useForm({});
const toggleRole = (member) => {
    promoteForm.post(route('groups.members.promote', [props.group.slug, member.id]), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="group.name" />

    <AuthenticatedLayout :back-href="route('groups.index')">
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ group.name }}</h2>
                    <RoleBadge :role="userRole" />
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('play.setup', group.slug)">
                        <PrimaryButton class="!bg-emerald-600 hover:!bg-emerald-500 focus:!bg-emerald-500 active:!bg-emerald-700 focus:!ring-emerald-500">Play</PrimaryButton>
                    </Link>
                    <Link v-if="isOwnerOrAdmin" :href="route('groups.edit', group.slug)">
                        <SecondaryButton>Settings</SecondaryButton>
                    </Link>
                    <DangerButton @click="showLeaveModal = true">Leave</DangerButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Invite Card -->
                <div v-if="isOwnerOrAdmin" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Invite Link</h3>
                        <div class="mt-3 flex items-center gap-3">
                            <input
                                type="text"
                                :value="inviteUrl"
                                readonly
                                class="block w-full rounded-md border-gray-300 bg-gray-50 text-sm text-gray-600 shadow-sm"
                            />
                            <SecondaryButton @click="copyInvite">
                                {{ copied ? 'Copied!' : 'Copy' }}
                            </SecondaryButton>
                            <SecondaryButton @click="regenerateInvite" :disabled="regenerateForm.processing">
                                Regenerate
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Members Card -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Members</h3>
                        <div class="mt-4 divide-y divide-gray-100">
                            <div
                                v-for="member in members"
                                :key="member.id"
                                class="flex items-center justify-between py-3"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-sm font-medium text-gray-600">
                                        {{ member.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ member.name }} <DuprBadge :dupr-id="member.dupr_id" /></p>
                                        <p class="text-sm text-gray-500">Joined {{ new Date(member.joined_at).toLocaleDateString() }}</p>
                                    </div>
                                    <RoleBadge :role="member.role" />
                                </div>
                                <div v-if="isOwner && member.role !== 'owner'" class="flex items-center gap-2">
                                    <SecondaryButton @click="toggleRole(member)" :disabled="promoteForm.processing" class="text-xs">
                                        {{ member.role === 'admin' ? 'Demote' : 'Promote' }}
                                    </SecondaryButton>
                                    <DangerButton @click="confirmRemove(member)" class="text-xs">
                                        Remove
                                    </DangerButton>
                                </div>
                                <div v-else-if="isOwnerOrAdmin && !isOwner && member.role === 'member'" class="flex items-center gap-2">
                                    <DangerButton @click="confirmRemove(member)" class="text-xs">
                                        Remove
                                    </DangerButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Leaderboard</h3>
                            <div class="flex items-center gap-4">
                                <Link :href="route('groups.matches', group.slug)" class="text-sm text-indigo-600 hover:text-indigo-500">
                                    Match History
                                </Link>
                                <Link :href="route('groups.stats', group.slug)" class="text-sm text-indigo-600 hover:text-indigo-500">
                                    View Full Stats
                                </Link>
                            </div>
                        </div>

                        <div v-if="leaderboardPreview.length" class="mt-4">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">#</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Player</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Games</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Win%</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr
                                        v-for="(row, i) in leaderboardPreview"
                                        :key="row.user_id"
                                        :class="row.user_id === $page.props.auth.user.id ? 'bg-indigo-50' : ''"
                                    >
                                        <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-500">{{ i + 1 }}</td>
                                        <td class="whitespace-nowrap px-3 py-2 text-sm font-medium text-gray-900">{{ row.name }} <DuprBadge :dupr-id="row.dupr_id" /></td>
                                        <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-500">{{ row.games }}</td>
                                        <td class="whitespace-nowrap px-3 py-2 text-sm font-medium text-gray-900">{{ row.win_rate }}%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="mt-2 text-sm text-gray-500">No matches played yet. Start a game to see stats!</p>
                    </div>
                </div>

                <p v-if="showMatchCounter" class="px-2 text-sm">
                    <span :class="weeklyMatchCount >= 4 ? 'text-amber-600' : 'text-gray-500'">{{ weeklyMatchCount }}/5 free matches this week</span>
                    <span class="mx-1 text-gray-300">&middot;</span>
                    <Link :href="route('billing.index')" class="text-emerald-600 hover:text-emerald-500">Unlock unlimited &rarr;</Link>
                </p>
            </div>
        </div>

        <!-- Leave Modal -->
        <ConfirmationModal
            :show="showLeaveModal"
            title="Leave Group"
            :message="isOwner
                ? 'You are the owner. Ownership will transfer to the longest-tenured member, or the group will be deleted if you are the last member.'
                : 'Are you sure you want to leave this group?'"
            confirm-label="Leave"
            :danger="true"
            :processing="leaveForm.processing"
            @confirm="leaveGroup"
            @cancel="showLeaveModal = false"
        />

        <!-- Remove Member Modal -->
        <ConfirmationModal
            :show="!!memberToRemove"
            title="Remove Member"
            :message="`Are you sure you want to remove ${memberToRemove?.name} from the group?`"
            confirm-label="Remove"
            :danger="true"
            :processing="removeForm.processing"
            @confirm="removeMember"
            @cancel="memberToRemove = null"
        />
    </AuthenticatedLayout>
</template>

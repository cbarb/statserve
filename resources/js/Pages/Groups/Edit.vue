<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    group: Object,
    members: Array,
    userRole: String,
    timezones: Array,
    status: String,
});

const isOwner = props.userRole === 'owner';

// General settings form
const settingsForm = useForm({
    name: props.group.name,
    timezone: props.group.timezone || '',
});

const saveSettings = () => {
    settingsForm.patch(route('groups.update', props.group.slug));
};

// Transfer ownership
const showTransferModal = ref(false);
const transferForm = useForm({
    new_owner_id: '',
});

const nonOwnerMembers = props.members.filter(m => m.role !== 'owner');

const transferOwnership = () => {
    transferForm.post(route('groups.transfer', props.group.slug), {
        onSuccess: () => (showTransferModal.value = false),
    });
};

// Delete group
const showDeleteModal = ref(false);
const deleteForm = useForm({});

const deleteGroup = () => {
    deleteForm.delete(route('groups.destroy', props.group.slug));
};
</script>

<template>
    <Head :title="`${group.name} Settings`" />

    <AuthenticatedLayout :back-href="route('groups.show', group.slug)">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ group.name }} &mdash; Settings</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">
                <!-- General Settings -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">General</h3>

                        <form @submit.prevent="saveSettings" class="mt-4 space-y-6">
                            <div>
                                <InputLabel for="name" value="Group Name" />
                                <TextInput
                                    id="name"
                                    v-model="settingsForm.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="settingsForm.errors.name" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="timezone" value="Timezone" />
                                <select
                                    id="timezone"
                                    v-model="settingsForm.timezone"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Select a timezone...</option>
                                    <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                                </select>
                                <InputError :message="settingsForm.errors.timezone" class="mt-2" />
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="settingsForm.processing">Save</PrimaryButton>
                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p v-if="settingsForm.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                                </Transition>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danger Zone (owner only) -->
                <div v-if="isOwner" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-red-600">Danger Zone</h3>

                        <div class="mt-4 space-y-4">
                            <!-- Transfer Ownership -->
                            <div v-if="nonOwnerMembers.length" class="flex items-center justify-between rounded-md border border-gray-200 p-4">
                                <div>
                                    <p class="font-medium text-gray-900">Transfer Ownership</p>
                                    <p class="text-sm text-gray-500">Transfer this group to another member.</p>
                                </div>
                                <DangerButton @click="showTransferModal = true">Transfer</DangerButton>
                            </div>

                            <!-- Delete Group -->
                            <div class="flex items-center justify-between rounded-md border border-gray-200 p-4">
                                <div>
                                    <p class="font-medium text-gray-900">Delete Group</p>
                                    <p class="text-sm text-gray-500">Permanently delete this group and all its data.</p>
                                </div>
                                <DangerButton @click="showDeleteModal = true">Delete</DangerButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfer Modal -->
        <ConfirmationModal
            :show="showTransferModal"
            title="Transfer Ownership"
            message="Select the new owner. You will be demoted to admin."
            confirm-label="Transfer"
            :danger="true"
            :processing="transferForm.processing"
            @confirm="transferOwnership"
            @cancel="showTransferModal = false"
        >
            <div class="mt-4">
                <select
                    v-model="transferForm.new_owner_id"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">Select a member...</option>
                    <option v-for="member in nonOwnerMembers" :key="member.id" :value="member.id">
                        {{ member.name }}
                    </option>
                </select>
                <InputError :message="transferForm.errors.new_owner_id" class="mt-2" />
            </div>
        </ConfirmationModal>

        <!-- Delete Modal -->
        <ConfirmationModal
            :show="showDeleteModal"
            title="Delete Group"
            message="Are you sure you want to permanently delete this group? This action cannot be undone."
            confirm-label="Delete Group"
            :danger="true"
            :processing="deleteForm.processing"
            @confirm="deleteGroup"
            @cancel="showDeleteModal = false"
        />
    </AuthenticatedLayout>
</template>

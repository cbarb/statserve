<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    formats: Array,
    bracketTypes: Array,
});

const form = useForm({
    name: '',
    description: '',
    format: 'open_singles',
    bracket_type: 'single_elimination',
    max_players: 16,
    address: '',
    city: '',
    state: '',
    starts_at: '',
    registration_opens_at: '',
    registration_closes_at: '',
    entry_fee: '',
    min_rating: '',
    max_rating: '',
});

const entryFeeDollars = computed({
    get: () => form.entry_fee ? (form.entry_fee / 100).toFixed(2) : '',
    set: (val) => { form.entry_fee = val ? Math.round(parseFloat(val) * 100) : ''; },
});

const submit = () => {
    form.post(route('tournaments.store'));
};
</script>

<template>
    <Head title="Create Tournament" />

    <AuthenticatedLayout :back-href="route('tournaments.index')">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Create Tournament</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="name" value="Tournament Name" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    autofocus
                                    placeholder="e.g. Spring Open 2026"
                                />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="description" value="Description (optional)" />
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Tell players about this tournament..."
                                />
                                <InputError :message="form.errors.description" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="format" value="Format" />
                                    <select
                                        id="format"
                                        v-model="form.format"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option v-for="f in formats" :key="f.value" :value="f.value">{{ f.label }}</option>
                                    </select>
                                    <InputError :message="form.errors.format" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="bracket_type" value="Bracket Type" />
                                    <select
                                        id="bracket_type"
                                        v-model="form.bracket_type"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option v-for="b in bracketTypes" :key="b.value" :value="b.value">{{ b.label }}</option>
                                    </select>
                                    <InputError :message="form.errors.bracket_type" class="mt-2" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="max_players" value="Max Players" />
                                <TextInput
                                    id="max_players"
                                    v-model="form.max_players"
                                    type="number"
                                    min="4"
                                    max="128"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.max_players" class="mt-2" />
                            </div>

                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-sm font-medium text-gray-900">Location</h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <InputLabel for="address" value="Address" />
                                        <TextInput
                                            id="address"
                                            v-model="form.address"
                                            type="text"
                                            class="mt-1 block w-full"
                                            placeholder="123 Main St"
                                        />
                                        <InputError :message="form.errors.address" class="mt-2" />
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <InputLabel for="city" value="City" />
                                            <TextInput
                                                id="city"
                                                v-model="form.city"
                                                type="text"
                                                class="mt-1 block w-full"
                                            />
                                            <InputError :message="form.errors.city" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="state" value="State" />
                                            <TextInput
                                                id="state"
                                                v-model="form.state"
                                                type="text"
                                                class="mt-1 block w-full"
                                            />
                                            <InputError :message="form.errors.state" class="mt-2" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-sm font-medium text-gray-900">Schedule</h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <InputLabel for="starts_at" value="Start Date & Time" />
                                        <TextInput
                                            id="starts_at"
                                            v-model="form.starts_at"
                                            type="datetime-local"
                                            class="mt-1 block w-full"
                                        />
                                        <InputError :message="form.errors.starts_at" class="mt-2" />
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <InputLabel for="registration_opens_at" value="Registration Opens (optional)" />
                                            <TextInput
                                                id="registration_opens_at"
                                                v-model="form.registration_opens_at"
                                                type="datetime-local"
                                                class="mt-1 block w-full"
                                            />
                                            <InputError :message="form.errors.registration_opens_at" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="registration_closes_at" value="Registration Closes (optional)" />
                                            <TextInput
                                                id="registration_closes_at"
                                                v-model="form.registration_closes_at"
                                                type="datetime-local"
                                                class="mt-1 block w-full"
                                            />
                                            <InputError :message="form.errors.registration_closes_at" class="mt-2" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-sm font-medium text-gray-900">Entry Fee (optional)</h3>
                                <div class="mt-4">
                                    <InputLabel for="entry_fee" value="Entry Fee ($)" />
                                    <TextInput
                                        id="entry_fee"
                                        v-model="entryFeeDollars"
                                        type="number"
                                        step="0.01"
                                        min="1"
                                        max="1000"
                                        class="mt-1 block w-full sm:w-48"
                                        placeholder="Free"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">Leave empty for a free tournament. Platform takes 10% of entry fees.</p>
                                    <InputError :message="form.errors.entry_fee" class="mt-2" />
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-sm font-medium text-gray-900">Rating Range (optional)</h3>
                                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel for="min_rating" value="Min Rating" />
                                        <TextInput
                                            id="min_rating"
                                            v-model="form.min_rating"
                                            type="number"
                                            class="mt-1 block w-full"
                                            placeholder="No minimum"
                                        />
                                        <InputError :message="form.errors.min_rating" class="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel for="max_rating" value="Max Rating" />
                                        <TextInput
                                            id="max_rating"
                                            v-model="form.max_rating"
                                            type="number"
                                            class="mt-1 block w-full"
                                            placeholder="No maximum"
                                        />
                                        <InputError :message="form.errors.max_rating" class="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <PrimaryButton :disabled="form.processing">Create Tournament</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

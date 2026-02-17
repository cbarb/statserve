<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    timezones: Array,
});

const form = useForm({
    name: '',
    timezone: '',
});

const submit = () => {
    form.post(route('groups.store'));
};
</script>

<template>
    <Head title="Create Group" />

    <AuthenticatedLayout :back-href="route('groups.index')">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Create Group</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="name" value="Group Name" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    autofocus
                                    placeholder="e.g. Friday Night Poker"
                                />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="timezone" value="Timezone (optional)" />
                                <select
                                    id="timezone"
                                    v-model="form.timezone"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Select a timezone...</option>
                                    <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                                </select>
                                <InputError :message="form.errors.timezone" class="mt-2" />
                            </div>

                            <div class="flex justify-end">
                                <PrimaryButton :disabled="form.processing">Create Group</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

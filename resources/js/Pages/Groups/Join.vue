<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    group: Object,
    code: String,
    isMember: Boolean,
});

const page = usePage();
const isAuthenticated = !!page.props.auth.user;

const form = useForm({});

const joinGroup = () => {
    form.post(route('groups.join', props.code));
};
</script>

<template>
    <Head :title="`Join ${group.name}`" />

    <div class="flex min-h-screen items-center justify-center bg-gray-100">
        <div class="w-full max-w-md overflow-hidden rounded-lg bg-white shadow-md">
            <div class="p-8 text-center">
                <h1 class="text-2xl font-bold text-gray-900">{{ group.name }}</h1>
                <p class="mt-2 text-gray-500">
                    {{ group.members_count }} {{ group.members_count === 1 ? 'member' : 'members' }}
                </p>

                <div class="mt-8">
                    <!-- Already a member -->
                    <template v-if="isMember">
                        <p class="text-sm text-gray-600">You're already in this group.</p>
                        <Link :href="route('groups.show', group.slug)" class="mt-4 inline-block">
                            <PrimaryButton>Go to Group</PrimaryButton>
                        </Link>
                    </template>

                    <!-- Authenticated, not a member -->
                    <template v-else-if="isAuthenticated">
                        <p class="text-sm text-gray-600">You've been invited to join this group.</p>
                        <div class="mt-4">
                            <PrimaryButton @click="joinGroup" :disabled="form.processing">
                                Join Group
                            </PrimaryButton>
                        </div>
                    </template>

                    <!-- Guest -->
                    <template v-else>
                        <p class="text-sm text-gray-600">Log in or create an account to join this group.</p>
                        <div class="mt-4 flex justify-center gap-3">
                            <Link :href="route('login', { redirect: `/join/${code}` })">
                                <PrimaryButton>Log In</PrimaryButton>
                            </Link>
                            <Link :href="route('register', { redirect: `/join/${code}` })">
                                <SecondaryButton>Create Account</SecondaryButton>
                            </Link>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

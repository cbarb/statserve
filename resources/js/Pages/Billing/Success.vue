<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Subscription Active" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="mx-auto max-w-lg sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Your subscription is active!</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Thank you for subscribing. You now have access to unlimited match logging.
                        </p>
                        <div class="mt-6 flex items-center justify-center gap-3">
                            <Link :href="route('dashboard')">
                                <PrimaryButton>Go to Dashboard</PrimaryButton>
                            </Link>
                            <Link :href="route('billing.index')" class="text-sm text-indigo-600 hover:text-indigo-500">
                                Manage subscription
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

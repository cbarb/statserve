<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    message: {
        type: String,
        default: 'This group has reached its weekly limit of 5 free matches.',
    },
});

defineEmits(['dismiss']);
</script>

<template>
    <div class="rounded-lg border border-amber-200 bg-amber-50 p-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.345 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-amber-800">Match Limit Reached</h3>
                <p class="mt-2 text-sm text-amber-700">{{ message }}</p>
                <div class="mt-4 flex items-center gap-3">
                    <Link :href="route('billing.index')">
                        <PrimaryButton class="text-xs">Upgrade Now</PrimaryButton>
                    </Link>
                    <SecondaryButton @click="$emit('dismiss')" class="text-xs">Dismiss</SecondaryButton>
                </div>
            </div>
        </div>
    </div>
</template>

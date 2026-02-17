<script setup>
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        default: '',
    },
    confirmLabel: {
        type: String,
        default: 'Confirm',
    },
    cancelLabel: {
        type: String,
        default: 'Cancel',
    },
    danger: {
        type: Boolean,
        default: false,
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
    <Modal :show="show" @close="emit('cancel')" max-width="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">{{ title }}</h2>
            <p v-if="message" class="mt-2 text-sm text-gray-600">{{ message }}</p>
            <slot />
            <div class="mt-6 flex justify-end space-x-3">
                <SecondaryButton @click="emit('cancel')">{{ cancelLabel }}</SecondaryButton>
                <DangerButton v-if="danger" @click="emit('confirm')" :disabled="processing">{{ confirmLabel }}</DangerButton>
                <PrimaryButton v-else @click="emit('confirm')" :disabled="processing">{{ confirmLabel }}</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

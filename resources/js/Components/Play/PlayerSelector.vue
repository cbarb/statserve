<script setup>
import { computed } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    members: Array,
    modelValue: Array,
    minCount: {
        type: Number,
        default: 2,
    },
});

const emit = defineEmits(['update:modelValue']);

const allSelected = computed(() => props.modelValue.length === props.members.length);

function toggle(id) {
    const current = [...props.modelValue];
    const index = current.indexOf(id);
    if (index === -1) {
        current.push(id);
    } else {
        current.splice(index, 1);
    }
    emit('update:modelValue', current);
}

function toggleAll() {
    if (allSelected.value) {
        emit('update:modelValue', []);
    } else {
        emit('update:modelValue', props.members.map(m => m.id));
    }
}
</script>

<template>
    <div>
        <div class="mb-4 flex items-center justify-between">
            <span class="text-sm text-gray-500">{{ modelValue.length }} of {{ members.length }} selected</span>
            <button type="button" @click="toggleAll" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                {{ allSelected ? 'Deselect All' : 'Select All' }}
            </button>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <button
                v-for="member in members"
                :key="member.id"
                type="button"
                @click="toggle(member.id)"
                class="flex items-center gap-3 rounded-lg border-2 p-3 text-left transition"
                :class="modelValue.includes(member.id) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'"
            >
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-medium"
                     :class="modelValue.includes(member.id) ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-600'">
                    {{ member.name.charAt(0).toUpperCase() }}
                </div>
                <span class="truncate text-sm font-medium text-gray-900">{{ member.name }}</span>
            </button>
        </div>
        <p v-if="modelValue.length < minCount" class="mt-2 text-sm text-red-600">
            Select at least {{ minCount }} players.
        </p>
    </div>
</template>

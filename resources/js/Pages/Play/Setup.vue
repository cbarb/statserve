<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PlayerSelector from '@/Components/Play/PlayerSelector.vue';
import FormatSelector from '@/Components/Play/FormatSelector.vue';
import TeamModeSelector from '@/Components/Play/TeamModeSelector.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    group: Object,
    members: Array,
    activeSession: Object,
});

const step = ref(1);

const form = useForm({
    player_ids: [],
    format: null,
    team_mode: null,
});

const minPlayers = computed(() => {
    if (form.format === 'doubles') return 4;
    return 2;
});

const canAdvanceStep1 = computed(() => form.player_ids.length >= 2);
const canAdvanceStep2 = computed(() => {
    if (!form.format) return false;
    if (form.format === 'doubles' && form.player_ids.length < 4) return false;
    return true;
});
const canSubmit = computed(() => !!form.team_mode);

function next() {
    if (step.value === 1 && canAdvanceStep1.value) step.value = 2;
    else if (step.value === 2 && canAdvanceStep2.value) step.value = 3;
}

function back() {
    if (step.value > 1) step.value--;
}

function submit() {
    form.post(route('play.create-session', props.group.slug));
}
</script>

<template>
    <Head :title="`Play — ${group.name}`" />

    <AuthenticatedLayout :back-href="route('groups.show', group.slug)">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Play — {{ group.name }}</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <!-- Resume active session banner -->
                <div v-if="activeSession" class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-amber-800">You have an active session</p>
                            <p class="text-sm text-amber-600">
                                {{ activeSession.format }} &middot; {{ activeSession.team_mode }} &middot;
                                Started {{ new Date(activeSession.started_at).toLocaleTimeString() }}
                            </p>
                        </div>
                        <Link :href="route('play.session', [group.slug, activeSession.id])">
                            <PrimaryButton>Resume</PrimaryButton>
                        </Link>
                    </div>
                </div>

                <!-- Step indicators -->
                <div class="mb-8 flex items-center justify-center gap-2">
                    <div v-for="s in 3" :key="s" class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium"
                            :class="s <= step ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-500'"
                        >
                            {{ s }}
                        </div>
                        <div v-if="s < 3" class="h-0.5 w-8" :class="s < step ? 'bg-indigo-500' : 'bg-gray-200'" />
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Step 1: Select Players -->
                        <div v-show="step === 1">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Who's playing?</h3>
                            <PlayerSelector
                                :members="members"
                                v-model="form.player_ids"
                                :min-count="2"
                            />
                        </div>

                        <!-- Step 2: Select Format -->
                        <div v-show="step === 2">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Match format</h3>
                            <FormatSelector
                                v-model="form.format"
                                :player-count="form.player_ids.length"
                            />
                        </div>

                        <!-- Step 3: Team Mode -->
                        <div v-show="step === 3">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">How should teams be assigned?</h3>
                            <TeamModeSelector v-model="form.team_mode" />
                        </div>

                        <!-- Navigation -->
                        <div class="mt-8 flex items-center justify-between">
                            <SecondaryButton v-if="step > 1" @click="back">Back</SecondaryButton>
                            <div v-else />

                            <PrimaryButton
                                v-if="step < 3"
                                @click="next"
                                :disabled="(step === 1 && !canAdvanceStep1) || (step === 2 && !canAdvanceStep2)"
                            >
                                Next
                            </PrimaryButton>

                            <PrimaryButton
                                v-if="step === 3"
                                @click="submit"
                                :disabled="!canSubmit || form.processing"
                            >
                                Start Session
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

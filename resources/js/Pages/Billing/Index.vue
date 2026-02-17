<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    isPro: Boolean,
    proSubscription: Object,
    groups: Array,
    prices: Object,
});

const proInterval = ref('monthly');
const boostInterval = ref('monthly');

const proForm = useForm({ interval: 'monthly' });
const boostForm = useForm({ interval: 'monthly' });
const cancelForm = useForm({});
const resumeForm = useForm({});
const portalForm = useForm({});

const showCancelModal = ref(false);
const cancelTarget = ref(null);

const proOnGracePeriod = computed(() => props.proSubscription?.on_grace_period ?? false);

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}

function upgradeToPro() {
    proForm.interval = proInterval.value;
    proForm.post(route('billing.checkout'));
}

function boostGroup(group) {
    boostForm.interval = boostInterval.value;
    boostForm.post(route('billing.boost-checkout', group.slug));
}

function openCancelPro() {
    cancelTarget.value = null;
    showCancelModal.value = true;
}

function openCancelBoost(group) {
    cancelTarget.value = group;
    showCancelModal.value = true;
}

function confirmCancel() {
    if (cancelTarget.value) {
        cancelForm.delete(route('billing.cancel-boost', cancelTarget.value.slug), {
            onSuccess: () => { showCancelModal.value = false; },
        });
    } else {
        cancelForm.delete(route('billing.cancel-pro'), {
            onSuccess: () => { showCancelModal.value = false; },
        });
    }
}

function resumePro() {
    resumeForm.post(route('billing.resume-pro'));
}

function resumeBoost(group) {
    resumeForm.post(route('billing.resume-boost', group.slug));
}

function openPortal() {
    portalForm.post(route('billing.portal'));
}
</script>

<template>
    <Head title="Billing" />

    <AuthenticatedLayout :back-href="route('dashboard')">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Billing</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">

                <!-- Pro Membership -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-medium text-gray-900">Pro Membership</h3>
                            <span v-if="isPro && !proOnGracePeriod" class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-800">
                                Active
                            </span>
                            <span v-if="proOnGracePeriod" class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                Cancelling
                            </span>
                        </div>

                        <!-- Active Pro (not on grace period) -->
                        <template v-if="isPro && !proOnGracePeriod">
                            <p class="mt-2 text-sm text-gray-600">
                                You have unlimited personal match logging across all your groups.
                            </p>
                            <div class="mt-4 flex items-center gap-3">
                                <SecondaryButton @click="openPortal" :disabled="portalForm.processing">
                                    Manage Payment Method
                                </SecondaryButton>
                                <DangerButton @click="openCancelPro" :disabled="cancelForm.processing">
                                    Cancel Pro
                                </DangerButton>
                            </div>
                        </template>

                        <!-- Pro on grace period -->
                        <template v-else-if="proOnGracePeriod">
                            <p class="mt-2 text-sm text-gray-600">
                                Your Pro subscription has been cancelled but remains active until
                                <strong>{{ formatDate(proSubscription.ends_at) }}</strong>.
                                You can renew anytime before then to keep your benefits.
                            </p>
                            <div class="mt-4 flex items-center gap-3">
                                <PrimaryButton @click="resumePro" :disabled="resumeForm.processing">
                                    Renew Pro
                                </PrimaryButton>
                                <SecondaryButton @click="openPortal" :disabled="portalForm.processing">
                                    Manage Payment Method
                                </SecondaryButton>
                            </div>
                        </template>

                        <!-- Not Pro — show upgrade -->
                        <template v-else>
                            <p class="mt-2 text-sm text-gray-600">
                                Unlimited personal match logging across all your groups. Your matches never count against a group's free weekly limit.
                            </p>
                            <div class="mt-4 flex items-center gap-4">
                                <label class="flex items-center gap-2">
                                    <input type="radio" v-model="proInterval" value="monthly" class="text-indigo-600" />
                                    <span class="text-sm">${{ prices.pro_monthly }}/month</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" v-model="proInterval" value="yearly" class="text-indigo-600" />
                                    <span class="text-sm">${{ prices.pro_yearly }}/year <span class="text-green-600 font-medium">(Save $24)</span></span>
                                </label>
                            </div>
                            <div class="mt-4">
                                <PrimaryButton @click="upgradeToPro" :disabled="proForm.processing">
                                    Upgrade to Pro
                                </PrimaryButton>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Group Boosts -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Group Boosts</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Boost a group to give <strong>all members</strong> unlimited match logging in that group.
                        </p>

                        <div class="mt-4 flex items-center gap-4">
                            <label class="flex items-center gap-2">
                                <input type="radio" v-model="boostInterval" value="monthly" class="text-amber-600" />
                                <span class="text-sm">${{ prices.boost_monthly }}/mo per group</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" v-model="boostInterval" value="yearly" class="text-amber-600" />
                                <span class="text-sm">${{ prices.boost_yearly }}/yr per group <span class="text-green-600 font-medium">(Save $50)</span></span>
                            </label>
                        </div>

                        <div class="mt-6 divide-y divide-gray-100">
                            <div v-for="group in groups" :key="group.id" class="flex items-center justify-between py-3">
                                <div>
                                    <p class="font-medium text-gray-900">{{ group.name }}</p>
                                    <p class="text-sm text-gray-500">
                                        <template v-if="group.boost_on_grace_period">
                                            <span class="font-medium text-amber-600">Boost cancelling</span>
                                            — active until {{ formatDate(group.boost_ends_at) }}
                                        </template>
                                        <template v-else-if="group.has_boost">
                                            <span class="font-medium text-amber-600">Boosted</span> — all members have unlimited logging
                                        </template>
                                        <template v-else>
                                            {{ group.weekly_match_count }}/5 free matches this week
                                        </template>
                                    </p>
                                </div>
                                <div>
                                    <template v-if="group.boost_on_grace_period">
                                        <PrimaryButton @click="resumeBoost(group)" :disabled="resumeForm.processing" class="text-xs">
                                            Renew Boost
                                        </PrimaryButton>
                                    </template>
                                    <template v-else-if="group.has_boost">
                                        <DangerButton @click="openCancelBoost(group)" class="text-xs">
                                            Cancel Boost
                                        </DangerButton>
                                    </template>
                                    <template v-else>
                                        <PrimaryButton @click="boostGroup(group)" :disabled="boostForm.processing" class="text-xs">
                                            Boost
                                        </PrimaryButton>
                                    </template>
                                </div>
                            </div>
                            <p v-if="!groups.length" class="py-3 text-sm text-gray-500">You're not a member of any groups yet.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Cancel Confirmation Modal -->
        <ConfirmationModal
            :show="showCancelModal"
            :title="cancelTarget ? 'Cancel Group Boost' : 'Cancel Pro Subscription'"
            :message="cancelTarget
                ? `Are you sure you want to cancel the boost for ${cancelTarget.name}? It will remain active until the end of the billing period.`
                : 'Are you sure you want to cancel your Pro subscription? It will remain active until the end of the billing period.'"
            confirm-label="Cancel Subscription"
            :danger="true"
            :processing="cancelForm.processing"
            @confirm="confirmCancel"
            @cancel="showCancelModal = false"
        />
    </AuthenticatedLayout>
</template>

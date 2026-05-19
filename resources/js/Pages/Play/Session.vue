<script setup>
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DuprBadge from '@/Components/DuprBadge.vue';
import CourtView from '@/Components/Play/CourtView.vue';
import MatchHistoryCard from '@/Components/Play/MatchHistoryCard.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import UpgradePrompt from '@/Components/UpgradePrompt.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    group: Object,
    session: Object,
    players: Array,
    matches: Array,
    nextAssignment: Object,
    canLogMatch: {
        type: Boolean,
        default: true,
    },
    hasBoost: Boolean,
    weeklyMatchCount: Number,
});

const page = usePage();
const flashError = computed(() => page.props.flash?.error);
const showUpgradePrompt = ref(!props.canLogMatch);

// XP toast
const xpToast = ref(null);
const showXpToast = ref(false);

// Badge toast
const badgeToast = ref([]);
const showBadgeToast = ref(false);

const LEVEL_NAMES = {
    1: 'Beginner', 2: 'Novice', 3: 'Rally Ready', 4: 'Court Regular',
    5: 'Kitchen King', 6: 'Dink Master', 7: 'Net Ninja', 8: 'Court Commander',
    9: 'Grand Dinkster', 10: 'Pickle Legend',
};

const TIER_COLORS = {
    bronze: 'border-amber-300 bg-amber-50 text-amber-800',
    silver: 'border-gray-300 bg-gray-50 text-gray-700',
    gold: 'border-yellow-400 bg-yellow-50 text-yellow-800',
    platinum: 'border-purple-400 bg-purple-50 text-purple-800',
};

watch(() => page.props.flash?.xp_awarded, (xpData) => {
    if (!xpData) return;
    const currentUserId = page.props.auth.user.id;
    const myXp = xpData[currentUserId];
    if (!myXp) return;

    const levelName = LEVEL_NAMES[myXp.new_level] || LEVEL_NAMES[10];
    xpToast.value = {
        xp: myXp.xp_gained,
        leveledUp: myXp.leveled_up,
        newLevel: myXp.new_level,
        levelName,
    };
    showXpToast.value = true;
    setTimeout(() => { showXpToast.value = false; }, 4000);
}, { immediate: true });

watch(() => page.props.flash?.badges_earned, (badgeData) => {
    if (!badgeData) return;
    const currentUserId = page.props.auth.user.id;
    const myBadges = badgeData[currentUserId];
    if (!myBadges || !myBadges.length) return;

    badgeToast.value = myBadges;
    showBadgeToast.value = true;
    setTimeout(() => { showBadgeToast.value = false; }, 5000);
}, { immediate: true });

const isActive = computed(() => props.session.status === 'active');

const isPro = page.props.subscription?.is_pro;
const showMatchCounter = computed(() => !props.hasBoost && !isPro && isActive.value);
const isCompleted = computed(() => props.session.status === 'completed');
const isHandpick = computed(() => props.session.team_mode === 'handpick');
const isDoubles = computed(() => props.session.format === 'doubles');

// Phase: 'court' = arranging teams, 'scoring' = entering scores (court still visible)
const phase = ref('court');

// Team assignments
const team1 = ref(props.nextAssignment?.team_1 || []);
const team2 = ref(props.nextAssignment?.team_2 || []);
const sittingOut = ref(props.nextAssignment?.sitting_out || []);

// Teams complete check
const expectedPerTeam = computed(() => isDoubles.value ? 2 : 1);
const teamsComplete = computed(() =>
    team1.value.length === expectedPerTeam.value &&
    team2.value.length === expectedPerTeam.value
);

// Scores (bound to CourtView kitchen zones)
const team1Score = ref(0);
const team2Score = ref(0);
const scoresValid = computed(() =>
    team1Score.value !== team2Score.value &&
    (team1Score.value > 0 || team2Score.value > 0)
);

// Match form
const matchForm = useForm({
    team_1_score: 0,
    team_2_score: 0,
    players: [],
});

function startGame() {
    matchForm.players = [
        ...team1.value.map(p => ({ user_id: p.user_id, team: 1, position: p.position })),
        ...team2.value.map(p => ({ user_id: p.user_id, team: 2, position: p.position })),
    ];
    phase.value = 'scoring';
}

function saveAndNext() {
    matchForm.team_1_score = team1Score.value;
    matchForm.team_2_score = team2Score.value;
    matchForm.post(route('play.store-match', [props.group.slug, props.session.id]), {
        preserveScroll: true,
        onSuccess: () => {
            phase.value = 'court';
            team1Score.value = 0;
            team2Score.value = 0;
            team1.value = props.nextAssignment?.team_1 || [];
            team2.value = props.nextAssignment?.team_2 || [];
            sittingOut.value = props.nextAssignment?.sitting_out || [];
        },
    });
}

function saveAndEnd() {
    matchForm.team_1_score = team1Score.value;
    matchForm.team_2_score = team2Score.value;
    matchForm.post(route('play.store-match', [props.group.slug, props.session.id]), {
        onSuccess: () => {
            router.post(route('play.end-session', [props.group.slug, props.session.id]));
        },
    });
}

// End session without saving a match
const showEndModal = ref(false);
const endForm = useForm({});
function endSessionOnly() {
    endForm.post(route('play.end-session', [props.group.slug, props.session.id]), {
        onSuccess: () => { showEndModal.value = false; },
    });
}

// Shuffle (random mode)
function shuffle() {
    router.reload({ only: ['nextAssignment'], onSuccess: () => {
        team1.value = props.nextAssignment?.team_1 || [];
        team2.value = props.nextAssignment?.team_2 || [];
        sittingOut.value = props.nextAssignment?.sitting_out || [];
    }});
}

// Player name helper
function getPlayerName(userId) {
    return props.players.find(p => p.id === userId)?.name || '?';
}

// Session summary stats
const playerStats = computed(() => {
    const stats = {};
    props.players.forEach(p => {
        stats[p.id] = { name: p.name, dupr_id: p.dupr_id, wins: 0, losses: 0, played: 0 };
    });
    props.matches.forEach(m => {
        m.players.forEach(p => {
            if (!stats[p.user_id]) return;
            stats[p.user_id].played++;
            if (m.winning_team === p.team) stats[p.user_id].wins++;
            else stats[p.user_id].losses++;
        });
    });
    return Object.values(stats).sort((a, b) => b.wins - a.wins);
});

// Sitting out display
const sittingOutNames = computed(() => sittingOut.value.map(id => getPlayerName(id)));
</script>

<template>
    <Head :title="`Session — ${group.name}`" />

    <AuthenticatedLayout :back-href="route('groups.show', group.slug)">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ group.name }}
                    <span class="text-sm font-normal text-gray-500">
                        &middot; {{ session.format }} &middot; {{ session.team_mode }}
                    </span>
                </h2>
                <div v-if="isActive" class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                        Live
                    </span>
                    <DangerButton @click="showEndModal = true" class="text-xs">End Session</DangerButton>
                </div>
                <span v-else class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                    Completed
                </span>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">

                <!-- Upgrade Prompt -->
                <UpgradePrompt
                    v-if="showUpgradePrompt && isActive"
                    @dismiss="showUpgradePrompt = false"
                />

                <!-- Flash Error -->
                <div v-if="flashError" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {{ flashError }}
                </div>

                <!-- XP Toast -->
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="translate-y-2 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="translate-y-2 opacity-0"
                >
                    <div
                        v-if="showXpToast && xpToast"
                        class="rounded-lg border p-4 text-sm"
                        :class="xpToast.leveledUp
                            ? 'border-yellow-300 bg-yellow-50 text-yellow-800'
                            : 'border-indigo-200 bg-indigo-50 text-indigo-700'"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">+{{ xpToast.xp }} XP</span>
                            <span v-if="xpToast.leveledUp" class="font-bold">
                                Level Up! Lv. {{ xpToast.newLevel }} &mdash; {{ xpToast.levelName }}
                            </span>
                        </div>
                    </div>
                </Transition>

                <!-- Badge Toast -->
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="translate-y-2 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="translate-y-2 opacity-0"
                >
                    <div v-if="showBadgeToast && badgeToast.length" class="space-y-2">
                        <div
                            v-for="badge in badgeToast"
                            :key="badge.badge_id"
                            class="rounded-lg border p-4 text-sm"
                            :class="TIER_COLORS[badge.tier] || TIER_COLORS.bronze"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">Badge Earned: {{ badge.name }}</span>
                                <span class="text-xs capitalize opacity-75">{{ badge.tier }}</span>
                            </div>
                            <p v-if="badge.xp_reward" class="mt-0.5 text-xs opacity-75">+{{ badge.xp_reward }} XP bonus</p>
                        </div>
                    </div>
                </Transition>

                <!-- ACTIVE SESSION -->
                <template v-if="isActive">
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900">
                                    Game {{ matches.length + 1 }}
                                    <span v-if="phase === 'scoring'" class="ml-2 text-sm font-normal text-gray-500">
                                        — Enter scores
                                    </span>
                                </h3>
                                <div class="flex items-center gap-2">
                                    <SecondaryButton v-if="phase === 'court' && !isHandpick" @click="shuffle" class="text-xs">
                                        Shuffle
                                    </SecondaryButton>
                                </div>
                            </div>

                            <!-- Court is always visible -->
                            <CourtView
                                :format="session.format"
                                v-model:team1="team1"
                                v-model:team2="team2"
                                v-model:sitting-out="sittingOut"
                                :editable="isHandpick && phase === 'court'"
                                :all-players="players"
                                :scoring="phase === 'scoring'"
                                v-model:team1-score="team1Score"
                                v-model:team2-score="team2Score"
                            />

                            <!-- Sitting out -->
                            <div v-if="sittingOutNames.length" class="mt-4 text-center">
                                <p class="text-sm text-gray-500">
                                    Sitting out: {{ sittingOutNames.join(', ') }}
                                </p>
                            </div>

                            <!-- Errors -->
                            <p v-if="matchForm.errors.team_1_score" class="mt-2 text-center text-sm text-red-600">{{ matchForm.errors.team_1_score }}</p>
                            <p v-if="matchForm.errors.players" class="mt-2 text-center text-sm text-red-600">{{ matchForm.errors.players }}</p>

                            <!-- Action buttons -->
                            <div class="mt-6 flex items-center justify-center gap-3">
                                <template v-if="phase === 'court'">
                                    <PrimaryButton @click="startGame" :disabled="!teamsComplete">
                                        Start Game
                                    </PrimaryButton>
                                </template>
                                <template v-else>
                                    <SecondaryButton @click="phase = 'court'">Back</SecondaryButton>
                                    <PrimaryButton @click="saveAndNext" :disabled="matchForm.processing || !scoresValid">
                                        Save & Next Game
                                    </PrimaryButton>
                                    <PrimaryButton @click="saveAndEnd" :disabled="matchForm.processing || !scoresValid">
                                        Save & End
                                    </PrimaryButton>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- COMPLETED SESSION SUMMARY -->
                <template v-if="isCompleted">
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900">Session Summary</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ matches.length }} {{ matches.length === 1 ? 'game' : 'games' }} played</p>

                            <div class="mt-6">
                                <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Player Results</h4>
                                <div class="divide-y divide-gray-100">
                                    <div v-for="stat in playerStats" :key="stat.name" class="flex items-center justify-between py-2">
                                        <span class="font-medium text-gray-900">{{ stat.name }} <DuprBadge :dupr-id="stat.dupr_id" /></span>
                                        <span class="text-sm">
                                            <span class="font-semibold text-green-700">{{ stat.wins }}W</span>
                                            <span class="mx-1 text-gray-300">-</span>
                                            <span class="font-semibold text-red-600">{{ stat.losses }}L</span>
                                            <span class="ml-2 text-gray-400">({{ stat.played }} played)</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Match Counter Pill -->
                <div v-if="showMatchCounter" class="flex justify-center">
                    <span
                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
                        :class="weeklyMatchCount >= 4 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600'"
                    >
                        {{ weeklyMatchCount }}/5 free matches this week
                    </span>
                </div>

                <!-- Match History -->
                <div v-if="matches.length" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">Match History</h3>
                        <div class="space-y-2">
                            <MatchHistoryCard v-for="match in matches" :key="match.id" :match="match" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- End Session Confirmation Modal -->
        <ConfirmationModal
            :show="showEndModal"
            title="End Session"
            message="Are you sure you want to end this session? No more games can be added."
            confirm-label="End Session"
            :danger="true"
            :processing="endForm.processing"
            @confirm="endSessionOnly"
            @cancel="showEndModal = false"
        />
    </AuthenticatedLayout>
</template>

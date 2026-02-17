<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ApplicationLogoDark from '@/Components/ApplicationLogoDark.vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="Pickleball Stat Tracking" />

    <!-- Nav -->
    <nav class="bg-gray-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center">
                    <ApplicationLogoDark class="h-10 w-auto" />
                </div>
                <div v-if="canLogin" class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-600"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-sm font-medium text-gray-300 transition hover:text-white"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-600"
                        >
                            Register
                        </Link>
                    </template>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="bg-gray-900 py-20 sm:py-28">
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">
                Track Every Point. <br class="" />
                <span class="text-emerald-400">Settle Every Score.</span>
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-gray-400 sm:text-xl">
                The easiest way for your pickleball group to log matches, track stats, and see who really runs the court.
            </p>
            <div class="mt-10 flex items-center justify-center gap-4">
                <Link
                    v-if="$page.props.auth?.user"
                    :href="route('dashboard')"
                    class="rounded-lg bg-emerald-500 px-8 py-3 text-lg font-semibold text-white shadow-lg transition hover:bg-emerald-600"
                >
                    Go to Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="route('register')"
                        class="rounded-lg bg-emerald-500 px-8 py-3 text-lg font-semibold text-white shadow-lg transition hover:bg-emerald-600"
                    >
                        Get Started Free
                    </Link>
                    <Link
                        :href="route('login')"
                        class="rounded-lg border border-gray-600 px-8 py-3 text-lg font-semibold text-gray-300 transition hover:border-gray-400 hover:text-white"
                    >
                        Log in
                    </Link>
                </template>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="bg-white py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl">Everything your group needs</h2>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-gray-600">
                    From casual rallies to heated rivalries, Stat Serve keeps track of it all.
                </p>
            </div>
            <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Interactive Court -->
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-6 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
                        <svg class="h-7 w-7 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25A2.25 2.25 0 0 1 5.25 3h13.5A2.25 2.25 0 0 1 21 5.25Z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">Interactive Court</h3>
                    <p class="mt-2 text-sm text-gray-600">Tap the court to log shots and track where every point is won or lost.</p>
                </div>

                <!-- Detailed Stats -->
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-6 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
                        <svg class="h-7 w-7 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">Detailed Stats</h3>
                    <p class="mt-2 text-sm text-gray-600">Win rates, head-to-head records, partnerships, and player breakdowns at a glance.</p>
                </div>

                <!-- XP & Badges -->
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-6 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
                        <svg class="h-7 w-7 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .982-3.172M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M2.25 18.75V7.5A2.25 2.25 0 0 1 4.5 5.25h15A2.25 2.25 0 0 1 21.75 7.5v11.25" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">XP & Badges</h3>
                    <p class="mt-2 text-sm text-gray-600">Earn experience points, level up, and unlock badges as you play more matches.</p>
                </div>

                <!-- Groups & Social -->
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-6 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
                        <svg class="h-7 w-7 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">Groups & Social</h3>
                    <p class="mt-2 text-sm text-gray-600">Create groups, invite friends, and keep your pickleball crew connected.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="bg-gray-50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl">How it works</h2>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-gray-600">
                    Get up and running in minutes.
                </p>
            </div>
            <div class="mt-16 grid gap-12 sm:grid-cols-3">
                <div class="text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500 text-xl font-bold text-white">1</div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">Create a Group</h3>
                    <p class="mt-2 text-sm text-gray-600">Set up your pickleball crew and invite players with a simple link.</p>
                </div>
                <div class="text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500 text-xl font-bold text-white">2</div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">Log Matches</h3>
                    <p class="mt-2 text-sm text-gray-600">Record scores after each game using our quick match entry flow.</p>
                </div>
                <div class="text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500 text-xl font-bold text-white">3</div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">Track Stats</h3>
                    <p class="mt-2 text-sm text-gray-600">See who leads the pack with detailed stats, leaderboards, and badges.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Proof / Stats -->
    <section class="bg-gray-900 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div class="text-center">
                    <div class="text-4xl font-extrabold text-emerald-400">10,000+</div>
                    <div class="mt-2 text-sm text-gray-400">Matches Logged</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-extrabold text-emerald-400">2,500+</div>
                    <div class="mt-2 text-sm text-gray-400">Players</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-extrabold text-emerald-400">500+</div>
                    <div class="mt-2 text-sm text-gray-400">Groups</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-extrabold text-emerald-400">1M+</div>
                    <div class="mt-2 text-sm text-gray-400">Points Tracked</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="bg-white py-20">
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl">Ready to settle the score?</h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-gray-600">
                Join your friends on Stat Serve and find out who really dominates the court.
            </p>
            <div class="mt-8">
                <Link
                    v-if="$page.props.auth?.user"
                    :href="route('dashboard')"
                    class="inline-block rounded-lg bg-emerald-500 px-8 py-3 text-lg font-semibold text-white shadow-lg transition hover:bg-emerald-600"
                >
                    Go to Dashboard
                </Link>
                <Link
                    v-else
                    :href="route('register')"
                    class="inline-block rounded-lg bg-emerald-500 px-8 py-3 text-lg font-semibold text-white shadow-lg transition hover:bg-emerald-600"
                >
                    Get Started Free
                </Link>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-50 py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-between">
                <ApplicationLogo class="h-8 w-auto" />
                <p class="text-sm text-gray-500">&copy; {{ new Date().getFullYear() }} Stat Serve. All rights reserved.</p>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ApplicationLogoDark from '@/Components/ApplicationLogoDark.vue';
</script>

<template>
    <Head title="Pricing" />

    <div class="flex min-h-screen flex-col">
    <!-- Nav -->
    <nav class="bg-gray-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <Link href="/" class="flex items-center">
                    <ApplicationLogoDark class="h-10 w-auto" />
                </Link>
                <div class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth?.user"
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

    <!-- Header -->
    <section class="bg-gray-900 pb-36 pt-16">
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                Simple, transparent pricing
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-gray-400">
                Start free. Upgrade when your crew gets serious.
            </p>
        </div>
    </section>

    <!-- Pricing Cards -->
    <section class="-mt-20 flex-1 pb-20">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-3">

                <!-- Free -->
                <div class="flex flex-col rounded-2xl border border-gray-200 bg-white pt-12 pb-8 px-8 shadow-sm">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Free</h3>
                        <p class="mt-1 text-sm text-gray-500">For casual groups getting started</p>
                    </div>
                    <div class="mt-6">
                        <span class="text-4xl font-extrabold text-gray-900">$0</span>
                        <span class="text-sm text-gray-500">/mo</span>
                    </div>
                    <ul class="mt-8 flex-1 space-y-3">
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-gray-700">5 matches/week per group</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-gray-700">Daily, weekly & monthly stats</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-gray-700">XP, levels & badges</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-gray-700">Groups & leaderboards</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-gray-700">Head-to-head & partnership stats</span>
                        </li>
                    </ul>
                    <div class="mt-8">
                        <Link
                            :href="$page.props.auth?.user ? route('dashboard') : route('register')"
                            class="block w-full rounded-lg border border-gray-300 py-3 text-center text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                        >
                            {{ $page.props.auth?.user ? 'Current Plan' : 'Get Started' }}
                        </Link>
                    </div>
                </div>

                <!-- Pro -->
                <div class=" flex flex-col rounded-2xl border-2 border-emerald-500 bg-white px-8 pb-10 pt-12 shadow-lg">
                    <!-- <div class="">
                        <span class="rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-white">
                            Personal
                        </span>
                    </div> -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Pro</h3>
                        <p class="mt-1 text-sm text-gray-500">For players who never want to stop</p>
                    </div>
                    <div class="mt-6">
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl font-extrabold text-gray-900">$5</span>
                            <span class="text-sm text-gray-500">/mo</span>
                        </div>
                        <span class="mt-1 block text-sm text-gray-400">billed annually</span>
                    </div>
                    <p class="mt-1 text-sm font-medium text-emerald-600">Save $24/yr vs monthly ($7/mo)</p>
                    <ul class="mt-8 flex-1 space-y-3">
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-gray-700"><strong>Unlimited</strong> personal match logging</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-gray-700"><strong>All-time</strong> stats &mdash; not just 30 days</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-gray-700">Your matches <strong>never</strong> count against a group's free limit</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-gray-700">Works across <strong>all</strong> your groups</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-gray-700">Everything in Free</span>
                        </li>
                    </ul>
                    <div class="mt-8">
                        <Link
                            :href="$page.props.auth?.user ? route('billing.index') : route('register')"
                            class="block w-full rounded-lg bg-emerald-500 py-3 text-center text-sm font-semibold text-white transition hover:bg-emerald-600"
                        >
                            {{ $page.props.auth?.user ? 'Upgrade to Pro' : 'Get Started' }}
                        </Link>
                    </div>
                </div>

                <!-- Group Boost -->
                <div class=" py-8 flex flex-col rounded-2xl border-2 border-amber-400 bg-white px-8 pb-10 pt-12 shadow-lg">
                    <!-- <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                        <span class="whitespace-nowrap rounded-full bg-amber-400 px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-gray-900">
                            Group Boost
                        </span>
                    </div> -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Group Boost</h3>
                        <p class="mt-1 text-sm text-gray-500">Unlock a group for everyone in it</p>
                    </div>
                    <div class="mt-6">
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl font-extrabold text-gray-900">$10.83</span>
                            <span class="text-sm text-gray-500">/mo</span>
                        </div>
                        <span class="mt-1 block text-sm text-gray-400">billed annually</span>
                    </div>
                    <p class="mt-1 text-sm font-medium text-amber-600">Save $50/yr vs monthly ($15/mo)</p>
                    <ul class="mt-8 flex-1 space-y-3">
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-gray-700"><strong>Unlimited</strong> logging for <strong>all</strong> group members</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-gray-700"><strong>All-time</strong> stats for everyone in the group</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-gray-700">No one in the group hits a weekly limit</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-gray-700">One person pays, <strong>entire group</strong> benefits</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-gray-700">Applied per group &mdash; boost only the groups you need</span>
                        </li>
                    </ul>
                    <div class="mt-8">
                        <Link
                            :href="$page.props.auth?.user ? route('billing.index') : route('register')"
                            class="block w-full rounded-lg bg-amber-400 py-3 text-center text-sm font-semibold text-gray-900 transition hover:bg-amber-500"
                        >
                            {{ $page.props.auth?.user ? 'Boost a Group' : 'Get Started' }}
                        </Link>
                    </div>
                </div>

            </div>

            <!-- Clarification note -->
            <p class="mx-auto mt-8 max-w-2xl text-center text-sm text-gray-500">
                Pro and Group Boost are independent &mdash; Pro upgrades <strong>your</strong> account across all groups,
                while a Boost unlocks a <strong>specific group</strong> for every member. Use one, the other, or both.
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="mt-auto bg-gray-50 py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-between">
                <ApplicationLogo class="h-8 w-auto" />
                <p class="text-sm text-gray-500">&copy; {{ new Date().getFullYear() }} Stat Serve. All rights reserved.</p>
            </div>
        </div>
    </footer>
    </div>
</template>

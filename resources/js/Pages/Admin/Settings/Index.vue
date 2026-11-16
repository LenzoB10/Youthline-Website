<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, DoorOpen, LayoutGrid, Package, School, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    currentYear: { type: Object, default: null },
    currentTerm: { type: Object, default: null },
    counts: { type: Object, default: () => ({}) },
    levels: { type: Array, default: () => [] },
});

const cards = computed(() => [
    {
        name: 'Academic Calendar',
        description: 'Academic years and terms, and which ones are current.',
        icon: CalendarDays,
        route: 'admin.settings.years.index',
        meta: props.currentYear ? `Current: ${props.currentYear.name}` : 'No current year set',
    },
    {
        name: 'Institutions',
        description: 'Secondary schools, colleges and universities students attend.',
        icon: School,
        route: 'admin.settings.institutions.index',
        meta: `${props.counts.institutions ?? 0} institutions`,
    },
    {
        name: 'Programs',
        description: 'The six programs — shared with the public website.',
        icon: LayoutGrid,
        route: 'admin.website.programs.index',
        meta: `${props.counts.programs ?? 0} programs`,
    },
    {
        name: 'Item Categories',
        description: 'Basic-needs items handed out to students, with unit costs.',
        icon: Package,
        route: 'admin.settings.item-categories.index',
        meta: `${props.counts.item_categories ?? 0} categories`,
    },
    {
        name: 'Exit Reasons',
        description: 'Why a student leaves — chosen whenever a student exits.',
        icon: DoorOpen,
        route: 'admin.settings.exit-reasons.index',
        meta: `${props.counts.exit_reasons ?? 0} active reasons`,
    },
    {
        name: 'Approvals & Policies',
        description: 'Payment and repair limits, approvals, and counselling-note privacy.',
        icon: ShieldCheck,
        route: 'admin.settings.approvals.edit',
        meta: null,
    },
]);

const secondary = computed(() => props.levels.filter((level) => level.stage === 'secondary'));
const tertiary = computed(() => props.levels.filter((level) => level.stage === 'tertiary'));
</script>

<template>
    <Head title="Settings" />

    <AuthenticatedLayout>
        <h1 class="page-heading">Settings</h1>
        <p class="mb-6 text-sm text-text-muted dark:text-text-dark-muted">
            The shared lists every part of the system relies on. Keep these up to date so student
            records stay consistent.
        </p>

        <div
            class="mb-6 flex flex-wrap items-center gap-x-6 gap-y-2 rounded-lg border border-mustGreen/30 bg-mustGreen/5 px-4 py-3 text-sm"
        >
            <span class="font-semibold text-mustBlue dark:text-text-inverted">Current period</span>
            <span class="text-text-muted dark:text-text-dark-muted">
                Academic year:
                <strong class="text-mustBlue dark:text-text-inverted">{{ currentYear?.name ?? 'not set' }}</strong>
            </span>
            <span class="text-text-muted dark:text-text-dark-muted">
                Term:
                <strong class="text-mustBlue dark:text-text-inverted">{{ currentTerm?.name ?? 'not set' }}</strong>
            </span>
            <Link :href="route('admin.settings.years.index')" class="text-sm font-medium text-mustGreen hover:underline">
                Change
            </Link>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="card in cards"
                :key="card.route"
                :href="route(card.route)"
                class="card-container flex items-start gap-4 transition hover:shadow-lg"
            >
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-mustGreen/10 text-mustGreen">
                    <component :is="card.icon" class="h-5 w-5" />
                </span>
                <div>
                    <h2 class="font-semibold text-mustBlue dark:text-text-inverted">{{ card.name }}</h2>
                    <p class="text-sm text-text-muted">{{ card.description }}</p>
                    <p v-if="card.meta" class="mt-2 text-xs font-medium text-mustGreen">{{ card.meta }}</p>
                </div>
            </Link>
        </div>

        <div class="card-container mt-6">
            <h2 class="page-heading-2 !mb-1">Education levels</h2>
            <p class="mb-4 text-sm text-text-muted">
                These are fixed because promotion follows their order. Form 4 to university is
                recorded as a separate step, not an automatic promotion.
            </p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-text-muted">Secondary</p>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="level in secondary" :key="level.value" class="rounded-display">{{ level.label }}</span>
                    </div>
                </div>
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-text-muted">College & university</p>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="level in tertiary" :key="level.value" class="rounded-display">{{ level.label }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

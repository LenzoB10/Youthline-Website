<script setup>
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, CheckCircle2, RotateCcw, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    year: { type: String, default: null },
    ready: { type: Array, default: () => [] }, // can move up a level
    decide: { type: Array, default: () => [] }, // top of a stage: can only repeat here
    unplaced: { type: Array, default: () => [] },
});

// Everyone who can be given a decision on this screen.
const rows = computed(() => [...props.ready, ...props.decide]);

// decisions[studentId] = 'promote' | 'repeat'; missing = not now
const decisions = ref({});
const decisionOf = (id) => decisions.value[id] ?? null;
const setDecision = (row, decision) => {
    if (decision === 'promote' && !row.can_promote) return;
    const next = { ...decisions.value };
    if (decision) next[row.student_id] = decision;
    else delete next[row.student_id];
    decisions.value = next;
};

// --- Filters ---
const search = ref('');
const levelFilter = ref('');
const levels = computed(() => {
    const seen = new Map();
    rows.value.forEach((r) => r.from_level && seen.set(r.from_level, r.from_label));
    return [...seen].map(([value, label]) => ({ value, label }));
});
const shown = computed(() => {
    const term = search.value.trim().toLowerCase();
    return rows.value.filter(
        (r) => (!levelFilter.value || r.from_level === levelFilter.value) && (!term || r.name.toLowerCase().includes(term)),
    );
});

// --- Selection & bulk actions ---
const selected = ref(new Set());
const isSelected = (id) => selected.value.has(id);
const toggleSelected = (id) => {
    const next = new Set(selected.value);
    next.has(id) ? next.delete(id) : next.add(id);
    selected.value = next;
};
const allShownSelected = computed(() => shown.value.length > 0 && shown.value.every((r) => selected.value.has(r.student_id)));
const toggleAllShown = () => {
    const next = new Set(selected.value);
    shown.value.forEach((r) => (allShownSelected.value ? next.delete(r.student_id) : next.add(r.student_id)));
    selected.value = next;
};
const selectedRows = computed(() => rows.value.filter((r) => selected.value.has(r.student_id)));
const selectedCantPromote = computed(() => selectedRows.value.filter((r) => !r.can_promote).length);
const applyToSelected = (decision) => {
    selectedRows.value.forEach((r) => setDecision(r, decision));
    selected.value = new Set();
};

// --- Totals ---
const promoteCount = computed(() => Object.values(decisions.value).filter((d) => d === 'promote').length);
const repeatCount = computed(() => Object.values(decisions.value).filter((d) => d === 'repeat').length);
const undecidedCount = computed(() => rows.value.length - promoteCount.value - repeatCount.value);

// --- Submit ---
const form = useForm({ decisions: {} });
const confirming = ref(false);
const submit = () => {
    confirming.value = false;
    form.transform(() => ({ decisions: decisions.value })).post(route('students.promote.store'), {
        preserveScroll: true,
        onSuccess: () => {
            decisions.value = {};
            selected.value = new Set();
        },
    });
};

const segment = (row, value) =>
    decisionOf(row.student_id) === value
        ? value === 'promote'
            ? 'bg-mustGreen text-white'
            : value === 'repeat'
              ? 'bg-amber-500 text-white'
              : 'bg-gray-500 text-white'
        : 'bg-white text-text-muted hover:bg-gray-50 dark:bg-transparent dark:hover:bg-white/5';
</script>

<template>
    <Head title="Promote students" />

    <AuthenticatedLayout>
        <Link :href="route('students.index')" class="mb-4 inline-flex items-center gap-1 text-sm text-mustBlue dark:text-text-dark-muted">
            <ArrowLeft class="h-4 w-4" /> Students
        </Link>
        <h1 class="page-heading">Promote students</h1>

        <div v-if="!year" class="card-container py-10 text-center text-sm text-text-muted">
            No current academic year is set. An Admin needs to add the new year and make it current under
            <strong>Settings → Academic Calendar</strong> before promoting.
        </div>

        <template v-else>
            <p class="mb-6 text-sm text-text-muted dark:text-text-dark-muted">
                Choose what happens to each student in <strong>{{ year }}</strong>:
                <strong class="text-mustGreen">Promote</strong> moves them up a level,
                <strong class="text-amber-600">Repeat</strong> keeps them at the same level, and
                <strong>Not now</strong> leaves them for later. Tick several students to set them all at once.
                A new {{ year }} record is added — earlier years are never changed.
            </p>

            <div v-if="!rows.length && !unplaced.length" class="card-container flex flex-col items-center py-10 text-center">
                <CheckCircle2 class="h-10 w-10 text-mustGreen" />
                <p class="mt-2 font-medium text-mustBlue dark:text-text-inverted">Everyone is already placed for {{ year }}.</p>
                <p class="text-sm text-text-muted">Active students without a {{ year }} record will appear here.</p>
            </div>

            <section v-if="rows.length" class="card-container mb-4">
                <!-- Toolbar -->
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <div class="relative w-full max-w-xs">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" />
                        <label for="promote_search" class="sr-only">Search students</label>
                        <input id="promote_search" v-model="search" type="search" placeholder="Search by name" class="text-input w-full !pl-9" />
                    </div>
                    <label for="promote_level" class="sr-only">Filter by level</label>
                    <select id="promote_level" v-model="levelFilter" class="text-input">
                        <option value="">All levels</option>
                        <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                    </select>

                    <div v-if="selected.size" class="ml-auto flex flex-wrap items-center gap-2 rounded-lg bg-mustGreen/5 px-3 py-2 text-sm">
                        <span class="font-medium text-mustBlue dark:text-text-inverted">{{ selected.size }} selected</span>
                        <button type="button" class="btn-primary-sm" :disabled="selectedCantPromote === selected.size" @click="applyToSelected('promote')">Promote</button>
                        <button type="button" class="rounded-md bg-amber-500 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-white hover:bg-amber-600" @click="applyToSelected('repeat')">Repeat</button>
                        <button type="button" class="btn-outline-sm" @click="applyToSelected(null)">Not now</button>
                        <span v-if="selectedCantPromote" class="w-full text-xs text-text-muted">
                            {{ selectedCantPromote }} selected {{ selectedCantPromote === 1 ? 'is' : 'are' }} at the top of a stage and can only repeat — they're skipped by “Promote”.
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                                <th class="w-10 px-3 py-2">
                                    <input type="checkbox" class="checkbox-input" :checked="allShownSelected" aria-label="Select all shown students" @change="toggleAllShown" />
                                </th>
                                <th class="px-3 py-2">Student</th>
                                <th class="px-3 py-2">In {{ year }}</th>
                                <th class="px-3 py-2">School / university</th>
                                <th class="px-3 py-2">Decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!shown.length">
                                <td colspan="5" class="px-3 py-6 text-center text-text-muted">No students match.</td>
                            </tr>
                            <tr
                                v-for="row in shown"
                                :key="row.student_id"
                                class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark"
                                :class="{
                                    'bg-green-50/60 dark:bg-green-500/5': decisionOf(row.student_id) === 'promote',
                                    'bg-amber-50/70 dark:bg-amber-500/5': decisionOf(row.student_id) === 'repeat',
                                }"
                            >
                                <td class="px-3 py-2">
                                    <input type="checkbox" class="checkbox-input" :checked="isSelected(row.student_id)" :aria-label="`Select ${row.name}`" @change="toggleSelected(row.student_id)" />
                                </td>
                                <td class="px-3 py-2">
                                    <Link :href="route('students.show', row.student_id)" class="font-medium text-mustBlue hover:underline dark:text-text-inverted">{{ row.name }}</Link>
                                    <span v-if="row.from_year" class="block text-xs text-text-muted">{{ row.from_label }} in {{ row.from_year }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2">
                                    <template v-if="decisionOf(row.student_id) === 'promote'">
                                        {{ row.from_label }} <ArrowRight class="inline h-3.5 w-3.5 text-mustGreen" /> <strong>{{ row.to_label }}</strong>
                                    </template>
                                    <template v-else-if="decisionOf(row.student_id) === 'repeat'">
                                        <RotateCcw class="inline h-3.5 w-3.5 text-amber-600" /> <strong>{{ row.from_label }}</strong> again
                                    </template>
                                    <span v-else-if="row.can_promote" class="text-text-muted">{{ row.from_label }} → {{ row.to_label }}?</span>
                                    <span v-else class="text-text-muted">
                                        Finished {{ row.from_label }} —
                                        <Link :href="`${route('students.show', row.student_id)}#education`" class="text-mustGreen hover:underline">decide on profile</Link>
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-text-muted">{{ row.institution ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <div class="inline-flex overflow-hidden rounded-lg border border-mustBorder-light text-xs font-semibold dark:border-mustBorder-dark" role="radiogroup" :aria-label="`Decision for ${row.name}`">
                                        <button
                                            type="button"
                                            role="radio"
                                            class="px-3 py-1.5 transition disabled:cursor-not-allowed disabled:opacity-40"
                                            :class="segment(row, 'promote')"
                                            :aria-checked="decisionOf(row.student_id) === 'promote'"
                                            :disabled="!row.can_promote"
                                            :title="row.can_promote ? `Promote to ${row.to_label}` : 'Top of a stage — record the next step on the profile'"
                                            @click="setDecision(row, 'promote')"
                                        >
                                            Promote
                                        </button>
                                        <button
                                            type="button"
                                            role="radio"
                                            class="border-x border-mustBorder-light px-3 py-1.5 transition dark:border-mustBorder-dark"
                                            :class="segment(row, 'repeat')"
                                            :aria-checked="decisionOf(row.student_id) === 'repeat'"
                                            @click="setDecision(row, 'repeat')"
                                        >
                                            Repeat
                                        </button>
                                        <button
                                            type="button"
                                            role="radio"
                                            class="px-3 py-1.5 transition"
                                            :class="decisionOf(row.student_id) === null ? 'bg-gray-100 text-mustBlue dark:bg-white/10 dark:text-text-inverted' : 'bg-white text-text-muted hover:bg-gray-50 dark:bg-transparent'"
                                            :aria-checked="decisionOf(row.student_id) === null"
                                            @click="setDecision(row, null)"
                                        >
                                            Not now
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- No level on record -->
            <section v-if="unplaced.length" class="card-container mb-4">
                <h2 class="page-heading-2 !mb-1">No level on record ({{ unplaced.length }})</h2>
                <p class="mb-3 text-sm text-text-muted">Set their school and level for {{ year }} from their profile.</p>
                <ul class="flex flex-wrap gap-2 text-sm">
                    <li v-for="row in unplaced" :key="row.student_id">
                        <Link :href="`${route('students.show', row.student_id)}#education`" class="rounded-full bg-gray-100 px-3 py-1 text-mustBlue hover:bg-gray-200 dark:bg-white/10 dark:text-text-inverted">
                            {{ row.name }}
                        </Link>
                    </li>
                </ul>
            </section>

            <!-- Summary bar -->
            <div v-if="rows.length" class="sticky bottom-0 flex flex-wrap items-center justify-end gap-3 border-t border-mustBorder-light bg-background/95 py-3 backdrop-blur dark:border-mustBorder-dark dark:bg-background-dark/95">
                <InputError :message="form.errors.decisions" />
                <span class="text-sm text-text-muted">
                    <strong class="text-mustGreen">{{ promoteCount }}</strong> promote ·
                    <strong class="text-amber-600">{{ repeatCount }}</strong> repeat ·
                    {{ undecidedCount }} not now
                </span>
                <button type="button" class="btn-primary" :disabled="form.processing || promoteCount + repeatCount === 0" @click="confirming = true">
                    Save decisions
                </button>
            </div>
        </template>

        <ConfirmationModal :show="confirming" type="success" :close="() => (confirming = false)" @confirm="submit">
            <template #title>Confirm for {{ year }}</template>
            <template #message>
                <strong>{{ promoteCount }}</strong> {{ promoteCount === 1 ? 'student moves' : 'students move' }} up a level and
                <strong>{{ repeatCount }}</strong> {{ repeatCount === 1 ? 'repeats' : 'repeat' }}. This adds their {{ year }} records.
                <template v-if="undecidedCount"> {{ undecidedCount }} {{ undecidedCount === 1 ? 'student stays' : 'students stay' }} on this list for later.</template>
            </template>
            <template #confirmText>Save</template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

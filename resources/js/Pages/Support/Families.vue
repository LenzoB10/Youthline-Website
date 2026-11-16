<script setup>
import { date } from '@/Helpers';
import { formatMoney } from '@/Helpers/money';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarClock, CircleCheck, Hammer } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    currency: { type: String, default: 'MWK' },
    due: { type: Array, default: () => [] },
    repairTotals: { type: Object, required: true },
    repairs: { type: Array, default: () => [] },
});

const money = (v) => formatMoney(v, props.currency);
const statusFilter = ref('open');
const shownRepairs = computed(() =>
    props.repairs.filter((r) => (statusFilter.value === 'open' ? r.status !== 'completed' : statusFilter.value === 'all' || r.status === statusFilter.value)),
);
const statusClass = { planned: 'bg-sky-100 text-sky-800', in_progress: 'bg-amber-100 text-amber-800', completed: 'bg-green-100 text-green-800' };
</script>

<template>
    <Head title="Families & repairs" />

    <AuthenticatedLayout>
        <h1 class="page-heading !mb-1">Families and home repairs</h1>
        <p class="mb-6 text-sm text-text-muted dark:text-text-dark-muted">Visits and repairs are recorded on each student's profile (Home &amp; family tab).</p>

        <!-- Visits due -->
        <section class="card-container mb-6">
            <h2 class="page-heading-2 flex items-center gap-2"><CalendarClock class="h-4 w-4" /> Home visits due ({{ due.length }})</h2>
            <p class="mb-4 text-sm text-text-muted">Families of active students never visited, overdue, or due within two weeks.</p>
            <p v-if="!due.length" class="flex items-center gap-2 py-4 text-sm text-text-muted"><CircleCheck class="h-4 w-4 text-mustGreen" /> Every family is up to date.</p>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                            <th class="px-3 py-2">Household</th>
                            <th class="px-3 py-2">Students</th>
                            <th class="px-3 py-2">Last visit</th>
                            <th class="px-3 py-2">Next visit due</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="h in due" :key="h.household_id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                            <td class="px-3 py-2">
                                <Link v-if="h.student_id" :href="route('students.show', h.student_id) + '#home'" class="font-medium text-mustGreen hover:underline">{{ h.household }}</Link>
                                <span v-if="h.area" class="block text-xs text-text-muted">{{ h.area }}</span>
                            </td>
                            <td class="px-3 py-2 text-text-muted">{{ h.students.join(', ') }}</td>
                            <td class="px-3 py-2">{{ h.last_visit ? date.formatDate(h.last_visit) : 'Never' }}</td>
                            <td class="px-3 py-2">
                                <span :class="h.is_overdue ? 'font-semibold text-red-700' : ''">{{ h.next_visit_due ? date.formatDate(h.next_visit_due) : 'Now' }}</span>
                                <span v-if="h.is_overdue" class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-800">Overdue</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Repairs -->
        <section class="card-container">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 class="page-heading-2 !mb-0 flex items-center gap-2"><Hammer class="h-4 w-4" /> Home repairs</h2>
                <div class="flex flex-wrap gap-1 text-sm" role="group" aria-label="Show repairs">
                    <button
                        v-for="f in [{ key: 'open', label: 'Open' }, { key: 'planned', label: `Planned (${repairTotals.planned})` }, { key: 'in_progress', label: `In progress (${repairTotals.in_progress})` }, { key: 'completed', label: `Completed (${repairTotals.completed})` }, { key: 'all', label: 'All' }]"
                        :key="f.key"
                        type="button"
                        class="rounded-full px-3 py-1 transition"
                        :class="statusFilter === f.key ? 'bg-mustGreen text-white' : 'bg-mustGreen/10 text-mustGreen hover:bg-mustGreen/20'"
                        :aria-pressed="statusFilter === f.key"
                        @click="statusFilter = f.key"
                    >
                        {{ f.label }}
                    </button>
                </div>
            </div>
            <p class="mb-4 text-sm text-text-muted">Spent on completed repairs: <strong class="text-mustBlue dark:text-text-inverted">{{ money(repairTotals.spent) }}</strong></p>
            <p v-if="!shownRepairs.length" class="py-4 text-sm text-text-muted">No repairs here.</p>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                            <th class="px-3 py-2">Household</th>
                            <th class="px-3 py-2">Repair</th>
                            <th class="px-3 py-2 text-right">Estimate</th>
                            <th class="px-3 py-2 text-right">Actual</th>
                            <th class="px-3 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in shownRepairs" :key="r.id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                            <td class="px-3 py-2">
                                <Link v-if="r.student_id" :href="route('students.show', r.student_id) + '#home'" class="font-medium text-mustGreen hover:underline">{{ r.household }}</Link>
                                <span v-else>{{ r.household }}</span>
                            </td>
                            <td class="px-3 py-2">{{ r.description }}</td>
                            <td class="px-3 py-2 text-right">{{ r.estimated_cost === null ? '—' : money(r.estimated_cost) }}</td>
                            <td class="px-3 py-2 text-right">{{ r.actual_cost === null ? '—' : money(r.actual_cost) }}</td>
                            <td class="px-3 py-2">
                                <span v-if="r.awaiting_approval" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Awaiting approval</span>
                                <span v-else-if="r.rejected" class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-700">Rejected</span>
                                <span v-else class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusClass[r.status]">{{ r.status_label }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AuthenticatedLayout>
</template>

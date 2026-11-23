<script setup>
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import { formatMoney } from '@/Helpers/money';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { CircleCheck, Download, Eye } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    currency: { type: String, default: 'MWK' },
    termId: { type: Number, default: null },
    terms: { type: Array, default: () => [] },
    totals: { type: Object, required: true },
    students: { type: Array, default: () => [] },
    schools: { type: Array, default: () => [] },
});

const money = (amount) => formatMoney(amount, props.currency);
const view = ref('students');

const term = ref(props.termId ?? '');
const termOptions = computed(() => [{ value: '', label: 'All terms' }, ...props.terms]);
watch(term, (value) => {
    router.get(route('finance.unpaid.index'), value ? { term: value } : {}, { preserveState: true, preserveScroll: true, replace: true });
});

const exportUrl = computed(() => route('finance.unpaid.export', props.termId ? { term: props.termId } : {}));
</script>

<template>
    <Head title="Unpaid fees" />

    <AuthenticatedLayout>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-heading !mb-1">Unpaid fees</h1>
                <p class="text-sm text-text-muted dark:text-text-dark-muted">
                    Approved fees Youthline still has to pay.
                </p>
            </div>
            <div class="flex flex-wrap items-end gap-3">
                <div class="w-56">
                    <label for="unpaid_term" class="sr-only">Term</label>
                    <SelectInput id="unpaid_term" v-model="term" :options="termOptions" placeholder="All terms" />
                </div>
                <a :href="exportUrl" class="btn-outline-sm inline-flex items-center gap-1"><Download class="h-4 w-4" /> CSV</a>
                <template v-if="$page.props.auth.permissions?.includes('export reports')">
                    <a :href="route('reports.export', { report: 'unpaid-fees', format: 'xlsx' })" class="btn-outline-sm">Excel</a>
                    <a :href="route('reports.export', { report: 'unpaid-fees', format: 'pdf' })" class="btn-outline-sm">PDF</a>
                </template>
            </div>
        </div>

        <!-- Totals -->
        <dl class="mb-6 grid gap-3 sm:grid-cols-3">
            <div class="card-container !mb-0">
                <dt class="text-xs uppercase tracking-wider text-text-muted">Still to pay</dt>
                <dd class="text-2xl font-bold text-red-700">{{ money(totals.outstanding) }}</dd>
            </div>
            <div class="card-container !mb-0">
                <dt class="text-xs uppercase tracking-wider text-text-muted">Students with a balance</dt>
                <dd class="text-2xl font-bold text-mustBlue dark:text-text-inverted">{{ totals.students }}</dd>
            </div>
            <div class="card-container !mb-0">
                <dt class="text-xs uppercase tracking-wider text-text-muted">Schools owed</dt>
                <dd class="text-2xl font-bold text-mustBlue dark:text-text-inverted">{{ totals.schools }}</dd>
            </div>
        </dl>

        <div class="card-container">
            <div class="mb-4 flex gap-1 border-b border-mustBorder-light dark:border-mustBorder-dark" role="tablist" aria-label="Group by">
                <button
                    v-for="tab in [{ key: 'students', label: `By student (${students.length})` }, { key: 'schools', label: `By school (${schools.length})` }]"
                    :key="tab.key"
                    type="button"
                    role="tab"
                    :aria-selected="view === tab.key"
                    class="-mb-px border-b-2 px-4 py-2 text-sm font-medium transition"
                    :class="view === tab.key ? 'border-mustGreen text-mustGreen' : 'border-transparent text-text-muted hover:text-mustBlue dark:hover:text-text-inverted'"
                    @click="view = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div v-if="!students.length" class="py-10 text-center">
                <CircleCheck class="mx-auto h-10 w-10 text-mustGreen" />
                <p class="mt-2 font-medium text-mustBlue dark:text-text-inverted">Every approved fee is fully paid.</p>
            </div>

            <!-- By student -->
            <div v-else-if="view === 'students'" class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                            <th class="px-3 py-2">Student</th>
                            <th class="px-3 py-2">School</th>
                            <th class="px-3 py-2">Terms</th>
                            <th class="px-3 py-2 text-right">Youthline share</th>
                            <th class="px-3 py-2 text-right">Paid</th>
                            <th class="px-3 py-2 text-right">Balance</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in students" :key="row.student_id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                            <td class="px-3 py-2">
                                <span class="font-medium text-mustBlue dark:text-text-inverted">{{ row.student }}</span>
                                <span class="block text-xs text-text-muted">{{ row.student_status }}</span>
                            </td>
                            <td class="px-3 py-2 text-text-muted">{{ row.schools.join(', ') || '—' }}</td>
                            <td class="px-3 py-2 text-text-muted">{{ row.terms.join(', ') }}</td>
                            <td class="px-3 py-2 text-right">{{ money(row.share) }}</td>
                            <td class="px-3 py-2 text-right">
                                {{ money(row.paid) }}
                            </td>
                            <td class="px-3 py-2 text-right font-semibold text-red-700">{{ money(row.balance) }}</td>
                            <td class="px-3 py-2 text-right">
                                <Link
                                    :href="route('students.show', row.student_id) + '#academics'"
                                    class="action-icon inline-flex"
                                    :aria-label="`View fees for ${row.student}`"
                                    title="View"
                                >
                                    <Eye class="h-[19px] w-[19px]" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- By school -->
            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                            <th class="px-3 py-2">School / university</th>
                            <th class="px-3 py-2 text-right">Students</th>
                            <th class="px-3 py-2 text-right">Youthline share</th>
                            <th class="px-3 py-2 text-right">Paid</th>
                            <th class="px-3 py-2 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in schools" :key="row.institution_id ?? 'none'" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                            <td class="px-3 py-2 font-medium text-mustBlue dark:text-text-inverted">{{ row.school }}</td>
                            <td class="px-3 py-2 text-right">{{ row.students }}</td>
                            <td class="px-3 py-2 text-right">{{ money(row.share) }}</td>
                            <td class="px-3 py-2 text-right">{{ money(row.paid) }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-red-700">{{ money(row.balance) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

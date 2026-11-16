<script setup>
import ReasonModal from '@/Components/Common/ReasonModal.vue';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import { date } from '@/Helpers';
import { formatMoney } from '@/Helpers/money';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Check, CircleCheck, ExternalLink, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    currency: { type: String, default: 'MWK' },
    paymentLimit: { type: Number, default: 0 },
    payments: { type: Array, default: () => [] },
    scholarships: { type: Array, default: () => [] },
    repairs: { type: Array, default: () => [] },
    repairLimit: { type: Number, default: 0 },
});

const money = (amount) => formatMoney(amount, props.currency);

// approving = { kind: 'payment' | 'scholarship', item }
const approving = ref(null);
const rejecting = ref(null);

const group = { payment: 'payments', scholarship: 'scholarships', repair: 'repairs' };
const approve = () => {
    const { kind, item } = approving.value;
    router.put(route(`finance.approvals.${group[kind]}.approve`, item.id), {}, { preserveScroll: true });
    approving.value = null;
};
const rejectUrl = () => (rejecting.value ? route(`finance.approvals.${group[rejecting.value.kind]}.reject`, rejecting.value.item.id) : null);
</script>

<template>
    <Head title="Approvals" />

    <AuthenticatedLayout>
        <h1 class="page-heading">Approvals</h1>
        <p class="mb-6 text-sm text-text-muted dark:text-text-dark-muted">
            Payments over {{ money(paymentLimit) }}, new scholarships and large repair budgets recorded by managers wait here for your decision.
            The person who recorded each one is told what you decide.
            <Link :href="route('admin.settings.approvals.edit')" class="text-mustGreen hover:underline">Change the rules</Link>
        </p>

        <div v-if="!payments.length && !scholarships.length && !repairs.length" class="card-container py-12 text-center">
            <CircleCheck class="mx-auto h-10 w-10 text-mustGreen" />
            <p class="mt-2 font-medium text-mustBlue dark:text-text-inverted">Nothing is waiting for approval.</p>
        </div>

        <!-- Scholarships -->
        <section v-if="scholarships.length" class="card-container mb-6">
            <h2 class="page-heading-2">New scholarships ({{ scholarships.length }})</h2>
            <p class="mb-4 text-sm text-text-muted">A student's first fee entry. Approving it lets payments be recorded for them.</p>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                            <th class="px-3 py-2">Student</th>
                            <th class="px-3 py-2">Term</th>
                            <th class="px-3 py-2">School</th>
                            <th class="px-3 py-2 text-right">Fees</th>
                            <th class="px-3 py-2 text-right">Youthline pays</th>
                            <th class="px-3 py-2">Added by</th>
                            <th class="px-3 py-2"><span class="sr-only">Decision</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in scholarships" :key="s.id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                            <td class="px-3 py-2">
                                <Link :href="route('students.show', s.student_id) + '#academics'" class="font-medium text-mustGreen hover:underline">{{ s.student }}</Link>
                                <span v-if="s.notes" class="block text-xs text-text-muted">{{ s.notes }}</span>
                            </td>
                            <td class="px-3 py-2">{{ s.term }}</td>
                            <td class="px-3 py-2 text-text-muted">{{ s.school ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ money(s.amount_owed) }}</td>
                            <td class="px-3 py-2 text-right font-semibold">{{ money(s.youthline_share) }}</td>
                            <td class="px-3 py-2 text-text-muted">{{ s.created_by ?? '—' }}<span class="block text-xs">{{ date.formatDate(s.created_at) }}</span></td>
                            <td class="px-3 py-2">
                                <div class="flex justify-end gap-2">
                                    <button type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="approving = { kind: 'scholarship', item: s }">
                                        <Check class="h-4 w-4" /> Approve
                                    </button>
                                    <button type="button" class="btn-danger-sm inline-flex items-center gap-1" @click="rejecting = { kind: 'scholarship', item: s }">
                                        <X class="h-4 w-4" /> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Repairs -->
        <section v-if="repairs.length" class="card-container mb-6">
            <h2 class="page-heading-2">Repair budgets over {{ money(repairLimit) }} ({{ repairs.length }})</h2>
            <p class="mb-4 text-sm text-text-muted">Work can't start until the budget is approved.</p>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                            <th class="px-3 py-2">Household</th>
                            <th class="px-3 py-2">Repair</th>
                            <th class="px-3 py-2 text-right">Estimate</th>
                            <th class="px-3 py-2">Planned by</th>
                            <th class="px-3 py-2"><span class="sr-only">Decision</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in repairs" :key="r.id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                            <td class="px-3 py-2">
                                <Link v-if="r.student_id" :href="route('students.show', r.student_id) + '#home'" class="font-medium text-mustGreen hover:underline">{{ r.household }}</Link>
                                <span v-else>{{ r.household }}</span>
                                <span v-if="r.students.length" class="block text-xs text-text-muted">{{ r.students.join(', ') }}</span>
                            </td>
                            <td class="px-3 py-2">{{ r.description }}<span v-if="r.photos" class="block text-xs text-text-muted">{{ r.photos }} photo(s) on the profile</span></td>
                            <td class="px-3 py-2 text-right font-semibold">{{ money(r.estimated_cost) }}</td>
                            <td class="px-3 py-2 text-text-muted">{{ r.created_by ?? '—' }}<span class="block text-xs">{{ date.formatDate(r.created_at) }}</span></td>
                            <td class="px-3 py-2">
                                <div class="flex justify-end gap-2">
                                    <button type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="approving = { kind: 'repair', item: r }">
                                        <Check class="h-4 w-4" /> Approve
                                    </button>
                                    <button type="button" class="btn-danger-sm inline-flex items-center gap-1" @click="rejecting = { kind: 'repair', item: r }">
                                        <X class="h-4 w-4" /> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Payments -->
        <section v-if="payments.length" class="card-container">
            <h2 class="page-heading-2">Payments over the limit ({{ payments.length }})</h2>
            <p class="mb-4 text-sm text-text-muted">These don't count towards a balance until approved.</p>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                            <th class="px-3 py-2">Student</th>
                            <th class="px-3 py-2">Term / school</th>
                            <th class="px-3 py-2 text-right">Amount</th>
                            <th class="px-3 py-2">Paid</th>
                            <th class="px-3 py-2">Recorded by</th>
                            <th class="px-3 py-2"><span class="sr-only">Decision</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in payments" :key="p.id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                            <td class="px-3 py-2">
                                <Link :href="route('students.show', p.student_id) + '#academics'" class="font-medium text-mustGreen hover:underline">{{ p.student }}</Link>
                            </td>
                            <td class="px-3 py-2">
                                {{ p.term }}
                                <span class="block text-xs text-text-muted">{{ p.school ?? 'School not recorded' }} · term balance {{ money(p.fee_balance) }}</span>
                            </td>
                            <td class="px-3 py-2 text-right font-semibold">{{ money(p.amount) }}</td>
                            <td class="px-3 py-2">
                                {{ date.formatDate(p.paid_on) }}
                                <span class="block text-xs text-text-muted">{{ p.method }}<template v-if="p.reference"> · {{ p.reference }}</template></span>
                                <a v-if="p.receipt_url" :href="p.receipt_url" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs text-mustGreen hover:underline">
                                    Receipt <ExternalLink class="h-3 w-3" />
                                </a>
                                <span v-else class="block text-xs text-amber-700">No receipt attached</span>
                            </td>
                            <td class="px-3 py-2 text-text-muted">{{ p.recorded_by ?? '—' }}<span class="block text-xs">{{ date.formatDate(p.recorded_at) }}</span></td>
                            <td class="px-3 py-2">
                                <div class="flex justify-end gap-2">
                                    <button type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="approving = { kind: 'payment', item: p }">
                                        <Check class="h-4 w-4" /> Approve
                                    </button>
                                    <button type="button" class="btn-danger-sm inline-flex items-center gap-1" @click="rejecting = { kind: 'payment', item: p }">
                                        <X class="h-4 w-4" /> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <ConfirmationModal :show="!!approving" type="success" :close="() => (approving = null)" @confirm="approve">
            <template #title>Approve {{ approving?.kind === 'repair' ? 'repair budget' : approving?.kind }}</template>
            <template #message>
                <template v-if="approving?.kind === 'payment'">
                    Approve the payment of <strong>{{ money(approving.item.amount) }}</strong> for {{ approving.item.student }}? It will count towards the balance.
                </template>
                <template v-else-if="approving?.kind === 'repair'">
                    Approve the repair budget of <strong>{{ money(approving.item.estimated_cost) }}</strong> for the {{ approving.item.household }} household?
                </template>
                <template v-else-if="approving">
                    Approve the new scholarship for <strong>{{ approving.item.student }}</strong> ({{ money(approving.item.youthline_share) }} for {{ approving.item.term }})?
                </template>
            </template>
        </ConfirmationModal>

        <ReasonModal
            :show="!!rejecting"
            :url="rejectUrl()"
            :title="rejecting?.kind === 'repair' ? 'Reject repair budget' : rejecting?.kind === 'payment' ? 'Reject payment' : 'Reject scholarship'"
            intro="The manager who recorded it will see your reason."
            label="Reason"
            confirm-text="Reject"
            @close="rejecting = null"
        />
    </AuthenticatedLayout>
</template>

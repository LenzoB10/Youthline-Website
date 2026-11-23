<script setup>
import ReasonModal from '@/Components/Common/ReasonModal.vue';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import FeeModal from '@/Components/Students/FeeModal.vue';
import PaymentModal from '@/Components/Students/PaymentModal.vue';
import { date } from '@/Helpers';
import { can } from '@/Helpers/can';
import { formatMoney } from '@/Helpers/money';
import { Link, router } from '@inertiajs/vue3';
import { Ban, ExternalLink, Plus, Receipt, SquarePen, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    student: { type: Object, required: true },
    fees: { type: Array, default: () => [] },
    totals: { type: Object, required: true },
    finance: { type: Object, required: true },
    terms: { type: Array, default: () => [] },
    institutions: { type: Array, default: () => [] },
    methods: { type: Array, default: () => [] },
});

const canManage = can('manage fees');
const canApprove = can('approve payments');
const money = (amount) => formatMoney(amount, props.finance.currency);

const stateBadge = {
    paid: ['Paid', 'bg-mustGreen/15 text-yl-green-deep dark:text-accent-light'],
    part_paid: ['Part paid', 'bg-yl-gold/15 text-amber-800 dark:text-yl-gold'],
    unpaid: ['Unpaid', 'bg-gray-700 text-white dark:bg-gray-300 dark:text-gray-900'],
    overpaid: ['Overpaid', 'bg-yl-gold/15 text-yl-green-deep ring-1 ring-inset ring-yl-gold/50 dark:text-yl-gold'],
    pending_approval: ['Scholarship awaiting approval', 'bg-yl-gold/15 text-amber-800 dark:text-yl-gold'],
    rejected: ['Scholarship rejected', 'bg-gray-200 text-gray-700 dark:bg-gray-500/20 dark:text-gray-300'],
};
const paymentBadge = {
    paid: 'bg-mustGreen/15 text-yl-green-deep dark:text-accent-light',
    rejected: 'bg-gray-200 text-gray-700 dark:bg-gray-500/20 dark:text-gray-300',
    error: 'bg-gray-700 text-white dark:bg-gray-300 dark:text-gray-900',
};

const feeModal = ref({ show: false, fee: null });
const paymentFee = ref(null);
const errorPayment = ref(null);
const deletingFee = ref(null);

const destroyFee = () => {
    router.delete(route('students.fees.destroy', [props.student.id, deletingFee.value.id]), { preserveScroll: true });
    deletingFee.value = null;
};
</script>

<template>
    <section class="card-container">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="page-heading-2 !mb-0">Fees and payments</h2>
                <p class="text-sm text-text-muted">What each term costs, Youthline's share, and every payment made.</p>
            </div>
            <button v-if="canManage" type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="feeModal = { show: true, fee: null }">
                <Plus class="h-4 w-4" /> Add term fees
            </button>
        </div>

        <!-- Totals -->
        <dl v-if="fees.length" class="mb-5 grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg bg-mustGreen/5 p-3">
                <dt class="text-xs uppercase tracking-wider text-text-muted">Youthline committed</dt>
                <dd class="text-lg font-bold text-mustBlue dark:text-text-inverted">{{ money(totals.share) }}</dd>
            </div>
            <div class="rounded-lg bg-mustGreen/5 p-3">
                <dt class="text-xs uppercase tracking-wider text-text-muted">Paid</dt>
                <dd class="text-lg font-bold text-mustGreen">{{ money(totals.paid) }}</dd>
            </div>
            <div class="rounded-lg p-3" :class="totals.balance > 0 ? 'bg-gray-100 dark:bg-white/5' : 'bg-mustGreen/5'">
                <dt class="text-xs uppercase tracking-wider text-text-muted">Still to pay</dt>
                <dd class="text-lg font-bold" :class="totals.balance > 0 ? 'text-red-700' : 'text-mustBlue dark:text-text-inverted'">{{ money(totals.balance) }}</dd>
            </div>
        </dl>

        <p v-if="!fees.length" class="py-6 text-center text-sm text-text-muted">No fees recorded yet.</p>

        <!-- One block per term -->
        <div class="space-y-4">
            <article v-for="fee in fees" :key="fee.id" class="rounded-lg border border-mustBorder-light p-4 dark:border-mustBorder-dark">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-mustBlue dark:text-text-inverted">
                            {{ fee.term || 'Term not set' }}
                            <span class="ml-2 rounded-full px-2 py-0.5 text-xs font-medium" :class="stateBadge[fee.state][1]">{{ stateBadge[fee.state][0] }}</span>
                        </h3>
                        <p class="text-sm text-text-muted">
                            {{ fee.school || 'School not recorded' }}
                            · Fees {{ money(fee.amount_owed) }} · Youthline pays {{ money(fee.youthline_share) }}
                            <template v-if="fee.notes"> · {{ fee.notes }}</template>
                        </p>
                        <p v-if="fee.approval_status === 'rejected' && fee.rejection_reason" class="mt-1 text-sm text-red-700">Rejected: {{ fee.rejection_reason }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span v-if="fee.approval_status === 'approved'" class="text-sm">
                            Balance <strong :class="fee.balance > 0 ? 'text-red-700' : 'text-mustGreen'">{{ money(fee.balance) }}</strong>
                        </span>
                        <button
                            v-if="canManage && fee.approval_status === 'approved' && fee.remaining > 0"
                            type="button"
                            class="btn-primary-sm inline-flex items-center gap-1"
                            @click="paymentFee = fee"
                        >
                            <Receipt class="h-4 w-4" /> Record payment
                        </button>
                        <Link
                            v-if="canApprove && fee.approval_status === 'pending'"
                            :href="route('finance.approvals.index')"
                            class="btn-outline-sm"
                        >
                            Review in Approvals
                        </Link>
                        <button v-if="canManage" type="button" class="action-icon" :aria-label="`Edit fees for ${fee.term}`" @click="feeModal = { show: true, fee }">
                            <SquarePen class="h-4 w-4" />
                        </button>
                        <button
                            v-if="canManage && !fee.payments.length"
                            type="button"
                            class="text-red-500 transition hover:text-red-700"
                            :aria-label="`Delete fees for ${fee.term}`"
                            @click="deletingFee = fee"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Payments -->
                <ul v-if="fee.payments.length" class="mt-3 divide-y divide-mustBorder-light border-t border-mustBorder-light text-sm dark:divide-mustBorder-dark dark:border-mustBorder-dark">
                    <li v-for="p in fee.payments" :key="p.id" class="flex flex-wrap items-center gap-x-4 gap-y-1 py-2">
                        <span class="font-semibold" :class="{ 'text-text-muted line-through': ['error', 'rejected'].includes(p.status) }">{{ money(p.amount) }}</span>
                        <span class="text-text-muted">{{ date.formatDate(p.paid_on) }} · {{ p.method }}<template v-if="p.reference"> · {{ p.reference }}</template></span>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="paymentBadge[p.status]">{{ p.status_label }}</span>
                        <span class="text-xs text-text-muted">
                            by {{ p.recorded_by ?? 'unknown' }}<template v-if="p.approved_by && p.status === 'paid'">, approved by {{ p.approved_by }}</template>
                        </span>
                        <span v-if="p.status === 'error'" class="w-full text-xs text-red-700">Error ({{ p.error_marked_by }}): {{ p.error_reason }}</span>
                        <span v-if="p.status === 'rejected'" class="w-full text-xs text-text-muted">Rejected by {{ p.approved_by }}: {{ p.rejection_reason }}</span>
                        <span class="ml-auto flex items-center gap-3">
                            <a v-if="p.receipt_url" :href="p.receipt_url" target="_blank" rel="noopener" class="action-icon" title="Receipt" :aria-label="`Open receipt for ${money(p.amount)}`">
                                <ExternalLink class="h-4 w-4" />
                            </a>
                            <button
                                v-if="canManage && p.status === 'paid'"
                                type="button"
                                class="inline-flex items-center gap-1 text-xs text-red-600 hover:underline"
                                @click="errorPayment = { fee, payment: p }"
                            >
                                <Ban class="h-3.5 w-3.5" /> Mark as error
                            </button>
                        </span>
                    </li>
                </ul>
                <p v-else-if="fee.approval_status === 'approved'" class="mt-3 border-t border-mustBorder-light pt-3 text-sm text-text-muted dark:border-mustBorder-dark">No payments yet.</p>
            </article>
        </div>

        <FeeModal
            :show="feeModal.show"
            :student="student"
            :fee="feeModal.fee"
            :terms="terms"
            :institutions="institutions"
            :currency="finance.currency"
            :needs-approval="finance.scholarshipNeedsApproval && !canApprove"
            @close="feeModal = { show: false, fee: null }"
        />
        <PaymentModal
            :show="!!paymentFee"
            :student="student"
            :fee="paymentFee"
            :methods="methods"
            :currency="finance.currency"
            @close="paymentFee = null"
        />
        <ReasonModal
            :show="!!errorPayment"
            :url="errorPayment ? route('students.payments.error', [student.id, errorPayment.fee.id, errorPayment.payment.id]) : null"
            title="Mark payment as an error"
            intro="Payments are never deleted. Marking this one as an error keeps it in the record but stops it counting towards the balance."
            label="What was wrong?"
            confirm-text="Mark as error"
            @close="errorPayment = null"
        />
        <ConfirmationModal :show="!!deletingFee" :close="() => (deletingFee = null)" @confirm="destroyFee">
            <template #title>Delete term fees</template>
            <template #message>Delete the fee entry for <strong>{{ deletingFee?.term }}</strong>? It has no payments.</template>
        </ConfirmationModal>
    </section>
</template>

<script setup>
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { date } from '@/Helpers';
import { can } from '@/Helpers/can';
import { formatMoney } from '@/Helpers/money';
import { router, useForm } from '@inertiajs/vue3';
import { Banknote, ClipboardList, Plus, SquarePen, Store, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    student: { type: Object, required: true },
    businesses: { type: Array, default: () => [] },
    fundingTypes: { type: Array, default: () => [] },
    currency: { type: String, default: 'MWK' },
});

const canManage = can('manage businesses');
const canDelete = can('delete support records');
const today = new Date().toISOString().slice(0, 10);
const money = (v) => formatMoney(v, props.currency);

// modal = { kind: 'business' | 'checkin' | 'repayment', business }
const modal = ref(null);
const businessForm = useForm({ name: '', business_type: '', start_date: today, amount_given: '', funding_type: 'grant' });
const checkinForm = useForm({ checkin_date: today, is_running: 1, monthly_income: '', notes: '' });
const repaymentForm = useForm({ amount: '', paid_on: today });

const open = (kind, business = null) => {
    if (kind === 'business') {
        businessForm.clearErrors();
        businessForm.name = business?.name ?? '';
        businessForm.business_type = business?.business_type ?? '';
        businessForm.start_date = business?.start_date ?? today;
        businessForm.amount_given = business?.amount_given ?? '';
        businessForm.funding_type = business?.funding_type ?? 'grant';
    } else if (kind === 'checkin') {
        checkinForm.reset();
        checkinForm.clearErrors();
        checkinForm.checkin_date = today;
        checkinForm.is_running = business.is_running ? 1 : 0;
    } else {
        repaymentForm.reset();
        repaymentForm.clearErrors();
        repaymentForm.paid_on = today;
    }
    modal.value = { kind, business };
};
const close = () => (modal.value = null);
const options = { preserveScroll: true, onSuccess: close };

const save = () => {
    const { kind, business } = modal.value;
    if (kind === 'business') {
        business
            ? businessForm.put(route('students.businesses.update', [props.student.id, business.id]), options)
            : businessForm.post(route('students.businesses.store', props.student.id), options);
    } else if (kind === 'checkin') {
        checkinForm.post(route('students.businesses.checkins.store', [props.student.id, business.id]), options);
    } else {
        repaymentForm.post(route('students.businesses.repayments.store', [props.student.id, business.id]), options);
    }
};

const deleting = ref(null);
const destroy = () => {
    router.delete(route('students.businesses.destroy', [props.student.id, deleting.value.id]), { preserveScroll: true });
    deleting.value = null;
};
const runningOptions = [
    { value: 1, label: 'Yes, still running' },
    { value: 0, label: 'No, it has stopped' },
];
</script>

<template>
    <section class="card-container">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="page-heading-2 !mb-0 flex items-center gap-2"><Store class="h-4 w-4" /> Businesses</h2>
                <p class="text-sm text-text-muted">Start-up support, regular check-ins and loan repayments.</p>
            </div>
            <button v-if="canManage" type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="open('business')">
                <Plus class="h-4 w-4" /> Add business
            </button>
        </div>

        <p v-if="!businesses.length" class="py-6 text-center text-sm text-text-muted">No business support recorded.</p>

        <div class="space-y-4">
            <article v-for="b in businesses" :key="b.id" class="rounded-lg border border-mustBorder-light p-4 text-sm dark:border-mustBorder-dark">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-mustBlue dark:text-text-inverted">
                            {{ b.name }}
                            <span class="ml-2 rounded-full px-2 py-0.5 text-xs font-medium" :class="b.is_running ? 'bg-mustGreen/15 text-yl-green-deep' : 'bg-gray-200 text-gray-700'">
                                {{ b.is_running ? 'Running' : 'Stopped' }}
                            </span>
                            <span v-if="b.checkin_due" class="ml-1 rounded-full bg-yl-gold/15 px-2 py-0.5 text-xs font-medium text-amber-800">Check-in due</span>
                        </h3>
                        <p class="text-text-muted">
                            <template v-if="b.business_type">{{ b.business_type }} · </template>started {{ date.formatDate(b.start_date) }}
                            · {{ b.funding_label }} of {{ money(b.amount_given) }}
                        </p>
                        <p v-if="b.funding_type === 'loan'" class="mt-1">
                            Repaid {{ money(b.repaid) }} · <strong :class="b.balance > 0 ? 'text-red-700' : 'text-mustGreen'">{{ b.balance > 0 ? `${money(b.balance)} still to repay` : 'Fully repaid' }}</strong>
                        </p>
                    </div>
                    <div v-if="canManage" class="flex flex-wrap items-center gap-2">
                        <button type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="open('checkin', b)"><ClipboardList class="h-4 w-4" /> Check-in</button>
                        <button v-if="b.funding_type === 'loan' && b.balance > 0" type="button" class="btn-outline-sm inline-flex items-center gap-1" @click="open('repayment', b)">
                            <Banknote class="h-4 w-4" /> Repayment
                        </button>
                        <button type="button" class="action-icon" :aria-label="`Edit ${b.name}`" @click="open('business', b)"><SquarePen class="h-4 w-4" /></button>
                        <button v-if="canDelete" type="button" class="text-red-500 hover:text-red-700" :aria-label="`Delete ${b.name}`" @click="deleting = b"><Trash2 class="h-4 w-4" /></button>
                    </div>
                </div>

                <div class="mt-3 grid gap-4 border-t border-mustBorder-light pt-3 md:grid-cols-2 dark:border-mustBorder-dark">
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-text-muted">Check-ins</p>
                        <p v-if="!b.checkins.length" class="text-text-muted">None yet.</p>
                        <ul v-else class="space-y-2">
                            <li v-for="c in b.checkins" :key="c.id">
                                <span class="font-medium">{{ date.formatDate(c.checkin_date) }}</span>
                                <span class="text-text-muted"> · {{ c.is_running ? 'running' : 'stopped' }}<template v-if="c.monthly_income !== null"> · earns about {{ money(c.monthly_income) }}/month</template></span>
                                <span v-if="c.notes" class="block text-text-muted">{{ c.notes }}</span>
                            </li>
                        </ul>
                    </div>
                    <div v-if="b.funding_type === 'loan'">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-text-muted">Repayments</p>
                        <p v-if="!b.repayments.length" class="text-text-muted">None yet.</p>
                        <ul v-else class="space-y-1">
                            <li v-for="r in b.repayments" :key="r.id" class="flex justify-between gap-4">
                                <span>{{ date.formatDate(r.paid_on) }}</span><span class="font-medium">{{ money(r.amount) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </article>
        </div>

        <Modal maxWidth="lg" :show="!!modal" :closeOnClickOutside="false" @close="close">
            <div v-if="modal" class="p-6">
                <ModalHeader
                    :title="modal.kind === 'business' ? (modal.business ? 'Edit business' : 'Add a business') : modal.kind === 'checkin' ? `Check-in — ${modal.business.name}` : `Loan repayment — ${modal.business.name}`"
                    :close="close"
                />
                <form class="space-y-4" @submit.prevent="save">
                    <template v-if="modal.kind === 'business'">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="b_name" value="Business name" />
                                <TextInput id="b_name" v-model="businessForm.name" class="mt-1 block w-full" required />
                                <InputError class="mt-1" :message="businessForm.errors.name" />
                            </div>
                            <div>
                                <InputLabel for="b_type" value="Type of business" />
                                <TextInput id="b_type" v-model="businessForm.business_type" placeholder="e.g. Tailoring" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel for="b_start" value="Start date" />
                                <TextInput id="b_start" v-model="businessForm.start_date" type="date" :max="today" class="mt-1 block w-full" required />
                                <InputError class="mt-1" :message="businessForm.errors.start_date" />
                            </div>
                            <div>
                                <InputLabel for="b_amount" :value="`Amount given (${currency})`" />
                                <TextInput id="b_amount" v-model="businessForm.amount_given" type="number" min="0" step="0.01" class="mt-1 block w-full" required />
                                <InputError class="mt-1" :message="businessForm.errors.amount_given" />
                            </div>
                        </div>
                        <div>
                            <InputLabel for="b_funding" value="Funding" />
                            <SelectInput id="b_funding" v-model="businessForm.funding_type" :options="fundingTypes" required />
                            <InputError class="mt-1" :message="businessForm.errors.funding_type" />
                        </div>
                    </template>

                    <template v-else-if="modal.kind === 'checkin'">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="c_date" value="Date" />
                                <TextInput id="c_date" v-model="checkinForm.checkin_date" type="date" :max="today" class="mt-1 block w-full" required />
                                <InputError class="mt-1" :message="checkinForm.errors.checkin_date" />
                            </div>
                            <div>
                                <InputLabel for="c_running" value="Is the business still running?" />
                                <SelectInput id="c_running" v-model="checkinForm.is_running" :options="runningOptions" required />
                            </div>
                        </div>
                        <div>
                            <InputLabel for="c_income" :value="`Roughly how much it earns a month (${currency}, optional)`" />
                            <TextInput id="c_income" v-model="checkinForm.monthly_income" type="number" min="0" step="0.01" class="mt-1 block w-full" />
                            <InputError class="mt-1" :message="checkinForm.errors.monthly_income" />
                        </div>
                        <div>
                            <InputLabel for="c_notes" value="Progress and any problems" />
                            <TextArea id="c_notes" v-model="checkinForm.notes" rows="3" class="mt-1 block w-full" />
                        </div>
                    </template>

                    <template v-else>
                        <p class="text-sm text-text-muted">Still to repay: <strong>{{ money(modal.business.balance) }}</strong></p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="p_amount" :value="`Amount (${currency})`" />
                                <TextInput id="p_amount" v-model="repaymentForm.amount" type="number" min="0.01" step="0.01" :max="modal.business.balance" class="mt-1 block w-full" required />
                                <InputError class="mt-1" :message="repaymentForm.errors.amount" />
                            </div>
                            <div>
                                <InputLabel for="p_date" value="Date repaid" />
                                <TextInput id="p_date" v-model="repaymentForm.paid_on" type="date" :max="today" class="mt-1 block w-full" required />
                                <InputError class="mt-1" :message="repaymentForm.errors.paid_on" />
                            </div>
                        </div>
                    </template>

                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-outline-sm" @click="close">Cancel</button>
                        <button type="submit" class="btn-primary-sm" :disabled="businessForm.processing || checkinForm.processing || repaymentForm.processing">Save</button>
                    </div>
                </form>
            </div>
        </Modal>

        <ConfirmationModal :show="!!deleting" :close="() => (deleting = null)" @confirm="destroy">
            <template #title>Delete business</template>
            <template #message>Delete <strong>{{ deleting?.name }}</strong> with all its check-ins and repayments?</template>
        </ConfirmationModal>
    </section>
</template>

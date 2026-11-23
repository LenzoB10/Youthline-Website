<script setup>
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { formatMoney } from '@/Helpers/money';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    student: { type: Object, required: true },
    fee: { type: Object, default: null },
    methods: { type: Array, default: () => [] },
    currency: { type: String, default: 'MWK' },
});
const emit = defineEmits(['close']);

const today = () => new Date().toISOString().slice(0, 10);
const form = useForm({ amount: '', paid_on: today(), method: 'Bank transfer', reference: '', receipt: null });
const fileInput = ref(null);

watch(
    () => props.show,
    (open) => {
        if (!open) return;
        form.reset();
        form.clearErrors();
        form.paid_on = today();
        form.amount = props.fee?.remaining > 0 ? props.fee.remaining : '';
        if (fileInput.value) fileInput.value.value = '';
    },
);

const methodOptions = computed(() => props.methods.map((m) => ({ value: m, label: m })));

const close = () => emit('close');
const submit = () =>
    form.post(route('students.payments.store', [props.student.id, props.fee.id]), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: close,
    });
</script>

<template>
    <Modal maxWidth="lg" :show="show" :closeOnClickOutside="false" @close="close">
        <div v-if="fee" class="p-6">
            <ModalHeader :title="`Record a payment — ${fee.term}`" :close="close" />
            <p class="mb-4 text-sm text-text-muted">
                Still owed for this term: <strong class="text-mustBlue dark:text-text-inverted">{{ formatMoney(fee.remaining, currency) }}</strong>
                <template v-if="fee.school"> to {{ fee.school }}</template>.
            </p>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="pay_amount" :value="`Amount (${currency})`" />
                        <TextInput id="pay_amount" v-model="form.amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.amount" />
                    </div>
                    <div>
                        <InputLabel for="pay_date" value="Date paid" />
                        <TextInput id="pay_date" v-model="form.paid_on" type="date" :max="today()" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.paid_on" />
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="pay_method" value="Method" />
                        <SelectInput id="pay_method" v-model="form.method" :options="methodOptions" required />
                        <InputError class="mt-2" :message="form.errors.method" />
                    </div>
                    <div>
                        <InputLabel for="pay_reference" value="Reference (optional)" />
                        <TextInput id="pay_reference" v-model="form.reference" placeholder="Bank / cheque / transaction no." class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.reference" />
                    </div>
                </div>
                <div>
                    <InputLabel for="pay_receipt" value="Receipt (optional, recommended)" />
                    <input
                        id="pay_receipt"
                        ref="fileInput"
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                        class="mt-1 block w-full text-sm text-text-muted file:mr-3 file:rounded-md file:border-0 file:bg-mustGreen/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-mustGreen"
                        @input="form.receipt = $event.target.files[0]"
                    />
                    <InputError class="mt-2" :message="form.errors.receipt" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn-outline-sm" @click="close">Cancel</button>
                    <button type="submit" class="btn-primary-sm" :disabled="form.processing">Record payment</button>
                </div>
            </form>
        </div>
    </Modal>
</template>

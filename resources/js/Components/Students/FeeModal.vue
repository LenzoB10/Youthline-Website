<script setup>
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SearchableSelectInput from '@/Components/FormElements/SearchableSelectInput.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    student: { type: Object, required: true },
    fee: { type: Object, default: null }, // editing when set
    terms: { type: Array, default: () => [] },
    institutions: { type: Array, default: () => [] },
    currency: { type: String, default: 'MWK' },
    needsApproval: { type: Boolean, default: false },
});
const emit = defineEmits(['close']);

const form = useForm({ term_id: '', institution_id: null, amount_owed: '', youthline_share: '', notes: '' });
const shareTouched = ref(false);

watch(
    () => props.show,
    (open) => {
        if (!open) return;
        form.clearErrors();
        const f = props.fee;
        form.term_id = f?.term_id ?? props.terms.find((t) => t.is_current)?.value ?? '';
        form.institution_id = f?.institution_id ?? null;
        form.amount_owed = f?.amount_owed ?? '';
        form.youthline_share = f?.youthline_share ?? '';
        form.notes = f?.notes ?? '';
        shareTouched.value = !!f;
    },
);

// Youthline usually pays the whole fee: mirror the amount until the share is edited.
watch(
    () => form.amount_owed,
    (value) => {
        if (!shareTouched.value) form.youthline_share = value;
    },
);

const close = () => emit('close');
const submit = () => {
    const options = { preserveScroll: true, onSuccess: close };
    if (props.fee) {
        form.put(route('students.fees.update', [props.student.id, props.fee.id]), options);
    } else {
        form.post(route('students.fees.store', props.student.id), options);
    }
};
</script>

<template>
    <Modal maxWidth="lg" :show="show" :closeOnClickOutside="false" @close="close">
        <div class="p-6">
            <ModalHeader :title="fee ? 'Edit term fees' : 'Add term fees'" :close="close" />

            <p v-if="!fee && needsApproval" class="mb-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                This is {{ student.first_name }}'s first fee entry — a <strong>new scholarship</strong>. It will wait for Admin approval before any payment can be recorded.
            </p>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <InputLabel for="fee_term" value="Term" />
                    <SelectInput id="fee_term" v-model="form.term_id" :options="terms" placeholder="Select term" required />
                    <InputError class="mt-2" :message="form.errors.term_id" />
                </div>
                <div>
                    <InputLabel for="fee_school" value="School / university" />
                    <SearchableSelectInput id="fee_school" v-model="form.institution_id" :options="institutions" placeholder="Defaults to the school for that year" class="mt-1" />
                    <InputError class="mt-2" :message="form.errors.institution_id" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="fee_owed" :value="`Fees for the term (${currency})`" />
                        <TextInput id="fee_owed" v-model="form.amount_owed" type="number" min="0" step="0.01" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.amount_owed" />
                    </div>
                    <div>
                        <InputLabel for="fee_share" :value="`Youthline pays (${currency})`" />
                        <TextInput id="fee_share" v-model="form.youthline_share" type="number" min="0" step="0.01" class="mt-1 block w-full" required @input="shareTouched = true" />
                        <InputError class="mt-2" :message="form.errors.youthline_share" />
                    </div>
                </div>
                <div>
                    <InputLabel for="fee_notes" value="Notes (optional)" />
                    <TextInput id="fee_notes" v-model="form.notes" placeholder="e.g. includes boarding" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.notes" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn-outline-sm" @click="close">Cancel</button>
                    <button type="submit" class="btn-primary-sm" :disabled="form.processing">Save</button>
                </div>
            </form>
        </div>
    </Modal>
</template>

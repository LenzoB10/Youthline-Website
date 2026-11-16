<script setup>
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SearchableSelectInput from '@/Components/FormElements/SearchableSelectInput.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    student: { type: Object, required: true },
    currentYear: { type: String, default: null },
    current: { type: Object, default: null }, // this year's enrolment, if any
    latest: { type: Object, default: null }, // most recent enrolment, to prefill
    institutions: { type: Array, default: () => [] },
    levels: { type: Array, default: () => [] },
});
const emit = defineEmits(['close']);

const form = useForm({ institution_id: null, level: '', course: '', is_repeating: false });

watch(
    () => props.show,
    (open) => {
        if (!open) return;
        const source = props.current ?? props.latest;
        form.clearErrors();
        form.institution_id = source?.institution_id ?? null;
        form.level = props.current?.level ?? '';
        form.course = source?.course ?? '';
        form.is_repeating = props.current?.is_repeating ?? false;
    },
);

const close = () => emit('close');
const submit = () =>
    form.put(route('students.enrollment.update', props.student.id), { preserveScroll: true, onSuccess: close });
</script>

<template>
    <Modal maxWidth="lg" :show="show" :closeOnClickOutside="false" @close="close">
        <div class="p-6">
            <ModalHeader :title="`School and level for ${currentYear ?? 'this year'}`" :close="close" />
            <p class="mb-4 text-sm text-text-muted">
                Use this when a student moves school — for example from Form 4 to university
                (choose the university and <strong>Year 1</strong>). Only {{ currentYear }} is changed;
                earlier years stay exactly as they were.
            </p>

            <p v-if="!currentYear" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                No current academic year is set. An Admin can set one under Settings → Academic Calendar.
            </p>

            <form v-else class="space-y-4" @submit.prevent="submit">
                <div>
                    <InputLabel for="enrol_institution" value="School / university" />
                    <SearchableSelectInput id="enrol_institution" v-model="form.institution_id" :options="institutions" placeholder="Search institutions" class="mt-1" />
                    <InputError class="mt-2" :message="form.errors.institution_id" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="enrol_level" value="Level" />
                        <SelectInput id="enrol_level" v-model="form.level" :options="levels" placeholder="Select level" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.level" />
                    </div>
                    <div>
                        <InputLabel for="enrol_course" value="Course (if any)" />
                        <TextInput id="enrol_course" v-model="form.course" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.course" />
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-text-muted">
                    <Checkbox v-model="form.is_repeating" /> Repeating this level
                </label>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn-outline-sm" @click="close">Cancel</button>
                    <button type="submit" class="btn-primary-sm" :disabled="form.processing">Save</button>
                </div>
            </form>
        </div>
    </Modal>
</template>

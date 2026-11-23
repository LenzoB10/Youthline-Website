<script setup>
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { Plus, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    student: { type: Object, required: true },
    result: { type: Object, default: null }, // editing when set
    lastResult: { type: Object, default: null }, // prefill subjects from the latest result
    terms: { type: Array, default: () => [] },
    resultTypes: { type: Array, default: () => [] },
});
const emit = defineEmits(['close']);

const DEFAULT_SUBJECTS = ['English', 'Mathematics', 'Chichewa', 'Biology', 'Physical Science', 'Agriculture'];

const form = useForm({
    term_id: '',
    exam_type: 'term',
    average: '',
    msce_points: '',
    position: '',
    remarks: '',
    grades: [],
    report_card: null,
});
const fileInput = ref(null);
const isMsce = computed(() => form.exam_type === 'msce');

watch(
    () => props.show,
    (open) => {
        if (!open) return;
        form.clearErrors();
        const r = props.result;
        const currentTerm = props.terms.find((t) => t.is_current);
        form.term_id = r?.term_id ?? currentTerm?.value ?? '';
        form.exam_type = r?.exam_type ?? props.lastResult?.exam_type ?? 'term';
        form.average = r?.average ?? '';
        form.msce_points = r?.msce_points ?? '';
        form.position = r?.position ?? '';
        form.remarks = r?.remarks ?? '';
        form.report_card = null;
        if (fileInput.value) fileInput.value.value = '';
        const subjects = r?.grades?.length
            ? r.grades
            : (props.lastResult?.grades?.length ? props.lastResult.grades.map((g) => g.subject) : DEFAULT_SUBJECTS).map((subject) => ({ subject, grade: '' }));
        form.grades = subjects.map((g) => ({ subject: g.subject, grade: g.grade ?? '' }));
    },
);

const addSubject = () => form.grades.push({ subject: '', grade: '' });
const removeSubject = (index) => form.grades.splice(index, 1);

const close = () => emit('close');
const submit = () => {
    const options = { preserveScroll: true, forceFormData: true, onSuccess: close };
    if (props.result) {
        // Files can't travel in a real PUT, so send POST + _method.
        form.transform((data) => ({ ...data, _method: 'put' })).post(route('students.results.update', [props.student.id, props.result.id]), options);
    } else {
        form.transform((data) => data).post(route('students.results.store', props.student.id), options);
    }
};
</script>

<template>
    <Modal maxWidth="2xl" :show="show" :closeOnClickOutside="false" @close="close">
        <div class="p-6">
            <ModalHeader :title="result ? 'Edit result' : 'Record a result'" :close="close" />

            <p v-if="!terms.length" class="rounded-lg bg-yl-gold/10 p-3 text-sm text-amber-800">
                No terms exist yet. An Admin can add them under Settings → Academic Calendar.
            </p>

            <form v-else class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="result_term" value="Term" />
                        <SelectInput id="result_term" v-model="form.term_id" :options="terms" placeholder="Select term" required />
                        <InputError class="mt-2" :message="form.errors.term_id" />
                    </div>
                    <div>
                        <InputLabel for="result_type" value="Type of result" />
                        <SelectInput id="result_type" v-model="form.exam_type" :options="resultTypes" required />
                        <InputError class="mt-2" :message="form.errors.exam_type" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div v-if="!isMsce">
                        <InputLabel for="result_average" value="Average (out of 100)" />
                        <TextInput id="result_average" v-model="form.average" type="number" min="0" max="100" step="0.01" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.average" />
                    </div>
                    <div v-else>
                        <InputLabel for="result_points" value="MSCE aggregate points (6 = best)" />
                        <TextInput id="result_points" v-model="form.msce_points" type="number" min="6" max="54" step="1" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.msce_points" />
                    </div>
                    <div>
                        <InputLabel for="result_position" value="Position in class (optional)" />
                        <TextInput id="result_position" v-model="form.position" placeholder="e.g. 5 of 40" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.position" />
                    </div>
                </div>

                <!-- Subjects -->
                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-mustBlue dark:text-text-inverted">Grades per subject</legend>
                    <div class="space-y-2">
                        <div v-for="(row, index) in form.grades" :key="index" class="grid grid-cols-[1fr_6rem_auto] items-center gap-2">
                            <TextInput v-model="row.subject" :aria-label="`Subject ${index + 1}`" placeholder="Subject" class="block w-full" />
                            <TextInput v-model="row.grade" :aria-label="`Grade for ${row.subject || 'subject ' + (index + 1)}`" placeholder="Grade" class="block w-full" />
                            <button type="button" class="text-text-muted transition hover:text-red-500" :aria-label="`Remove ${row.subject || 'subject'}`" @click="removeSubject(index)">
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    <button type="button" class="mt-2 inline-flex items-center gap-1 text-sm text-mustGreen hover:underline" @click="addSubject">
                        <Plus class="h-4 w-4" /> Add subject
                    </button>
                    <p class="mt-1 text-xs text-text-muted">Leave a row's subject empty to skip it. Use letters (A–F) or MSCE points (1–9) as the school reports them.</p>
                    <InputError class="mt-2" :message="form.errors.grades" />
                </fieldset>

                <div>
                    <InputLabel for="result_card" :value="result ? 'Replace report card (optional)' : 'Report card scan (optional)'" />
                    <input
                        id="result_card"
                        ref="fileInput"
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                        class="mt-1 block w-full text-sm text-text-muted file:mr-3 file:rounded-md file:border-0 file:bg-mustGreen/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-mustGreen"
                        @input="form.report_card = $event.target.files[0]"
                    />
                    <p class="mt-1 text-xs text-text-muted">PDF or photo, up to 10 MB. It is also saved in the student's documents.</p>
                    <InputError class="mt-2" :message="form.errors.report_card" />
                </div>

                <div>
                    <InputLabel for="result_remarks" value="Teacher's remarks (optional)" />
                    <TextArea id="result_remarks" v-model="form.remarks" rows="2" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.remarks" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn-outline-sm" @click="close">Cancel</button>
                    <button type="submit" class="btn-primary-sm" :disabled="form.processing">{{ result ? 'Save changes' : 'Record result' }}</button>
                </div>
            </form>
        </div>
    </Modal>
</template>

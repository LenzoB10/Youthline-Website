<script setup>
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { date } from '@/Helpers';
import { can } from '@/Helpers/can';
import { router, useForm } from '@inertiajs/vue3';
import { GraduationCap, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    student: { type: Object, required: true },
    data: { type: Object, required: true },
    outcomeTypes: { type: Array, default: () => [] },
});

const canManage = can('manage careers');
const canDelete = can('delete support records');
const today = new Date().toISOString().slice(0, 10);

const outcomeForm = useForm({ recorded_on: today, outcome: 'employed', details: '' });
const saveOutcome = () =>
    outcomeForm.post(route('students.outcomes.store', props.student.id), {
        preserveScroll: true,
        onSuccess: () => outcomeForm.reset('details'),
    });

const deleting = ref(null);
const destroy = () => {
    router.delete(route('students.outcomes.destroy', [props.student.id, deleting.value.id]), { preserveScroll: true });
    deleting.value = null;
};
</script>

<template>
    <section class="card-container">
        <h2 class="page-heading-2 flex items-center gap-2"><GraduationCap class="h-4 w-4" /> After graduating</h2>
        <p class="mb-4 text-sm text-text-muted">What {{ student.first_name }} is doing now — follow up with alumni and add a new entry when things change.</p>

        <form
            v-if="canManage"
            class="mb-4 grid gap-3 rounded-lg border border-dashed border-mustBorder-light p-4 sm:grid-cols-[10rem_12rem_1fr_auto] sm:items-end dark:border-mustBorder-dark"
            @submit.prevent="saveOutcome"
        >
            <div>
                <InputLabel for="o_date" value="Date" />
                <TextInput id="o_date" v-model="outcomeForm.recorded_on" type="date" :max="today" class="mt-1 block w-full" required />
                <InputError class="mt-1" :message="outcomeForm.errors.recorded_on" />
            </div>
            <div>
                <InputLabel for="o_outcome" value="Now" />
                <SelectInput id="o_outcome" v-model="outcomeForm.outcome" :options="outcomeTypes" required />
            </div>
            <div>
                <InputLabel for="o_details" value="Details" />
                <TextInput id="o_details" v-model="outcomeForm.details" placeholder="e.g. Nurse at Zomba Central Hospital" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="outcomeForm.errors.details" />
            </div>
            <button type="submit" class="btn-primary-sm" :disabled="outcomeForm.processing">Add</button>
        </form>

        <p v-if="!data.outcomes.length" class="text-sm text-text-muted">No outcomes recorded.</p>
        <ul v-else class="divide-y divide-mustBorder-light text-sm dark:divide-mustBorder-dark">
            <li v-for="o in data.outcomes" :key="o.id" class="flex flex-wrap items-center gap-3 py-2">
                <span class="font-medium">{{ date.formatDate(o.recorded_on) }}</span>
                <span class="rounded-full bg-mustGreen/10 px-2 py-0.5 text-xs text-mustGreen">{{ o.outcome_label }}</span>
                <span class="text-text-muted">{{ o.details }}</span>
                <button v-if="canDelete" type="button" class="ml-auto text-red-500 hover:text-red-700" :aria-label="`Delete outcome from ${o.recorded_on}`" @click="deleting = o">
                    <Trash2 class="h-4 w-4" />
                </button>
            </li>
        </ul>

        <ConfirmationModal :show="!!deleting" :close="() => (deleting = null)" @confirm="destroy">
            <template #title>Delete outcome</template>
            <template #message>Delete this outcome? This can't be undone.</template>
        </ConfirmationModal>
    </section>
</template>

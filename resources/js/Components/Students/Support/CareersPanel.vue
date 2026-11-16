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
import { router, useForm } from '@inertiajs/vue3';
import { Briefcase, Compass, Plus, SquarePen, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    student: { type: Object, required: true },
    data: { type: Object, required: true },
    applicationTypes: { type: Array, default: () => [] },
    applicationStatuses: { type: Array, default: () => [] },
    outcomeTypes: { type: Array, default: () => [] },
});

const canManage = can('manage careers');
const canDelete = can('delete support records');
const today = new Date().toISOString().slice(0, 10);

// --- Interests ---
const interestsForm = useForm({ interests: [...props.data.interests] });
const newInterest = ref('');
const addInterest = () => {
    const value = newInterest.value.trim();
    if (value && !interestsForm.interests.some((i) => i.toLowerCase() === value.toLowerCase())) {
        interestsForm.interests.push(value);
    }
    newInterest.value = '';
};
const saveInterests = () => interestsForm.put(route('students.interests.update', props.student.id), { preserveScroll: true });

// --- Applications ---
const showApp = ref(false);
const editingApp = ref(null);
const appForm = useForm({ type: 'university', organisation: '', course: '', applied_on: today, status: 'applied', notes: '' });
const openApp = (app = null) => {
    editingApp.value = app;
    appForm.clearErrors();
    appForm.type = app?.type ?? 'university';
    appForm.organisation = app?.organisation ?? '';
    appForm.course = app?.course ?? '';
    appForm.applied_on = app?.applied_on ?? today;
    appForm.status = app?.status ?? 'applied';
    appForm.notes = app?.notes ?? '';
    showApp.value = true;
};
const saveApp = () => {
    const opts = { preserveScroll: true, onSuccess: () => (showApp.value = false) };
    editingApp.value
        ? appForm.put(route('students.applications.update', [props.student.id, editingApp.value.id]), opts)
        : appForm.post(route('students.applications.store', props.student.id), opts);
};

// --- Outcomes ---
const outcomeForm = useForm({ recorded_on: today, outcome: 'employed', details: '' });
const saveOutcome = () =>
    outcomeForm.post(route('students.outcomes.store', props.student.id), {
        preserveScroll: true,
        onSuccess: () => outcomeForm.reset('details'),
    });

const deleting = ref(null); // { kind, item }
const destroy = () => {
    const { kind, item } = deleting.value;
    router.delete(route(kind === 'application' ? 'students.applications.destroy' : 'students.outcomes.destroy', [props.student.id, item.id]), { preserveScroll: true });
    deleting.value = null;
};
const statusClass = { applied: 'bg-sky-100 text-sky-800', accepted: 'bg-green-100 text-green-800', rejected: 'bg-gray-200 text-gray-700' };
</script>

<template>
    <div class="grid gap-4 xl:grid-cols-[1fr_2fr]">
        <!-- Interests -->
        <section class="card-container">
            <h2 class="page-heading-2 flex items-center gap-2"><Compass class="h-4 w-4" /> Career interests</h2>
            <p v-if="!interestsForm.interests.length" class="mb-3 text-sm text-text-muted">None recorded.</p>
            <div class="mb-3 flex flex-wrap gap-2">
                <span v-for="(interest, i) in interestsForm.interests" :key="interest" class="inline-flex items-center gap-1 rounded-full bg-mustGreen/10 px-3 py-1 text-sm text-mustGreen">
                    {{ interest }}
                    <button v-if="canManage" type="button" class="hover:text-red-600" :aria-label="`Remove ${interest}`" @click="interestsForm.interests.splice(i, 1)"><X class="h-3 w-3" /></button>
                </span>
            </div>
            <template v-if="canManage">
                <form class="flex gap-2" @submit.prevent="addInterest">
                    <label for="new_interest" class="sr-only">Add an interest</label>
                    <TextInput id="new_interest" v-model="newInterest" placeholder="e.g. Nursing" class="block w-full" />
                    <button type="submit" class="btn-outline-sm">Add</button>
                </form>
                <InputError class="mt-1" :message="interestsForm.errors.interests" />
                <button type="button" class="btn-primary-sm mt-3" :disabled="interestsForm.processing || !interestsForm.isDirty" @click="saveInterests">Save interests</button>
            </template>
        </section>

        <!-- Applications -->
        <section class="card-container">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="page-heading-2 !mb-0 flex items-center gap-2"><Briefcase class="h-4 w-4" /> Applications</h2>
                    <p class="text-sm text-text-muted">University, college, scholarship and job applications Youthline helps with.</p>
                </div>
                <button v-if="canManage" type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="openApp()"><Plus class="h-4 w-4" /> Add application</button>
            </div>
            <p v-if="!data.applications.length" class="py-6 text-center text-sm text-text-muted">No applications recorded.</p>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                            <th class="px-3 py-2">Applied to</th>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="a in data.applications" :key="a.id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                            <td class="px-3 py-2">
                                <span class="font-medium text-mustBlue dark:text-text-inverted">{{ a.organisation }}</span>
                                <span v-if="a.course" class="block text-xs text-text-muted">{{ a.course }}</span>
                                <span v-if="a.notes" class="block text-xs text-text-muted">{{ a.notes }}</span>
                            </td>
                            <td class="px-3 py-2">{{ a.type_label }}</td>
                            <td class="px-3 py-2 text-text-muted">{{ a.applied_on ? date.formatDate(a.applied_on) : '—' }}</td>
                            <td class="px-3 py-2"><span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusClass[a.status]">{{ a.status_label }}</span></td>
                            <td class="px-3 py-2">
                                <div class="flex justify-end gap-3">
                                    <button v-if="canManage" type="button" class="action-icon" :aria-label="`Edit application to ${a.organisation}`" @click="openApp(a)"><SquarePen class="h-4 w-4" /></button>
                                    <button v-if="canDelete" type="button" class="text-red-500 hover:text-red-700" :aria-label="`Delete application to ${a.organisation}`" @click="deleting = { kind: 'application', item: a }"><Trash2 class="h-4 w-4" /></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Outcomes -->
        <section class="card-container xl:col-span-2">
            <h2 class="page-heading-2">After graduating</h2>
            <p class="mb-4 text-sm text-text-muted">What {{ student.first_name }} is doing now — follow up with alumni and add a new entry when things change.</p>
            <form v-if="canManage" class="mb-4 grid gap-3 rounded-lg border border-dashed border-mustBorder-light p-4 sm:grid-cols-[10rem_12rem_1fr_auto] sm:items-end dark:border-mustBorder-dark" @submit.prevent="saveOutcome">
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
                    <button v-if="canDelete" type="button" class="ml-auto text-red-500 hover:text-red-700" :aria-label="`Delete outcome from ${o.recorded_on}`" @click="deleting = { kind: 'outcome', item: o }"><Trash2 class="h-4 w-4" /></button>
                </li>
            </ul>
        </section>

        <Modal maxWidth="lg" :show="showApp" :closeOnClickOutside="false" @close="showApp = false">
            <div class="p-6">
                <ModalHeader :title="editingApp ? 'Edit application' : 'Add an application'" :close="() => (showApp = false)" />
                <form class="space-y-4" @submit.prevent="saveApp">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="app_type" value="Type" />
                            <SelectInput id="app_type" v-model="appForm.type" :options="applicationTypes" required />
                        </div>
                        <div>
                            <InputLabel for="app_status" value="Status" />
                            <SelectInput id="app_status" v-model="appForm.status" :options="applicationStatuses" required />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="app_org" value="University, college, scholarship or employer" />
                        <TextInput id="app_org" v-model="appForm.organisation" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="appForm.errors.organisation" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="app_course" value="Course / position (optional)" />
                            <TextInput id="app_course" v-model="appForm.course" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="app_date" value="Date applied" />
                            <TextInput id="app_date" v-model="appForm.applied_on" type="date" :max="today" class="mt-1 block w-full" />
                            <InputError class="mt-1" :message="appForm.errors.applied_on" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="app_notes" value="Notes (optional)" />
                        <TextArea id="app_notes" v-model="appForm.notes" rows="2" class="mt-1 block w-full" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-outline-sm" @click="showApp = false">Cancel</button>
                        <button type="submit" class="btn-primary-sm" :disabled="appForm.processing">Save</button>
                    </div>
                </form>
            </div>
        </Modal>

        <ConfirmationModal :show="!!deleting" :close="() => (deleting = null)" @confirm="destroy">
            <template #title>Delete {{ deleting?.kind }}</template>
            <template #message>Delete this {{ deleting?.kind }}? This can't be undone.</template>
        </ConfirmationModal>
    </div>
</template>

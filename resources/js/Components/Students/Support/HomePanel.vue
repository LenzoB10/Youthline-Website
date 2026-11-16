<script setup>
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { date } from '@/Helpers';
import { can } from '@/Helpers/can';
import { formatMoney } from '@/Helpers/money';
import { Link, router, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Camera, ChevronRight, Hammer, Home, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    student: { type: Object, required: true },
    data: { type: Object, required: true },
    currency: { type: String, default: 'MWK' },
});

const canManage = can('manage home visits');
const canDelete = can('delete support records');
const canApprove = can('approve repairs');
const today = new Date().toISOString().slice(0, 10);
const money = (v) => formatMoney(v, props.currency);
const fileClass =
    'mt-1 block w-full text-sm text-text-muted file:mr-3 file:rounded-md file:border-0 file:bg-mustGreen/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-mustGreen';

// --- Visits ---
const showVisit = ref(false);
const visitForm = useForm({ visit_date: today, members_count: '', living_conditions: '', income_sources: '', needs_identified: '', next_visit_due: '', photos: [] });
const openVisit = () => {
    visitForm.reset();
    visitForm.clearErrors();
    visitForm.visit_date = today;
    showVisit.value = true;
};
const saveVisit = () =>
    visitForm.post(route('students.visits.store', props.student.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (showVisit.value = false),
    });

// --- Repairs ---
const showRepair = ref(false);
const repairForm = useForm({ description: '', estimated_cost: '', photos: [] });
const openRepair = () => {
    repairForm.reset();
    repairForm.clearErrors();
    showRepair.value = true;
};
const saveRepair = () =>
    repairForm.post(route('students.repairs.store', props.student.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (showRepair.value = false),
    });

const advancing = ref(null);
const advanceForm = useForm({ actual_cost: '', date: today, photos: [] });
const openAdvance = (repair) => {
    advanceForm.reset();
    advanceForm.clearErrors();
    advanceForm.date = today;
    advanceForm.actual_cost = repair.estimated_cost ?? '';
    advancing.value = repair;
};
const saveAdvance = () =>
    advanceForm.post(route('students.repairs.advance', [props.student.id, advancing.value.id]), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (advancing.value = null),
    });

const deleting = ref(null); // { kind: 'visit'|'repair', item }
const destroy = () => {
    const { kind, item } = deleting.value;
    router.delete(route(kind === 'visit' ? 'students.visits.destroy' : 'students.repairs.destroy', [props.student.id, item.id]), { preserveScroll: true });
    deleting.value = null;
};

const steps = ['planned', 'in_progress', 'completed'];
const stepLabels = { planned: 'Planned', in_progress: 'In progress', completed: 'Completed' };
</script>

<template>
    <section v-if="!data.household" class="card-container py-10 text-center text-sm text-text-muted">
        {{ student.first_name }} isn't linked to a household yet. Use <strong>Edit</strong> on the profile to link one, then record visits and repairs here.
    </section>

    <div v-else class="space-y-4">
        <!-- Visits -->
        <section class="card-container">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="page-heading-2 !mb-0 flex items-center gap-2"><Home class="h-4 w-4" /> Home visits — {{ data.household.name }} household</h2>
                    <p class="text-sm text-text-muted">
                        Next visit due:
                        <strong :class="data.visitOverdue ? 'text-red-700' : 'text-mustBlue dark:text-text-inverted'">
                            {{ data.nextVisitDue ? date.formatDate(data.nextVisitDue) : 'never visited' }}
                        </strong>
                        <span v-if="data.visitOverdue" class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-800">Due</span>
                    </p>
                </div>
                <button v-if="canManage" type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="openVisit">
                    <Plus class="h-4 w-4" /> Record visit
                </button>
            </div>

            <p v-if="!data.visits.length" class="py-6 text-center text-sm text-text-muted">No visits recorded yet.</p>
            <ul v-else class="space-y-3">
                <li v-for="v in data.visits" :key="v.id" class="rounded-lg border border-mustBorder-light p-4 text-sm dark:border-mustBorder-dark">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span class="font-semibold text-mustBlue dark:text-text-inverted">{{ date.formatDate(v.visit_date) }}</span>
                        <span v-if="v.members_count" class="text-text-muted">{{ v.members_count }} people in the home</span>
                        <span class="text-text-muted">· next visit {{ date.formatDate(v.next_visit_due) }}</span>
                        <span class="text-xs text-text-muted">· by {{ v.recorded_by ?? 'unknown' }}</span>
                        <button v-if="canDelete" type="button" class="ml-auto text-red-500 hover:text-red-700" :aria-label="`Delete visit on ${v.visit_date}`" @click="deleting = { kind: 'visit', item: v }">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                    <dl class="mt-2 grid gap-x-4 gap-y-1 sm:grid-cols-[10rem_1fr]">
                        <template v-if="v.living_conditions"><dt class="text-text-muted">Living conditions</dt><dd>{{ v.living_conditions }}</dd></template>
                        <template v-if="v.income_sources"><dt class="text-text-muted">Income sources</dt><dd>{{ v.income_sources }}</dd></template>
                        <template v-if="v.needs_identified"><dt class="text-text-muted">Needs found</dt><dd>{{ v.needs_identified }}</dd></template>
                    </dl>
                    <div v-if="v.photos.length" class="mt-3 flex flex-wrap gap-2">
                        <a v-for="p in v.photos" :key="p.id" :href="p.url" target="_blank" rel="noopener" :aria-label="`Open photo ${p.name ?? ''}`">
                            <img :src="p.url" alt="" class="h-20 w-28 rounded-md object-cover" loading="lazy" />
                        </a>
                    </div>
                </li>
            </ul>
        </section>

        <!-- Repairs -->
        <section class="card-container">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="page-heading-2 !mb-0 flex items-center gap-2"><Hammer class="h-4 w-4" /> Home repairs</h2>
                    <p class="text-sm text-text-muted">
                        Planned → In progress → Completed.
                        <template v-if="data.repairApprovalLimit !== null">Budgets over {{ money(data.repairApprovalLimit) }} need Admin approval first.</template>
                    </p>
                </div>
                <button v-if="canManage" type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="openRepair">
                    <Plus class="h-4 w-4" /> Plan a repair
                </button>
            </div>

            <p v-if="!data.repairs.length" class="py-6 text-center text-sm text-text-muted">No repairs recorded.</p>
            <ul v-else class="space-y-3">
                <li v-for="r in data.repairs" :key="r.id" class="rounded-lg border border-mustBorder-light p-4 text-sm dark:border-mustBorder-dark">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-mustBlue dark:text-text-inverted">{{ r.description }}</p>
                            <p class="text-text-muted">
                                Estimate {{ money(r.estimated_cost) }}
                                <template v-if="r.actual_cost !== null"> · actual {{ money(r.actual_cost) }}</template>
                                <template v-if="r.started_on"> · started {{ date.formatDate(r.started_on) }}</template>
                                <template v-if="r.completed_on"> · finished {{ date.formatDate(r.completed_on) }}</template>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                v-if="canManage && r.approval_status === 'approved' && r.next_status_label"
                                type="button"
                                class="btn-primary-sm inline-flex items-center gap-1"
                                @click="openAdvance(r)"
                            >
                                Mark {{ r.next_status_label.toLowerCase() }} <ChevronRight class="h-4 w-4" />
                            </button>
                            <Link v-if="canApprove && r.approval_status === 'pending'" :href="route('finance.approvals.index')" class="btn-outline-sm">Review in Approvals</Link>
                            <button v-if="canDelete" type="button" class="text-red-500 hover:text-red-700" :aria-label="`Delete repair ${r.description}`" @click="deleting = { kind: 'repair', item: r }">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Progress -->
                    <ol v-if="r.approval_status === 'approved'" class="mt-3 flex items-center gap-2 text-xs" aria-label="Repair progress">
                        <li v-for="(step, i) in steps" :key="step" class="flex items-center gap-2">
                            <span
                                class="rounded-full px-2.5 py-1 font-medium"
                                :class="steps.indexOf(r.status) >= i ? 'bg-mustGreen text-white' : 'bg-gray-100 text-text-muted dark:bg-gray-700'"
                                :aria-current="r.status === step ? 'step' : undefined"
                            >{{ stepLabels[step] }}</span>
                            <ChevronRight v-if="i < steps.length - 1" class="h-3 w-3 text-text-muted" />
                        </li>
                    </ol>
                    <p v-else-if="r.approval_status === 'pending'" class="mt-3 rounded-md bg-amber-50 p-2 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">Budget awaiting Admin approval — work can't start yet.</p>
                    <p v-else class="mt-3 rounded-md bg-gray-100 p-2 text-gray-700 dark:bg-gray-700 dark:text-gray-200">Budget rejected: {{ r.rejection_reason }}</p>

                    <div v-if="r.photos.length" class="mt-3 flex flex-wrap gap-2">
                        <a v-for="p in r.photos" :key="p.id" :href="p.url" target="_blank" rel="noopener" class="relative" :aria-label="`Open ${p.kind} photo`">
                            <img :src="p.url" alt="" class="h-20 w-28 rounded-md object-cover" loading="lazy" />
                            <span class="absolute left-1 top-1 rounded bg-black/60 px-1.5 text-[10px] uppercase text-white">{{ p.kind }}</span>
                        </a>
                    </div>
                </li>
            </ul>
        </section>

        <!-- Record visit -->
        <Modal maxWidth="2xl" :show="showVisit" :closeOnClickOutside="false" @close="showVisit = false">
            <div class="p-6">
                <ModalHeader title="Record a home visit" :close="() => (showVisit = false)" />
                <form class="space-y-4" @submit.prevent="saveVisit">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <InputLabel for="v_date" value="Visit date" />
                            <TextInput id="v_date" v-model="visitForm.visit_date" type="date" :max="today" class="mt-1 block w-full" required />
                            <InputError class="mt-1" :message="visitForm.errors.visit_date" />
                        </div>
                        <div>
                            <InputLabel for="v_members" value="People in the home" />
                            <TextInput id="v_members" v-model="visitForm.members_count" type="number" min="1" class="mt-1 block w-full" />
                            <InputError class="mt-1" :message="visitForm.errors.members_count" />
                        </div>
                        <div>
                            <InputLabel for="v_next" value="Next visit due" />
                            <TextInput id="v_next" v-model="visitForm.next_visit_due" type="date" class="mt-1 block w-full" />
                            <p class="mt-1 text-xs text-text-muted">Blank = in {{ data.defaultNextVisitMonths }} months</p>
                            <InputError class="mt-1" :message="visitForm.errors.next_visit_due" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="v_living" value="Living conditions" />
                        <TextArea id="v_living" v-model="visitForm.living_conditions" rows="2" class="mt-1 block w-full" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="v_income" value="Income sources" />
                            <TextArea id="v_income" v-model="visitForm.income_sources" rows="2" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="v_needs" value="Needs found" />
                            <TextArea id="v_needs" v-model="visitForm.needs_identified" rows="2" class="mt-1 block w-full" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="v_photos" value="Photos (optional)" />
                        <template v-if="data.photoConsent">
                            <input id="v_photos" type="file" multiple accept=".jpg,.jpeg,.png,.webp" :class="fileClass" @input="visitForm.photos = Array.from($event.target.files)" />
                            <p class="mt-1 text-xs text-text-muted">Up to 8 photos, 8 MB each. Kept private.</p>
                        </template>
                        <p v-else class="mt-1 flex items-start gap-2 rounded-md bg-amber-50 p-2 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                            <Camera class="mt-0.5 h-3.5 w-3.5 shrink-0" /> No guardian in this household has given photo consent, so photos can't be added.
                        </p>
                        <InputError class="mt-1" :message="visitForm.errors.photos || visitForm.errors['photos.0']" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-outline-sm" @click="showVisit = false">Cancel</button>
                        <button type="submit" class="btn-primary-sm" :disabled="visitForm.processing">Save visit</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Plan repair -->
        <Modal maxWidth="lg" :show="showRepair" :closeOnClickOutside="false" @close="showRepair = false">
            <div class="p-6">
                <ModalHeader title="Plan a repair" :close="() => (showRepair = false)" />
                <form class="space-y-4" @submit.prevent="saveRepair">
                    <div>
                        <InputLabel for="r_desc" value="What needs repairing?" />
                        <TextArea id="r_desc" v-model="repairForm.description" rows="2" placeholder="e.g. Roof leaks over the bedroom; door won't lock" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="repairForm.errors.description" />
                    </div>
                    <div>
                        <InputLabel for="r_cost" :value="`Estimated cost (${currency})`" />
                        <TextInput id="r_cost" v-model="repairForm.estimated_cost" type="number" min="0" step="0.01" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="repairForm.errors.estimated_cost" />
                        <p v-if="data.repairApprovalLimit !== null && Number(repairForm.estimated_cost) > data.repairApprovalLimit && !canApprove" class="mt-2 rounded-md bg-amber-50 p-2 text-xs text-amber-800">
                            Over {{ money(data.repairApprovalLimit) }} — this will wait for Admin approval.
                        </p>
                    </div>
                    <div>
                        <InputLabel for="r_photos" value="Before photos (optional)" />
                        <input id="r_photos" type="file" multiple accept=".jpg,.jpeg,.png,.webp" :class="fileClass" @input="repairForm.photos = Array.from($event.target.files)" />
                        <InputError class="mt-1" :message="repairForm.errors.photos || repairForm.errors['photos.0']" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-outline-sm" @click="showRepair = false">Cancel</button>
                        <button type="submit" class="btn-primary-sm" :disabled="repairForm.processing">Save</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Advance repair -->
        <Modal maxWidth="lg" :show="!!advancing" :closeOnClickOutside="false" @close="advancing = null">
            <div v-if="advancing" class="p-6">
                <ModalHeader :title="`Mark as ${advancing.next_status_label.toLowerCase()}`" :close="() => (advancing = null)" />
                <form class="space-y-4" @submit.prevent="saveAdvance">
                    <p class="text-sm text-text-muted">{{ advancing.description }}</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="a_date" :value="advancing.status === 'planned' ? 'Work started on' : 'Finished on'" />
                            <TextInput id="a_date" v-model="advanceForm.date" type="date" :max="today" class="mt-1 block w-full" />
                            <InputError class="mt-1" :message="advanceForm.errors.date" />
                        </div>
                        <div v-if="advancing.status === 'in_progress'">
                            <InputLabel for="a_cost" :value="`Actual cost (${currency})`" />
                            <TextInput id="a_cost" v-model="advanceForm.actual_cost" type="number" min="0" step="0.01" class="mt-1 block w-full" required />
                            <InputError class="mt-1" :message="advanceForm.errors.actual_cost" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="a_photos" :value="advancing.status === 'in_progress' ? 'After photos (optional)' : 'Photos (optional)'" />
                        <input id="a_photos" type="file" multiple accept=".jpg,.jpeg,.png,.webp" :class="fileClass" @input="advanceForm.photos = Array.from($event.target.files)" />
                        <InputError class="mt-1" :message="advanceForm.errors.photos || advanceForm.errors['photos.0'] || advanceForm.errors.status" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-outline-sm" @click="advancing = null">Cancel</button>
                        <button type="submit" class="btn-primary-sm" :disabled="advanceForm.processing">Save</button>
                    </div>
                </form>
            </div>
        </Modal>

        <ConfirmationModal :show="!!deleting" :close="() => (deleting = null)" @confirm="destroy">
            <template #title>Delete {{ deleting?.kind }}</template>
            <template #message>Delete this {{ deleting?.kind }} and its photos? This can't be undone.</template>
        </ConfirmationModal>
    </div>
</template>

<script setup>
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { date } from '@/Helpers';
import { can } from '@/Helpers/can';
import { Link, router, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Lock, MessagesSquare, Plus, SquarePen, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    student: { type: Object, required: true },
    data: { type: Object, required: true },
    mentors: { type: Array, default: () => [] },
    sessionTypes: { type: Array, default: () => [] },
});

const canManage = can('manage mentorship');
const canDelete = can('delete support records');
const today = new Date().toISOString().slice(0, 10);

// --- Mentor assignment ---
const assignForm = useForm({ mentor_id: props.data.mentor?.id ?? '' });
const assign = () =>
    assignForm
        .transform((d) => ({ mentor_id: d.mentor_id || null }))
        .put(route('students.mentor.assign', props.student.id), { preserveScroll: true });
const mentorOptions = computed(() => [{ value: '', label: 'No mentor' }, ...props.mentors]);

// --- Sessions ---
const showSession = ref(false);
const editing = ref(null);
const sessionForm = useForm({ session_date: today, type: 'mentorship', mentor_id: '', notes: '', follow_up_needed: false });

const openSession = (session = null) => {
    editing.value = session;
    sessionForm.clearErrors();
    sessionForm.session_date = session?.session_date ?? today;
    sessionForm.type = session?.type ?? 'mentorship';
    sessionForm.mentor_id = session?.mentor_id ?? props.data.mentor?.id ?? '';
    sessionForm.notes = session?.notes ?? '';
    sessionForm.follow_up_needed = session?.follow_up_needed ?? false;
    showSession.value = true;
};
const saveSession = () => {
    const options = { preserveScroll: true, onSuccess: () => (showSession.value = false) };
    const payload = (d) => ({ ...d, mentor_id: d.mentor_id || null });
    if (editing.value) {
        sessionForm.transform(payload).put(route('students.sessions.update', [props.student.id, editing.value.id]), options);
    } else {
        sessionForm.transform(payload).post(route('students.sessions.store', props.student.id), options);
    }
};

const deleting = ref(null);
const destroy = () => {
    router.delete(route('students.sessions.destroy', [props.student.id, deleting.value.id]), { preserveScroll: true });
    deleting.value = null;
};
</script>

<template>
    <div class="grid gap-4 xl:grid-cols-[1fr_2fr]">
        <!-- Mentor & summary -->
        <section class="card-container">
            <h2 class="page-heading-2">Mentor</h2>
            <div v-if="data.mentor" class="mb-4 text-sm">
                <p class="text-base font-semibold text-mustBlue dark:text-text-inverted">{{ data.mentor.name }}</p>
                <p class="text-text-muted">
                    <template v-if="data.mentor.phone">{{ data.mentor.phone }} · </template>since {{ date.formatDate(data.mentor.since) }}
                </p>
            </div>
            <p v-else class="mb-4 text-sm text-text-muted">No mentor assigned.</p>

            <form v-if="canManage" class="flex flex-wrap items-end gap-2" @submit.prevent="assign">
                <div class="min-w-0 flex-1">
                    <InputLabel for="assign_mentor" :value="data.mentor ? 'Change mentor' : 'Assign a mentor'" />
                    <SelectInput id="assign_mentor" v-model="assignForm.mentor_id" :options="mentorOptions" />
                </div>
                <button type="submit" class="btn-primary-sm" :disabled="assignForm.processing || assignForm.mentor_id === (data.mentor?.id ?? '')">Save</button>
            </form>
            <InputError class="mt-1" :message="assignForm.errors.mentor_id" />
            <Link v-if="canManage" :href="route('support.mentors.index')" class="mt-2 inline-block text-xs text-mustGreen hover:underline">Manage the mentor list</Link>

            <dl class="mt-5 grid grid-cols-[8rem_1fr] gap-y-2 border-t border-mustBorder-light pt-4 text-sm dark:border-mustBorder-dark">
                <dt class="text-text-muted">Last session</dt>
                <dd>{{ data.summary.last_session ? date.formatDate(data.summary.last_session) : 'None yet' }}</dd>
                <dt class="text-text-muted">Follow-up</dt>
                <dd>
                    <span v-if="data.summary.follow_up_needed" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Needed</span>
                    <span v-else class="text-text-muted">Not needed</span>
                </dd>
            </dl>
            <p v-if="data.summary.is_overdue" class="mt-3 flex items-start gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-red-500/10 dark:text-red-300">
                <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" /> Overdue — no session in the last {{ data.summary.overdue_days }} days.
            </p>
        </section>

        <!-- Sessions -->
        <section class="card-container">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="page-heading-2 !mb-0 flex items-center gap-2"><MessagesSquare class="h-4 w-4" /> Sessions</h2>
                    <p class="flex items-center gap-1 text-xs text-text-muted">
                        <Lock class="h-3 w-3" />
                        Notes are private: visible to {{ data.notesVisibleToAll ? 'all managers' : 'the person who wrote them' }} and the Admin.
                    </p>
                </div>
                <button v-if="canManage" type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="openSession()">
                    <Plus class="h-4 w-4" /> Record session
                </button>
            </div>

            <p v-if="!data.sessions.length" class="py-6 text-center text-sm text-text-muted">No sessions recorded yet.</p>
            <ul v-else class="divide-y divide-mustBorder-light dark:divide-mustBorder-dark">
                <li v-for="s in data.sessions" :key="s.id" class="py-3 text-sm">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span class="font-semibold text-mustBlue dark:text-text-inverted">{{ date.formatDate(s.session_date) }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs" :class="s.type === 'counselling' ? 'bg-purple-100 text-purple-800' : 'bg-sky-100 text-sky-800'">{{ s.type_label }}</span>
                        <span class="text-text-muted">{{ s.mentor ?? 'No mentor' }}</span>
                        <span v-if="s.follow_up_needed" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Follow-up needed</span>
                        <span class="ml-auto flex items-center gap-3">
                            <button v-if="canManage && s.can_edit" type="button" class="action-icon" :aria-label="`Edit session on ${s.session_date}`" @click="openSession(s)">
                                <SquarePen class="h-4 w-4" />
                            </button>
                            <button v-if="canDelete" type="button" class="text-red-500 hover:text-red-700" :aria-label="`Delete session on ${s.session_date}`" @click="deleting = s">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </span>
                    </div>
                    <p v-if="s.notes" class="mt-1 whitespace-pre-line text-text-muted">{{ s.notes }}</p>
                    <p v-else-if="s.notes_hidden" class="mt-1 flex items-center gap-1 text-xs italic text-text-muted"><Lock class="h-3 w-3" /> Notes are private to {{ s.recorded_by ?? 'their author' }}.</p>
                    <p class="mt-1 text-xs text-text-muted">Recorded by {{ s.recorded_by ?? 'unknown' }}</p>
                </li>
            </ul>
        </section>

        <Modal maxWidth="lg" :show="showSession" :closeOnClickOutside="false" @close="showSession = false">
            <div class="p-6">
                <ModalHeader :title="editing ? 'Edit session' : 'Record a session'" :close="() => (showSession = false)" />
                <form class="space-y-4" @submit.prevent="saveSession">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="ses_date" value="Date" />
                            <TextInput id="ses_date" v-model="sessionForm.session_date" type="date" :max="today" class="mt-1 block w-full" required />
                            <InputError class="mt-1" :message="sessionForm.errors.session_date" />
                        </div>
                        <div>
                            <InputLabel for="ses_type" value="Type" />
                            <SelectInput id="ses_type" v-model="sessionForm.type" :options="sessionTypes" required />
                            <InputError class="mt-1" :message="sessionForm.errors.type" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="ses_mentor" value="Mentor / counsellor" />
                        <SelectInput id="ses_mentor" v-model="sessionForm.mentor_id" :options="mentorOptions" />
                        <InputError class="mt-1" :message="sessionForm.errors.mentor_id" />
                    </div>
                    <div>
                        <InputLabel for="ses_notes" value="Short note (private)" />
                        <TextArea id="ses_notes" v-model="sessionForm.notes" rows="4" class="mt-1 block w-full" />
                        <InputError class="mt-1" :message="sessionForm.errors.notes" />
                    </div>
                    <label class="flex items-center gap-2 text-sm"><Checkbox v-model="sessionForm.follow_up_needed" /> Follow-up needed</label>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-outline-sm" @click="showSession = false">Cancel</button>
                        <button type="submit" class="btn-primary-sm" :disabled="sessionForm.processing">Save</button>
                    </div>
                </form>
            </div>
        </Modal>

        <ConfirmationModal :show="!!deleting" :close="() => (deleting = null)" @confirm="destroy">
            <template #title>Delete session</template>
            <template #message>Delete the session on {{ date.formatDate(deleting?.session_date) }}?</template>
        </ConfirmationModal>
    </div>
</template>

<script setup>
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SearchableSelectInput from '@/Components/FormElements/SearchableSelectInput.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { date } from '@/Helpers';
import { can } from '@/Helpers/can';
import { formatMoney } from '@/Helpers/money';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SponsorForm from '@/Pages/Sponsors/Partials/SponsorForm.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, FileDown, MessageSquareText, Plus, SquarePen, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    currency: { type: String, default: 'MWK' },
    types: { type: Array, default: () => [] },
    sponsor: { type: Object, required: true },
    sponsorships: { type: Array, default: () => [] },
    students: { type: Array, default: () => [] },
});

const money = (v) => formatMoney(v, props.currency);
const today = new Date().toISOString().slice(0, 10);
const active = computed(() => props.sponsorships.filter((s) => s.is_active));
const past = computed(() => props.sponsorships.filter((s) => !s.is_active));
const missingUpdates = computed(() => active.value.filter((s) => !s.progress_update).length);

const editingSponsor = ref(false);
const deletingSponsor = ref(false);

// Link / edit a sponsorship
const linkModal = ref(null); // { sponsorship | null }
const linkForm = useForm({ student_id: null, start_date: today, end_date: '', amount_per_year: '' });
const openLink = (s = null) => {
    linkForm.clearErrors();
    linkForm.student_id = s?.student_id ?? null;
    linkForm.start_date = s?.start_date ?? today;
    linkForm.end_date = s?.end_date ?? '';
    linkForm.amount_per_year = s?.amount_per_year ?? '';
    linkModal.value = { sponsorship: s };
};
const saveLink = () => {
    const opts = { preserveScroll: true, onSuccess: () => (linkModal.value = null) };
    const s = linkModal.value.sponsorship;
    const payload = (d) => ({ ...d, end_date: d.end_date || null, amount_per_year: d.amount_per_year === '' ? null : d.amount_per_year });
    s
        ? linkForm.transform(payload).put(route('sponsors.sponsorships.update', [props.sponsor.id, s.id]), opts)
        : linkForm.transform(payload).post(route('sponsors.sponsorships.store', props.sponsor.id), opts);
};

// Progress update
const updating = ref(null);
const updateForm = useForm({ progress_update: '' });
const openUpdate = (s) => {
    updateForm.clearErrors();
    updateForm.progress_update = s.progress_update ?? '';
    updating.value = s;
};
const saveUpdate = () =>
    updateForm.put(route('sponsors.sponsorships.progress', [props.sponsor.id, updating.value.id]), {
        preserveScroll: true,
        onSuccess: () => (updating.value = null),
    });
</script>

<template>
    <Head :title="sponsor.name" />

    <AuthenticatedLayout>
        <Link :href="route('sponsors.index')" class="mb-4 inline-flex items-center gap-1 text-sm text-mustBlue dark:text-text-dark-muted"><ArrowLeft class="h-4 w-4" /> Sponsors</Link>

        <div class="card-container mb-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-mustBlue dark:text-text-inverted">{{ sponsor.name }}</h1>
                    <p class="text-sm text-text-muted">{{ sponsor.type_label }}<template v-if="sponsor.email"> · {{ sponsor.email }}</template><template v-if="sponsor.phone"> · {{ sponsor.phone }}</template></p>
                    <p v-if="sponsor.notes" class="mt-2 whitespace-pre-line text-sm text-text-muted">{{ sponsor.notes }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a :href="route('sponsors.report', sponsor.id)" class="btn-primary-sm inline-flex items-center gap-1"><FileDown class="h-4 w-4" /> Progress report (PDF)</a>
                    <button type="button" class="btn-outline-sm inline-flex items-center gap-1" @click="editingSponsor = true"><SquarePen class="h-4 w-4" /> Edit</button>
                    <button v-if="can('delete sponsors')" type="button" class="btn-danger-sm inline-flex items-center gap-1" @click="deletingSponsor = true"><Trash2 class="h-4 w-4" /> Delete</button>
                </div>
            </div>
            <p v-if="missingUpdates" class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                {{ missingUpdates }} {{ missingUpdates === 1 ? 'student has' : 'students have' }} no progress update yet. Add a short note before sending the report.
            </p>
        </div>

        <section class="card-container">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 class="page-heading-2 !mb-0">Students sponsored ({{ active.length }})</h2>
                <button type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="openLink()"><Plus class="h-4 w-4" /> Link a student</button>
            </div>

            <p v-if="!active.length" class="py-6 text-center text-sm text-text-muted">No students linked yet.</p>
            <ul class="space-y-3">
                <li v-for="s in active" :key="s.id" class="rounded-lg border border-mustBorder-light p-4 text-sm dark:border-mustBorder-dark">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <Link :href="route('students.show', s.student_id)" class="text-base font-semibold text-mustGreen hover:underline">{{ s.student }}</Link>
                            <p class="text-text-muted">
                                {{ s.status }}<template v-if="s.level"> · {{ s.level }}</template><template v-if="s.school"> at {{ s.school }}</template>
                                · since {{ date.formatDate(s.start_date) }}<template v-if="s.amount_per_year"> · {{ money(s.amount_per_year) }} a year</template>
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="btn-outline-sm inline-flex items-center gap-1" @click="openUpdate(s)"><MessageSquareText class="h-4 w-4" /> {{ s.progress_update ? 'Edit update' : 'Write update' }}</button>
                            <button type="button" class="action-icon" :aria-label="`Edit sponsorship of ${s.student}`" @click="openLink(s)"><SquarePen class="h-4 w-4" /></button>
                        </div>
                    </div>
                    <p v-if="s.progress_update" class="mt-2 rounded-md bg-mustGreen/5 p-3 italic">“{{ s.progress_update }}”
                        <span class="mt-1 block text-xs not-italic text-text-muted">{{ s.progress_update_by }}, {{ date.formatDate(s.progress_update_on) }}</span>
                    </p>
                </li>
            </ul>

            <details v-if="past.length" class="mt-4 text-sm">
                <summary class="cursor-pointer text-text-muted">Ended sponsorships ({{ past.length }})</summary>
                <ul class="mt-2 space-y-1 text-text-muted">
                    <li v-for="s in past" :key="s.id">{{ s.student }} · {{ date.formatDate(s.start_date) }} – {{ date.formatDate(s.end_date) }}</li>
                </ul>
            </details>
        </section>

        <!-- Edit sponsor -->
        <Modal maxWidth="lg" :show="editingSponsor" :closeOnClickOutside="false" @close="editingSponsor = false">
            <div class="p-6">
                <ModalHeader title="Edit sponsor" :close="() => (editingSponsor = false)" />
                <SponsorForm :sponsor="sponsor" :types="types" @saved="editingSponsor = false" @cancel="editingSponsor = false" />
            </div>
        </Modal>

        <!-- Link / edit sponsorship -->
        <Modal maxWidth="lg" :show="!!linkModal" :closeOnClickOutside="false" @close="linkModal = null">
            <div v-if="linkModal" class="p-6">
                <ModalHeader :title="linkModal.sponsorship ? `Sponsorship of ${linkModal.sponsorship.student}` : 'Link a student'" :close="() => (linkModal = null)" />
                <form class="space-y-4" @submit.prevent="saveLink">
                    <div v-if="!linkModal.sponsorship">
                        <InputLabel for="ls_student" value="Student" />
                        <SearchableSelectInput id="ls_student" v-model="linkForm.student_id" :options="students" placeholder="Search students" class="mt-1" />
                        <InputError class="mt-1" :message="linkForm.errors.student_id" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="ls_start" value="Start date" />
                            <TextInput id="ls_start" v-model="linkForm.start_date" type="date" class="mt-1 block w-full" required />
                            <InputError class="mt-1" :message="linkForm.errors.start_date" />
                        </div>
                        <div>
                            <InputLabel for="ls_end" value="End date (to end the sponsorship)" />
                            <TextInput id="ls_end" v-model="linkForm.end_date" type="date" class="mt-1 block w-full" />
                            <InputError class="mt-1" :message="linkForm.errors.end_date" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="ls_amount" :value="`Amount per year (${currency}, optional)`" />
                        <TextInput id="ls_amount" v-model="linkForm.amount_per_year" type="number" min="0" step="0.01" class="mt-1 block w-full" />
                        <InputError class="mt-1" :message="linkForm.errors.amount_per_year" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-outline-sm" @click="linkModal = null">Cancel</button>
                        <button type="submit" class="btn-primary-sm" :disabled="linkForm.processing">Save</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Progress update -->
        <Modal maxWidth="lg" :show="!!updating" :closeOnClickOutside="false" @close="updating = null">
            <div v-if="updating" class="p-6">
                <ModalHeader :title="`Progress update — ${updating.student}`" :close="() => (updating = null)" />
                <form class="space-y-4" @submit.prevent="saveUpdate">
                    <p class="text-sm text-text-muted">A few friendly sentences for the sponsor. It is printed in their report exactly as written — keep private matters (health, family problems, counselling) out of it.</p>
                    <div>
                        <InputLabel for="pu_text" value="Update" />
                        <TextArea id="pu_text" v-model="updateForm.progress_update" rows="5" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="updateForm.errors.progress_update" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-outline-sm" @click="updating = null">Cancel</button>
                        <button type="submit" class="btn-primary-sm" :disabled="updateForm.processing">Save update</button>
                    </div>
                </form>
            </div>
        </Modal>

        <ConfirmationModal :show="deletingSponsor" :close="() => (deletingSponsor = false)" @confirm="router.delete(route('sponsors.destroy', sponsor.id))">
            <template #title>Delete sponsor</template>
            <template #message>Delete <strong>{{ sponsor.name }}</strong> and all their sponsorship links? Students are not affected.</template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

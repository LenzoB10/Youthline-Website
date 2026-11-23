<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { date } from '@/Helpers';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { CalendarPlus, Plus, SquarePen, Star, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    years: { type: Array, default: () => [] },
});

const range = (start, end) => {
    if (!start && !end) {
        return 'No dates set';
    }
    return `${start ? date.formatDate(start) : '…'} – ${end ? date.formatDate(end) : '…'}`;
};

// ---- Academic year form ----
const showYearForm = ref(false);
const yearForm = useForm({ id: null, name: '', start_date: '', end_date: '', is_current: false });

const openYear = (year = null) => {
    yearForm.reset();
    yearForm.clearErrors();
    if (year) {
        yearForm.id = year.id;
        yearForm.name = year.name;
        yearForm.start_date = year.start_date ?? '';
        yearForm.end_date = year.end_date ?? '';
    }
    showYearForm.value = true;
};

const closeYear = () => {
    showYearForm.value = false;
    yearForm.reset();
};

const submitYear = () => {
    const options = { preserveScroll: true, onSuccess: closeYear };
    yearForm.id
        ? yearForm.transform(({ is_current, ...data }) => data).put(route('admin.settings.years.update', yearForm.id), options)
        : yearForm.post(route('admin.settings.years.store'), options);
};

// ---- Term form ----
const showTermForm = ref(false);
const termYear = ref(null);
const termForm = useForm({ id: null, academic_year_id: null, name: '', start_date: '', end_date: '' });

const openTerm = (year, term = null) => {
    termForm.reset();
    termForm.clearErrors();
    termYear.value = year;
    termForm.academic_year_id = year.id;
    if (term) {
        termForm.id = term.id;
        termForm.name = term.name;
        termForm.start_date = term.start_date ?? '';
        termForm.end_date = term.end_date ?? '';
    } else {
        termForm.name = `Term ${year.terms.length + 1}`;
    }
    showTermForm.value = true;
};

const closeTerm = () => {
    showTermForm.value = false;
    termForm.reset();
};

const submitTerm = () => {
    const options = { preserveScroll: true, onSuccess: closeTerm };
    termForm.id
        ? termForm
              .transform(({ academic_year_id, ...data }) => data)
              .put(route('admin.settings.terms.update', termForm.id), options)
        : termForm.post(route('admin.settings.terms.store'), options);
};

// ---- Make current / delete ----
const makeYearCurrent = (year) => router.put(route('admin.settings.years.current', year.id), {}, { preserveScroll: true });
const makeTermCurrent = (term) => router.put(route('admin.settings.terms.current', term.id), {}, { preserveScroll: true });

const pendingDelete = ref(null);
const confirmDelete = (kind, item) => {
    pendingDelete.value = { kind, item };
};
const destroy = () => {
    const { kind, item } = pendingDelete.value;
    router.delete(route(kind === 'year' ? 'admin.settings.years.destroy' : 'admin.settings.terms.destroy', item.id), {
        preserveScroll: true,
    });
    pendingDelete.value = null;
};
</script>

<template>
    <Head title="Academic Calendar" />

    <AuthenticatedLayout>
        <BackLink :href="route('admin.settings.index')">Settings</BackLink>
        <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
            <h1 class="page-heading !mb-0">Academic Calendar</h1>
            <button type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="openYear()">
                <Plus class="h-4 w-4" /> Add academic year
            </button>
        </div>
        <p class="mb-6 text-sm text-text-muted dark:text-text-dark-muted">
            Exactly one year is current at a time. Making a term current also makes its year current.
            Years and terms that already hold enrolments, results or fees cannot be deleted.
        </p>

        <div v-if="!years.length" class="card-container py-10 text-center text-text-muted">
            No academic years yet. Add the first one to get started.
        </div>

        <div class="space-y-4">
            <section
                v-for="year in years"
                :key="year.id"
                class="card-container"
                :class="{ 'ring-2 ring-mustGreen': year.is_current }"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="flex items-center gap-2 text-lg font-semibold text-mustBlue dark:text-text-inverted">
                            {{ year.name }}
                            <span v-if="year.is_current" class="rounded-full bg-mustGreen/15 px-2 py-0.5 text-xs font-medium text-yl-green-deep">
                                Current year
                            </span>
                        </h2>
                        <p class="text-sm text-text-muted">
                            {{ range(year.start_date, year.end_date) }} · {{ year.enrollments_count }} enrolments
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button v-if="!year.is_current" type="button" class="btn-outline-sm" @click="makeYearCurrent(year)">
                            Make current
                        </button>
                        <button type="button" class="action-icon" :aria-label="`Edit ${year.name}`" @click="openYear(year)">
                            <SquarePen class="h-4 w-4" />
                        </button>
                        <button
                            v-if="!year.is_current"
                            type="button"
                            class="text-red-500 transition hover:text-red-700"
                            :aria-label="`Delete ${year.name}`"
                            @click="confirmDelete('year', year)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <ul class="mt-4 divide-y divide-mustBorder-light rounded-lg border border-mustBorder-light dark:divide-mustBorder-dark dark:border-mustBorder-dark">
                    <li v-if="!year.terms.length" class="px-4 py-3 text-sm text-text-muted">No terms yet.</li>
                    <li v-for="term in year.terms" :key="term.id" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                        <div class="text-sm">
                            <span class="font-medium text-mustBlue dark:text-text-inverted">{{ term.name }}</span>
                            <span v-if="term.is_current" class="ml-2 inline-flex items-center gap-1 rounded-full bg-mustGreen/15 px-2 py-0.5 text-xs font-medium text-yl-green-deep">
                                <Star class="h-3 w-3" /> Current term
                            </span>
                            <span class="block text-text-muted">{{ range(term.start_date, term.end_date) }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <button v-if="!term.is_current" type="button" class="text-xs font-medium text-mustGreen hover:underline" @click="makeTermCurrent(term)">
                                Make current
                            </button>
                            <button type="button" class="action-icon" :aria-label="`Edit ${term.name}`" @click="openTerm(year, term)">
                                <SquarePen class="h-4 w-4" />
                            </button>
                            <button
                                v-if="!term.is_current"
                                type="button"
                                class="text-red-500 transition hover:text-red-700"
                                :aria-label="`Delete ${term.name}`"
                                @click="confirmDelete('term', term)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </li>
                </ul>
                <button type="button" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-mustGreen hover:underline" @click="openTerm(year)">
                    <CalendarPlus class="h-4 w-4" /> Add term
                </button>
            </section>
        </div>

        <!-- Year form -->
        <Modal maxWidth="lg" :show="showYearForm" :closeOnClickOutside="false" @close="closeYear">
            <div class="p-6">
                <ModalHeader :title="yearForm.id ? 'Edit academic year' : 'Add academic year'" :close="closeYear" />
                <form class="mt-2 space-y-3" @submit.prevent="submitYear">
                    <div>
                        <InputLabel for="year-name" value="Name" />
                        <TextInput id="year-name" v-model="yearForm.name" class="mt-1 block w-full" placeholder="e.g. 2026" required autofocus />
                        <InputError class="mt-2" :message="yearForm.errors.name" />
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <InputLabel for="year-start" value="Start date" />
                            <TextInput id="year-start" v-model="yearForm.start_date" type="date" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="yearForm.errors.start_date" />
                        </div>
                        <div>
                            <InputLabel for="year-end" value="End date" />
                            <TextInput id="year-end" v-model="yearForm.end_date" type="date" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="yearForm.errors.end_date" />
                        </div>
                    </div>
                    <label v-if="!yearForm.id" class="flex items-center gap-2 text-sm text-text-muted">
                        <Checkbox v-model="yearForm.is_current" />
                        Make this the current academic year
                    </label>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn-outline-sm" @click="closeYear">Cancel</button>
                        <button type="submit" class="btn-primary-sm" :disabled="yearForm.processing">Save</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Term form -->
        <Modal maxWidth="lg" :show="showTermForm" :closeOnClickOutside="false" @close="closeTerm">
            <div class="p-6">
                <ModalHeader
                    :title="`${termForm.id ? 'Edit' : 'Add'} term${termYear ? ' — ' + termYear.name : ''}`"
                    :close="closeTerm"
                />
                <form class="mt-2 space-y-3" @submit.prevent="submitTerm">
                    <div>
                        <InputLabel for="term-name" value="Name" />
                        <TextInput id="term-name" v-model="termForm.name" class="mt-1 block w-full" required autofocus />
                        <InputError class="mt-2" :message="termForm.errors.name" />
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <InputLabel for="term-start" value="Start date" />
                            <TextInput id="term-start" v-model="termForm.start_date" type="date" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="termForm.errors.start_date" />
                        </div>
                        <div>
                            <InputLabel for="term-end" value="End date" />
                            <TextInput id="term-end" v-model="termForm.end_date" type="date" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="termForm.errors.end_date" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn-outline-sm" @click="closeTerm">Cancel</button>
                        <button type="submit" class="btn-primary-sm" :disabled="termForm.processing">Save</button>
                    </div>
                </form>
            </div>
        </Modal>

        <ConfirmationModal :show="!!pendingDelete" :close="() => (pendingDelete = null)" @confirm="destroy">
            <template #title>Delete {{ pendingDelete?.kind === 'year' ? 'academic year' : 'term' }}</template>
            <template #message>
                Delete <strong>{{ pendingDelete?.item.name }}</strong>?
                <span v-if="pendingDelete?.kind === 'year'">Its terms are deleted too.</span>
                Anything already holding student records cannot be deleted.
            </template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

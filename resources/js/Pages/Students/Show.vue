<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import ChangeStatusModal from '@/Components/Students/ChangeStatusModal.vue';
import ConsentPanel from '@/Components/Students/ConsentPanel.vue';
import DocumentsPanel from '@/Components/Students/DocumentsPanel.vue';
import EnrollmentModal from '@/Components/Students/EnrollmentModal.vue';
import FeesPanel from '@/Components/Students/FeesPanel.vue';
import ResultsPanel from '@/Components/Students/ResultsPanel.vue';
import BasicNeedsPanel from '@/Components/Students/Support/BasicNeedsPanel.vue';
import BusinessPanel from '@/Components/Students/Support/BusinessPanel.vue';
import CareersPanel from '@/Components/Students/Support/CareersPanel.vue';
import HomePanel from '@/Components/Students/Support/HomePanel.vue';
import MentorshipPanel from '@/Components/Students/Support/MentorshipPanel.vue';
import StatusBadge from '@/Components/Students/StatusBadge.vue';
import { date } from '@/Helpers';
import { can } from '@/Helpers/can';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { GraduationCap, Home, Layers, RefreshCw } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    student: { type: Object, required: true },
    household: { type: Object, default: null },
    programs: { type: Array, default: () => [] },
    enrollments: { type: Array, default: () => [] },
    documents: { type: Array, default: () => [] },
    consent: { type: Object, default: null },
    results: { type: Array, default: () => [] },
    fees: { type: Array, default: () => [] },
    feeTotals: { type: Object, default: () => ({ share: 0, paid: 0, balance: 0 }) },
    finance: { type: Object, required: true },
    support: { type: Object, required: true },
    sponsors: { type: Array, default: () => [] },
    currentYear: { type: String, default: null },
    options: { type: Object, required: true },
});

const tabs = [
    { key: 'overview', label: 'Overview' },
    { key: 'education', label: 'Education history' },
    { key: 'documents', label: 'Documents & consent' },
    { key: 'academics', label: 'Academics & fees' },
    { key: 'needs', label: 'Basic needs' },
    { key: 'mentorship', label: 'Mentorship' },
    { key: 'home', label: 'Home & family' },
    { key: 'business', label: 'Business' },
    { key: 'careers', label: 'Careers' },
];
const initial = window.location.hash.replace('#', '');
const activeTab = ref(tabs.some((t) => t.key === initial) ? initial : 'overview');
const selectTab = (key) => {
    activeTab.value = key;
    history.replaceState(null, '', `#${key}`);
};

const showStatus = ref(false);
const showEnrollment = ref(false);

const currentEnrollment = computed(() => props.enrollments.find((e) => e.is_current_year) ?? null);
const latestEnrollment = computed(() => props.enrollments[0] ?? null);
const activePrograms = computed(() => props.programs.filter((p) => !p.end_date));
const pastPrograms = computed(() => props.programs.filter((p) => p.end_date));

const genderLabel = { male: 'Male', female: 'Female', other: 'Other' };
const show = (value) => (value === null || value === undefined || value === '' ? '—' : value);
</script>

<template>
    <Head :title="student.full_name" />

    <AuthenticatedLayout>
        <BackLink :href="route('students.index')">Students</BackLink>

        <!-- Header -->
        <div class="card-container mb-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-bold text-mustBlue dark:text-text-inverted">{{ student.full_name }}</h1>
                        <StatusBadge :status="student.status" :label="student.status_label" />
                    </div>
                    <p class="mt-1 text-sm text-text-muted">
                        <template v-if="currentEnrollment">
                            {{ currentEnrollment.level_label }}<template v-if="currentEnrollment.institution"> at {{ currentEnrollment.institution }}</template>
                            ({{ currentEnrollment.year }})
                        </template>
                        <template v-else-if="currentYear">No level recorded for {{ currentYear }}</template>
                        <template v-if="student.age"> · {{ student.age }} years old</template>
                    </p>
                    <p v-if="student.exit_reason" class="mt-1 text-sm text-text-muted">
                        {{ student.status_label }} on {{ date.formatDate(student.exit_date) }} — <strong>{{ student.exit_reason }}</strong>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button v-if="can('change student status')" type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="showStatus = true">
                        <RefreshCw class="h-4 w-4" /> Change status
                    </button>
                </div>
            </div>
            <p v-if="student.status === 'applicant'" class="mt-4 rounded-lg bg-yl-gold/10 p-3 text-sm text-amber-800 dark:bg-yl-gold/10 dark:text-yl-gold">
                Waiting for assessment. After the home visit, use <strong>Change status → Active</strong> to start support.
            </p>
        </div>

        <!-- Tabs -->
        <div class="mb-4 overflow-x-auto border-b border-mustBorder-light dark:border-mustBorder-dark" role="tablist" aria-label="Student record">
            <div class="flex gap-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === tab.key"
                    class="-mb-px whitespace-nowrap border-b-2 px-4 py-2 text-sm font-medium transition"
                    :class="activeTab === tab.key ? 'border-mustGreen text-mustGreen' : 'border-transparent text-text-muted hover:text-mustBlue dark:hover:text-text-inverted'"
                    @click="selectTab(tab.key)"
                >
                    {{ tab.label }}
                </button>
            </div>
        </div>

        <!-- Overview -->
        <div v-if="activeTab === 'overview'" class="grid gap-4 lg:grid-cols-2">
            <section class="card-container">
                <h2 class="page-heading-2">Personal details</h2>
                <dl class="grid grid-cols-[9rem_1fr] gap-y-2 text-sm">
                    <dt class="text-text-muted">Date of birth</dt>
                    <dd>{{ student.date_of_birth ? date.formatDate(student.date_of_birth) : '—' }}</dd>
                    <dt class="text-text-muted">Gender</dt>
                    <dd>{{ genderLabel[student.gender] ?? '—' }}</dd>
                    <dt class="text-text-muted">Phone</dt>
                    <dd>{{ show(student.phone) }}</dd>
                    <dt class="text-text-muted">Home location</dt>
                    <dd>{{ show(student.home_location) }}</dd>
                    <dt class="text-text-muted">Registered</dt>
                    <dd>{{ date.formatDate(student.created_at) }}<template v-if="student.created_by"> by {{ student.created_by }}</template></dd>
                </dl>
            </section>

            <section class="card-container">
                <h2 class="page-heading-2">Guardian</h2>
                <dl class="grid grid-cols-[9rem_1fr] gap-y-2 text-sm">
                    <dt class="text-text-muted">Name</dt>
                    <dd>{{ show(student.guardian_name) }}</dd>
                    <dt class="text-text-muted">Relationship</dt>
                    <dd>{{ show(student.guardian_relationship) }}</dd>
                    <dt class="text-text-muted">Phone</dt>
                    <dd>{{ show(student.guardian_phone) }}</dd>
                </dl>
            </section>

            <section class="card-container">
                <h2 class="page-heading-2 flex items-center gap-2"><Home class="h-4 w-4" /> Household</h2>
                <template v-if="household">
                    <dl class="grid grid-cols-[9rem_1fr] gap-y-2 text-sm">
                        <dt class="text-text-muted">Head</dt>
                        <dd>{{ show(household.head_name) }}</dd>
                        <dt class="text-text-muted">Location</dt>
                        <dd>{{ [household.village_area, household.district].filter(Boolean).join(', ') || '—' }}</dd>
                        <dt class="text-text-muted">Phone</dt>
                        <dd>{{ show(household.phone) }}</dd>
                        <dt class="text-text-muted">People in home</dt>
                        <dd>{{ show(household.members_count) }}</dd>
                    </dl>
                    <div v-if="household.siblings.length" class="mt-4">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-text-muted">Also supported from this household</p>
                        <ul class="space-y-1 text-sm">
                            <li v-for="sibling in household.siblings" :key="sibling.id">
                                <Link :href="route('students.show', sibling.id)" class="text-mustGreen hover:underline">{{ sibling.full_name }}</Link>
                                <span class="text-text-muted"> · {{ sibling.status_label }}</span>
                            </li>
                        </ul>
                    </div>
                </template>
                <p v-else class="text-sm text-text-muted">Not linked to a household yet.<Link v-if="can('update student')" :href="route('students.edit', student.id)" class="ml-1 text-mustGreen hover:underline">Link one</Link></p>
            </section>

            <section class="card-container">
                <h2 class="page-heading-2">Sponsors</h2>
                <p v-if="!sponsors.length" class="text-sm text-text-muted">Not sponsored at the moment.</p>
                <ul v-else class="space-y-1 text-sm">
                    <li v-for="s in sponsors" :key="s.id" class="flex justify-between gap-3">
                        <Link v-if="can('manage sponsors')" :href="route('sponsors.show', s.id)" class="font-medium text-mustGreen hover:underline">{{ s.name }}</Link>
                        <span v-else class="font-medium">{{ s.name }}</span>
                        <span class="text-text-muted">since {{ date.formatDate(s.since) }}</span>
                    </li>
                </ul>
            </section>

            <section class="card-container">
                <h2 class="page-heading-2 flex items-center gap-2"><Layers class="h-4 w-4" /> Programs</h2>
                <p v-if="!activePrograms.length" class="text-sm text-text-muted">Not in any program right now.</p>
                <ul v-else class="space-y-2 text-sm">
                    <li v-for="program in activePrograms" :key="program.id" class="flex justify-between gap-3">
                        <span class="font-medium text-mustBlue dark:text-text-inverted">{{ program.name }}</span>
                        <span class="whitespace-nowrap text-text-muted">since {{ date.formatDate(program.start_date) }}</span>
                    </li>
                </ul>
                <details v-if="pastPrograms.length" class="mt-4 text-sm">
                    <summary class="cursor-pointer text-text-muted">Past programs ({{ pastPrograms.length }})</summary>
                    <ul class="mt-2 space-y-1">
                        <li v-for="program in pastPrograms" :key="program.id" class="flex justify-between gap-3 text-text-muted">
                            <span>{{ program.name }}</span>
                            <span class="whitespace-nowrap">{{ date.formatDate(program.start_date) }} – {{ date.formatDate(program.end_date) }}</span>
                        </li>
                    </ul>
                </details>
            </section>
        </div>

        <!-- Education history -->
        <section v-else-if="activeTab === 'education'" class="card-container">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="page-heading-2 !mb-0 flex items-center gap-2"><GraduationCap class="h-4 w-4" /> Education history</h2>
                    <p class="text-sm text-text-muted">One record per academic year — earlier years are never overwritten.</p>
                </div>
                <button v-if="can('change student status')" type="button" class="btn-outline-sm" @click="showEnrollment = true">
                    Change school / level for {{ currentYear ?? 'this year' }}
                </button>
            </div>
            <p v-if="!enrollments.length" class="py-6 text-center text-sm text-text-muted">No education records yet.</p>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                            <th class="px-3 py-2">Year</th>
                            <th class="px-3 py-2">Level</th>
                            <th class="px-3 py-2">School / university</th>
                            <th class="px-3 py-2">Course</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in enrollments" :key="row.id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                            <td class="px-3 py-2 font-medium text-mustBlue dark:text-text-inverted">
                                {{ row.year ?? '—' }}
                                <span v-if="row.is_current_year" class="ml-1 rounded-full bg-mustGreen/15 px-1.5 py-0.5 text-xs text-yl-green-deep">Current</span>
                            </td>
                            <td class="px-3 py-2">
                                {{ row.level_label ?? '—' }}
                                <span v-if="row.is_repeating" class="ml-1 rounded-full bg-yl-gold/15 px-1.5 py-0.5 text-xs text-amber-800">Repeating</span>
                            </td>
                            <td class="px-3 py-2">{{ row.institution ?? '—' }}</td>
                            <td class="px-3 py-2 text-text-muted">{{ row.course ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Documents & consent -->
        <div v-else-if="activeTab === 'documents'" class="grid gap-4 xl:grid-cols-[3fr_2fr]">
            <DocumentsPanel :student="student" :documents="documents" :document-types="options.documentTypes" />
            <ConsentPanel :key="JSON.stringify(consent)" :student="student" :consent="consent" :documents="documents" />
        </div>

        <!-- Academics & fees -->
        <div v-else-if="activeTab === 'academics'" class="space-y-4">
            <ResultsPanel
                :student="student"
                :results="results"
                :terms="options.terms"
                :result-types="options.resultTypes"
                :drop-threshold="finance.gradeDropThreshold"
            />
            <FeesPanel
                :student="student"
                :fees="fees"
                :totals="feeTotals"
                :finance="finance"
                :terms="options.terms"
                :institutions="options.institutions"
                :methods="options.paymentMethods"
            />
        </div>

        <!-- Support programs -->
        <BasicNeedsPanel v-else-if="activeTab === 'needs'" :student="student" :data="support.basicNeeds" :currency="finance.currency" />
        <MentorshipPanel
            v-else-if="activeTab === 'mentorship'"
            :key="`mentor-${support.mentorship.mentor?.id ?? 'none'}`"
            :student="student"
            :data="support.mentorship"
            :mentors="options.mentors"
            :session-types="options.sessionTypes"
        />
        <HomePanel v-else-if="activeTab === 'home'" :student="student" :data="support.home" :currency="finance.currency" />
        <BusinessPanel v-else-if="activeTab === 'business'" :student="student" :businesses="support.businesses" :funding-types="options.fundingTypes" :currency="finance.currency" />
        <CareersPanel
            v-else-if="activeTab === 'careers'"
            :student="student"
            :data="support.careers"
            :outcome-types="options.outcomeTypes"
        />

        <ChangeStatusModal
            :show="showStatus"
            :student="student"
            :transitions="options.transitions"
            :exit-reasons="options.exitReasons"
            @close="showStatus = false"
        />
        <EnrollmentModal
            :show="showEnrollment"
            :student="student"
            :current-year="currentYear"
            :current="currentEnrollment"
            :latest="latestEnrollment"
            :institutions="options.institutions"
            :levels="options.levels"
            @close="showEnrollment = false"
        />
    </AuthenticatedLayout>
</template>

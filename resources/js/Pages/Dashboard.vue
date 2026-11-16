<script setup>
import { date } from '@/Helpers';
import { formatMoney, formatPercent } from '@/Helpers/money';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Activity, BarChart3, CalendarClock, ClipboardCheck, ClipboardList, GraduationCap, HandCoins, MessageSquareWarning, TrendingDown, UserPlus, Users } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    currency: { type: String, default: 'MWK' },
    students: { type: Object, default: null },
    fees: { type: Object, default: null },
    approvals: { type: Object, default: null },
    gradeDrops: { type: Array, default: null },
    gradeDropThreshold: { type: Number, default: 10 },
    reminders: { type: Object, default: () => ({}) },
    reminderSettings: { type: Object, default: () => ({}) },
    thisMonth: { type: Object, default: null },
    overview: { type: Object, default: null },
});

const maxStatus = computed(() => Math.max(1, ...(props.overview?.byStatus ?? []).map((r) => r.count)));
const maxLevel = computed(() => Math.max(1, ...(props.overview?.byLevel ?? []).map((r) => r.count)));
const totalSpend = computed(() => (props.overview?.spend ?? []).reduce((sum, r) => sum + (r.amount ?? 0), 0));
const thisYear = new Date().getFullYear();
const changeYear = (year) => router.get(route('dashboard'), { year }, { preserveScroll: true, preserveState: true, only: ['overview'] });

const name = computed(() => usePage().props.auth?.user?.name?.split(' ')[0] ?? '');
const pendingApprovals = computed(() => (props.approvals ? props.approvals.payments + props.approvals.scholarships + (props.approvals.repairs ?? 0) : 0));
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <h1 class="page-heading !mb-1">Welcome back{{ name ? `, ${name}` : '' }}</h1>
        <p class="mb-6 text-sm text-text-muted dark:text-text-dark-muted">What needs attention today.</p>

        <!-- Headline numbers -->
        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Link v-if="students" :href="route('students.index', { status: 'active' })" class="card-container !mb-0 flex items-center gap-4 transition hover:shadow-lg">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-mustGreen/10 text-mustGreen"><Users class="h-6 w-6" /></span>
                <span>
                    <span class="block text-2xl font-bold text-mustBlue dark:text-text-inverted">{{ students.active }}</span>
                    <span class="text-sm text-text-muted">Active students</span>
                </span>
            </Link>
            <Link v-if="students" :href="route('students.index', { status: 'applicant' })" class="card-container !mb-0 flex items-center gap-4 transition hover:shadow-lg">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-100 text-sky-700"><UserPlus class="h-6 w-6" /></span>
                <span>
                    <span class="block text-2xl font-bold text-mustBlue dark:text-text-inverted">{{ students.applicants }}</span>
                    <span class="text-sm text-text-muted">Applicants to assess</span>
                </span>
            </Link>
            <Link v-if="fees" :href="route('finance.unpaid.index')" class="card-container !mb-0 flex items-center gap-4 transition hover:shadow-lg">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-100 text-red-700"><HandCoins class="h-6 w-6" /></span>
                <span>
                    <span class="block text-xl font-bold text-mustBlue dark:text-text-inverted">{{ formatMoney(fees.outstanding, currency) }}</span>
                    <span class="text-sm text-text-muted">Unpaid fees · {{ fees.students }} {{ fees.students === 1 ? 'student' : 'students' }}</span>
                </span>
            </Link>
            <Link v-if="approvals" :href="route('finance.approvals.index')" class="card-container !mb-0 flex items-center gap-4 transition hover:shadow-lg">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl" :class="pendingApprovals ? 'bg-amber-100 text-amber-700' : 'bg-mustGreen/10 text-mustGreen'">
                    <ClipboardCheck class="h-6 w-6" />
                </span>
                <span>
                    <span class="block text-2xl font-bold text-mustBlue dark:text-text-inverted">{{ pendingApprovals }}</span>
                    <span class="text-sm text-text-muted">
                        Waiting for your approval
                        <template v-if="pendingApprovals"> · {{ approvals.payments }} payments, {{ approvals.scholarships }} scholarships, {{ approvals.repairs ?? 0 }} repairs</template>
                    </span>
                </span>
            </Link>
        </div>

        <!-- This month -->
        <div v-if="thisMonth" class="mb-6 card-container">
            <h2 class="page-heading-2 !mb-3">This month so far</h2>
            <dl class="grid grid-cols-2 gap-4 text-center sm:grid-cols-4">
                <div><dd class="text-2xl font-bold text-mustBlue dark:text-text-inverted">{{ thisMonth.sessions }}</dd><dt class="text-xs text-text-muted">mentorship sessions</dt></div>
                <div><dd class="text-xl font-bold text-mustBlue dark:text-text-inverted">{{ formatMoney(thisMonth.basicNeeds, currency) }}</dd><dt class="text-xs text-text-muted">basic needs given</dt></div>
                <div><dd class="text-2xl font-bold text-mustBlue dark:text-text-inverted">{{ thisMonth.visits }}</dd><dt class="text-xs text-text-muted">home visits</dt></div>
                <div><dd class="text-2xl font-bold text-mustBlue dark:text-text-inverted">{{ thisMonth.payments }}</dd><dt class="text-xs text-text-muted">fee payments made</dt></div>
            </dl>
        </div>

        <!-- Support reminders -->
        <div class="mb-6 grid gap-4 lg:grid-cols-2">
            <section v-if="reminders.mentorship" class="card-container !mb-0">
                <h2 class="page-heading-2 flex items-center gap-2"><MessageSquareWarning class="h-5 w-5 text-amber-600" /> Mentorship overdue</h2>
                <p class="mb-3 text-sm text-text-muted">Students with a mentor but no session in {{ reminderSettings.mentorshipDays }} days.</p>
                <p v-if="!reminders.mentorship.length" class="text-sm text-text-muted">Everyone is up to date.</p>
                <ul v-else class="divide-y divide-mustBorder-light text-sm dark:divide-mustBorder-dark">
                    <li v-for="r in reminders.mentorship" :key="r.student_id" class="flex flex-wrap justify-between gap-2 py-2">
                        <Link :href="route('students.show', r.student_id) + '#mentorship'" class="font-medium text-mustGreen hover:underline">{{ r.student }}</Link>
                        <span class="text-text-muted">{{ r.mentor }} · last {{ r.last_session ? date.formatDate(r.last_session) : 'never' }}</span>
                    </li>
                </ul>
                <template v-if="reminders.followUps?.length">
                    <p class="mb-1 mt-4 text-xs font-semibold uppercase tracking-wider text-text-muted">Follow-up needed</p>
                    <ul class="divide-y divide-mustBorder-light text-sm dark:divide-mustBorder-dark">
                        <li v-for="r in reminders.followUps" :key="r.student_id" class="flex flex-wrap justify-between gap-2 py-2">
                            <Link :href="route('students.show', r.student_id) + '#mentorship'" class="font-medium text-mustGreen hover:underline">{{ r.student }}</Link>
                            <span class="text-text-muted">session {{ date.formatDate(r.session_date) }}</span>
                        </li>
                    </ul>
                </template>
            </section>

            <section v-if="reminders.visits" class="card-container !mb-0">
                <h2 class="page-heading-2 flex items-center gap-2"><CalendarClock class="h-5 w-5 text-sky-600" /> Home visits due</h2>
                <p class="mb-3 text-sm text-text-muted">Overdue, never visited, or due within two weeks.</p>
                <p v-if="!reminders.visits.length" class="text-sm text-text-muted">No visits due.</p>
                <ul v-else class="divide-y divide-mustBorder-light text-sm dark:divide-mustBorder-dark">
                    <li v-for="h in reminders.visits" :key="h.household_id" class="flex flex-wrap justify-between gap-2 py-2">
                        <Link v-if="h.student_id" :href="route('students.show', h.student_id) + '#home'" class="font-medium text-mustGreen hover:underline">{{ h.household }}</Link>
                        <span :class="h.is_overdue ? 'text-red-700' : 'text-text-muted'">{{ h.next_visit_due ? `due ${date.formatDate(h.next_visit_due)}` : 'never visited' }}</span>
                    </li>
                </ul>
                <Link :href="route('support.families')" class="mt-3 inline-block text-sm text-mustGreen hover:underline">All families</Link>
            </section>

            <section v-if="reminders.checkins" class="card-container !mb-0">
                <h2 class="page-heading-2 flex items-center gap-2"><ClipboardList class="h-5 w-5 text-purple-600" /> Business check-ins due</h2>
                <p class="mb-3 text-sm text-text-muted">Running businesses with no check-in in {{ reminderSettings.checkinDays }} days.</p>
                <p v-if="!reminders.checkins.length" class="text-sm text-text-muted">No check-ins due.</p>
                <ul v-else class="divide-y divide-mustBorder-light text-sm dark:divide-mustBorder-dark">
                    <li v-for="b in reminders.checkins" :key="b.business_id" class="flex flex-wrap justify-between gap-2 py-2">
                        <Link :href="route('students.show', b.student_id) + '#business'" class="font-medium text-mustGreen hover:underline">{{ b.business }} — {{ b.student }}</Link>
                        <span class="text-text-muted">last {{ b.last_checkin ? date.formatDate(b.last_checkin) : 'never' }}</span>
                    </li>
                </ul>
            </section>
        </div>

        <!-- Grade drops -->
        <section v-if="gradeDrops" class="card-container">
            <h2 class="page-heading-2 flex items-center gap-2"><TrendingDown class="h-5 w-5 text-red-600" /> Falling grades</h2>
            <p class="mb-4 text-sm text-text-muted">Students whose latest average fell by {{ gradeDropThreshold }} points or more on the term before.</p>
            <p v-if="!gradeDrops.length" class="flex items-center gap-2 py-4 text-sm text-text-muted">
                <GraduationCap class="h-4 w-4 text-mustGreen" /> No sharp drops in the latest results.
            </p>
            <ul v-else class="divide-y divide-mustBorder-light dark:divide-mustBorder-dark">
                <li v-for="drop in gradeDrops" :key="drop.student_id" class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                    <span>
                        <Link :href="route('students.show', drop.student_id) + '#academics'" class="font-medium text-mustGreen hover:underline">{{ drop.student }}</Link>
                        <span class="text-text-muted"> · {{ drop.term }}</span>
                    </span>
                    <span>
                        {{ formatPercent(drop.from) }} → <strong>{{ formatPercent(drop.to) }}</strong>
                        <span class="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">{{ drop.change }} pts</span>
                    </span>
                </li>
            </ul>
        </section>

        <!-- Organisation overview (Admin) -->
        <template v-if="overview">
            <h2 class="mb-3 mt-8 flex items-center gap-2 text-lg font-bold text-mustBlue dark:text-text-inverted"><BarChart3 class="h-5 w-5" /> Organisation overview</h2>
            <div class="grid gap-4 lg:grid-cols-2">
                <section class="card-container !mb-0">
                    <h3 class="page-heading-2">Students by status</h3>
                    <ul class="space-y-2 text-sm">
                        <li v-for="row in overview.byStatus" :key="row.status" class="grid grid-cols-[7rem_1fr_2.5rem] items-center gap-3">
                            <span class="text-text-muted">{{ row.label }}</span>
                            <span class="h-2.5 rounded-full bg-mustGreen/15"><span class="block h-2.5 rounded-full bg-mustGreen" :style="{ width: `${(row.count / maxStatus) * 100}%` }"></span></span>
                            <span class="text-right font-semibold">{{ row.count }}</span>
                        </li>
                    </ul>
                    <div class="mt-4 grid grid-cols-2 gap-3 border-t border-mustBorder-light pt-4 text-center dark:border-mustBorder-dark">
                        <div><p class="text-2xl font-bold text-mustBlue dark:text-text-inverted">{{ overview.graduates }}</p><p class="text-xs text-text-muted">graduates</p></div>
                        <div><p class="text-2xl font-bold text-mustBlue dark:text-text-inverted">{{ overview.families }}</p><p class="text-xs text-text-muted">families reached</p></div>
                    </div>
                </section>

                <section class="card-container !mb-0">
                    <h3 class="page-heading-2">Active students by level this year</h3>
                    <p v-if="!overview.byLevel.length" class="text-sm text-text-muted">No levels recorded for the current year.</p>
                    <ul v-else class="space-y-2 text-sm">
                        <li v-for="row in overview.byLevel" :key="row.level" class="grid grid-cols-[7rem_1fr_2.5rem] items-center gap-3">
                            <span class="text-text-muted">{{ row.label }}</span>
                            <span class="h-2.5 rounded-full bg-sky-100"><span class="block h-2.5 rounded-full bg-sky-600" :style="{ width: `${(row.count / maxLevel) * 100}%` }"></span></span>
                            <span class="text-right font-semibold">{{ row.count }}</span>
                        </li>
                    </ul>
                </section>

                <section class="card-container !mb-0">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="page-heading-2 !mb-0">Money spent per program</h3>
                        <label class="text-sm text-text-muted">
                            <span class="sr-only">Year</span>
                            <select class="text-input !py-1" :value="overview.year" @change="changeYear($event.target.value)">
                                <option v-for="y in [thisYear, thisYear - 1, thisYear - 2]" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </label>
                    </div>
                    <table class="w-full text-sm">
                        <tbody>
                            <tr v-for="row in overview.spend" :key="row.program" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                                <td class="py-2 pr-3">{{ row.program }}</td>
                                <td class="py-2 text-right" :class="row.amount === null ? 'text-xs text-text-muted' : 'font-semibold'">
                                    {{ row.amount === null ? 'not tracked' : formatMoney(row.amount, currency) }}
                                </td>
                            </tr>
                            <tr class="font-bold"><td class="pt-3">Total</td><td class="pt-3 text-right">{{ formatMoney(totalSpend, currency) }}</td></tr>
                        </tbody>
                    </table>
                    <p class="mt-2 text-xs text-text-muted">Fees paid, basic-needs money, completed repairs and business funding in {{ overview.year }}.</p>
                </section>

                <section class="card-container !mb-0">
                    <h3 class="page-heading-2 flex items-center gap-2"><Activity class="h-4 w-4" /> Recent activity</h3>
                    <p v-if="!overview.activity.length" class="text-sm text-text-muted">Nothing yet.</p>
                    <ul v-else class="divide-y divide-mustBorder-light text-sm dark:divide-mustBorder-dark">
                        <li v-for="a in overview.activity" :key="a.id" class="flex flex-wrap justify-between gap-2 py-2">
                            <span><strong>{{ a.who }}</strong> <span class="text-text-muted">{{ a.what }}</span></span>
                            <span class="text-xs text-text-muted">{{ date.formatDate(a.when) }}</span>
                        </li>
                    </ul>
                    <Link :href="route('admin.activity-logs.index')" class="mt-3 inline-block text-sm text-mustGreen hover:underline">Full audit log</Link>
                </section>
            </div>
        </template>

        <p v-if="!students && !fees && !approvals && !gradeDrops" class="card-container text-sm text-text-muted">
            Use the menu to get started.
        </p>
    </AuthenticatedLayout>
</template>

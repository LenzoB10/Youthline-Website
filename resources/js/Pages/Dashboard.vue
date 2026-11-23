<script setup>
import SupportTrendChart from '@/Components/Dashboard/SupportTrendChart.vue';
import { date } from '@/Helpers';
import { can } from '@/Helpers/can';
import { formatMoney, formatPercent } from '@/Helpers/money';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Activity,
    ArrowDownRight,
    ArrowUpRight,
    CalendarClock,
    CheckCircle2,
    ChevronRight,
    ClipboardCheck,
    ClipboardList,
    FileBarChart,
    HandCoins,
    HandHeart,
    HeartHandshake,
    Home,
    MessageSquareWarning,
    PauseCircle,
    Plus,
    Receipt,
    ShoppingBasket,
    Store,
    TrendingDown,
    UserPlus,
    Users,
} from 'lucide-vue-next';
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
    trend: { type: Array, default: null },
    overview: { type: Object, default: null },
});

const panel = 'surface rounded-2xl p-5';
const money = (v) => formatMoney(v, props.currency);
const name = computed(() => usePage().props.auth?.user?.name?.split(' ')[0] ?? '');
const today = new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
const hour = new Date().getHours();
const greeting = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';

/* ---------- Support this month vs last month (from the 12-month trend) ---------- */
const monthSum = (m) => (m ? (m.fees ?? 0) + (m.basic_needs ?? 0) + (m.family_support ?? 0) + (m.businesses ?? 0) : 0);
const supportNow = computed(() => monthSum(props.trend?.at(-1)));
const supportBefore = computed(() => monthSum(props.trend?.at(-2)));
const supportChange = computed(() => (supportBefore.value ? Math.round(((supportNow.value - supportBefore.value) / supportBefore.value) * 100) : null));

/* ---------- KPI cards: only the ones this person can use, so the row never has gaps ---------- */
const pendingApprovals = computed(() => props.approvals?.scholarships ?? 0);

const kpis = computed(() => {
    const cards = [];
    if (props.students) {
        cards.push({
            key: 'active',
            label: 'Active students',
            value: props.students.active,
            icon: Users,
            tone: 'bg-mustGreen/10 text-mustGreen',
            href: route('students.index', { status: 'active' }),
            foot: `${props.students.applicants} ${props.students.applicants === 1 ? 'applicant' : 'applicants'} waiting to be assessed`,
        });
    }
    if (props.fees) {
        cards.push({
            key: 'fees',
            label: 'Unpaid fees',
            value: money(props.fees.outstanding),
            icon: HandCoins,
            tone: 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300',
            href: route('finance.unpaid.index'),
            foot: `${props.fees.students} ${props.fees.students === 1 ? 'student' : 'students'} · ${props.fees.schools} ${props.fees.schools === 1 ? 'school' : 'schools'}`,
        });
    }
    if (props.trend) {
        cards.push({
            key: 'support',
            label: 'Support given this month',
            value: money(supportNow.value),
            icon: HandHeart,
            tone: 'bg-yl-gold/15 text-amber-700 dark:text-yl-gold',
            href: null,
            change: supportChange.value,
            foot: supportChange.value === null ? 'Nothing recorded last month' : 'vs last month',
        });
    } else if (props.students) {
        cards.push({ key: 'applicants', label: 'Applicants', value: props.students.applicants, icon: UserPlus, tone: 'bg-yl-gold/15 text-amber-700 dark:text-yl-gold', href: route('students.index', { status: 'applicant' }), foot: 'Waiting to be assessed' });
    }
    if (props.approvals) {
        cards.push({
            key: 'approvals',
            label: 'Waiting for approval',
            value: pendingApprovals.value,
            icon: ClipboardCheck,
            tone: pendingApprovals.value ? 'bg-yl-gold/15 text-amber-700 dark:text-yl-gold' : 'bg-mustGreen/10 text-mustGreen',
            href: route('finance.approvals.index'),
            foot: pendingApprovals.value ? `${pendingApprovals.value} ${pendingApprovals.value === 1 ? 'scholarship' : 'scholarships'} to decide` : 'All decisions made',
        });
    } else if (props.students) {
        cards.push({ key: 'paused', label: 'Paused students', value: props.students.paused, icon: PauseCircle, tone: 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-text-dark-muted', href: route('students.index', { status: 'paused' }), foot: 'Support on hold' });
    }
    return cards;
});

const kpiCols = computed(() => ({ 1: 'xl:grid-cols-1', 2: 'xl:grid-cols-2', 3: 'xl:grid-cols-3', 4: 'xl:grid-cols-4' })[kpis.value.length] ?? 'xl:grid-cols-4');

/* ---------- Needs attention: one to-do list, empty groups are hidden ---------- */
const lastSeen = (d) => (d ? date.formatDate(d) : 'never');

const attention = computed(() => {
    const r = props.reminders ?? {};
    return [
        props.gradeDrops && {
            key: 'grades',
            title: 'Falling grades',
            icon: TrendingDown,
            tone: 'bg-gray-700 text-white dark:bg-gray-300 dark:text-gray-900',
            items: props.gradeDrops.map((d) => ({ id: d.student_id, name: d.student, href: route('students.show', d.student_id) + '#academics', meta: `${formatPercent(d.from)} → ${formatPercent(d.to)} · ${d.term}`, badge: `${d.change} pts` })),
        },
        r.mentorship && {
            key: 'mentorship',
            title: 'Mentorship overdue',
            icon: MessageSquareWarning,
            tone: 'bg-yl-gold/15 text-amber-700 dark:text-yl-gold',
            items: r.mentorship.map((m) => ({ id: m.student_id, name: m.student, href: route('students.show', m.student_id) + '#mentorship', meta: `${m.mentor} · last ${lastSeen(m.last_session)}` })),
        },
        r.followUps && {
            key: 'followUps',
            title: 'Mentorship follow-ups',
            icon: MessageSquareWarning,
            tone: 'bg-yl-gold/15 text-amber-700 dark:text-yl-gold',
            items: r.followUps.map((f) => ({ id: f.student_id, name: f.student, href: route('students.show', f.student_id) + '#mentorship', meta: `Session on ${date.formatDate(f.session_date)}` })),
        },
        r.visits && {
            key: 'visits',
            title: 'Home visits due',
            icon: CalendarClock,
            tone: 'bg-mustGreen/10 text-mustGreen',
            items: r.visits.map((h) => ({ id: h.household_id, name: h.household, href: route('support.families.show', h.household_id), meta: h.next_visit_due ? `Due ${date.formatDate(h.next_visit_due)}` : 'Never visited', badge: h.is_overdue ? 'Overdue' : null })),
        },
        r.checkins && {
            key: 'checkins',
            title: 'Business check-ins',
            icon: ClipboardList,
            tone: 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-text-dark-muted',
            items: r.checkins.map((b) => ({ id: b.business_id, name: `${b.business} — ${b.student}`, href: route('students.show', b.student_id) + '#business', meta: `Last check-in ${lastSeen(b.last_checkin)}` })),
        },
    ].filter((g) => g && g.items.length);
});

const tracksAttention = computed(() => Boolean(props.gradeDrops || Object.values(props.reminders ?? {}).some((v) => v !== null)));
const attentionTotal = computed(() => attention.value.reduce((sum, g) => sum + g.items.length, 0));

/* ---------- This month & shortcuts ---------- */
const monthStats = computed(() =>
    props.thisMonth
        ? [
              { label: 'Mentorship sessions', value: props.thisMonth.sessions, icon: MessageSquareWarning },
              { label: 'Basic needs given', value: money(props.thisMonth.basicNeeds), icon: ShoppingBasket },
              { label: 'Home visits', value: props.thisMonth.visits, icon: Home },
              { label: 'Fee payments', value: props.thisMonth.payments, icon: Receipt },
          ]
        : [],
);

const shortcuts = computed(() =>
    [
        can('add student') && { label: 'Add a student', href: route('students.create'), icon: Plus },
        can('manage fees') && { label: 'Unpaid fees', href: route('finance.unpaid.index'), icon: HandCoins },
        can('manage basic needs') && { label: 'Basic needs', href: route('support.basic-needs.index'), icon: ShoppingBasket },
        can('manage home visits') && { label: 'Families', href: route('support.families'), icon: Home },
        can('manage businesses') && { label: 'Businesses', href: route('support.businesses'), icon: Store },
        can('manage sponsors') && { label: 'Sponsors', href: route('sponsors.index'), icon: HeartHandshake },
        can('export reports') && { label: 'Reports', href: route('reports.index'), icon: FileBarChart },
    ].filter(Boolean),
);

/* ---------- Organisation overview (Admin) ---------- */
const maxStatus = computed(() => Math.max(1, ...(props.overview?.byStatus ?? []).map((r) => r.count)));
const maxLevel = computed(() => Math.max(1, ...(props.overview?.byLevel ?? []).map((r) => r.count)));
const tracked = computed(() => (props.overview?.spend ?? []).filter((r) => r.amount !== null));
const maxSpend = computed(() => Math.max(1, ...tracked.value.map((r) => r.amount)));
const totalSpend = computed(() => tracked.value.reduce((sum, r) => sum + r.amount, 0));
const totalStudents = computed(() => (props.overview?.byStatus ?? []).reduce((sum, r) => sum + r.count, 0));
const thisYear = new Date().getFullYear();
const changeYear = (year) => router.get(route('dashboard'), { year }, { preserveScroll: true, preserveState: true, only: ['overview'] });
const initials = (who) => (who ?? '?').split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase();

const nothingToShow = computed(() => !kpis.value.length && !tracksAttention.value && !props.trend && !props.overview);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <!-- Greeting -->
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-text-muted dark:text-text-dark-muted">{{ today }}</p>
                <h1 class="mt-1 text-2xl font-bold text-mustBlue dark:text-text-inverted">{{ greeting }}{{ name ? `, ${name}` : '' }} 👋</h1>
                <p class="text-sm text-text-muted dark:text-text-dark-muted">Here's how Youthline is doing today.</p>
            </div>
            <Link v-if="can('add student')" :href="route('students.create')" class="btn-primary inline-flex items-center gap-1"><Plus class="h-4 w-4" /> Add student</Link>
        </div>

        <!-- KPI cards -->
        <div v-if="kpis.length" class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2" :class="kpiCols">
            <component
                :is="card.href ? Link : 'div'"
                v-for="card in kpis"
                :key="card.key"
                :href="card.href ?? undefined"
                :class="[panel, card.href ? 'transition hover:-translate-y-0.5 hover:shadow-md' : '']"
            >
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-medium text-text-muted dark:text-text-dark-muted">{{ card.label }}</p>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="card.tone"><component :is="card.icon" class="h-5 w-5" /></span>
                </div>
                <p class="mt-1 text-2xl font-bold tracking-tight text-mustBlue dark:text-text-inverted">{{ card.value }}</p>
                <p class="mt-2 flex items-center gap-1.5 text-xs text-text-muted dark:text-text-dark-muted">
                    <span
                        v-if="card.change !== undefined && card.change !== null"
                        class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 font-semibold"
                        :class="card.change >= 0 ? 'bg-mustGreen/15 text-yl-green-deep dark:text-accent-light' : 'bg-gray-700 text-white dark:bg-gray-300 dark:text-gray-900'"
                    >
                        <component :is="card.change >= 0 ? ArrowUpRight : ArrowDownRight" class="h-3 w-3" />{{ Math.abs(card.change) }}%
                    </span>
                    {{ card.foot }}
                </p>
            </component>
        </div>

        <!-- Trend + needs attention -->
        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section v-if="trend" :class="[panel, tracksAttention ? 'lg:col-span-2' : 'lg:col-span-3']">
                <div class="mb-1">
                    <h2 class="text-base font-semibold text-mustBlue dark:text-text-inverted">Support given</h2>
                    <p class="text-xs text-text-muted dark:text-text-dark-muted">Money paid out each month over the last 12 months</p>
                </div>
                <SupportTrendChart class="mt-4" :months="trend" :currency="currency" />

                <dl v-if="monthStats.length" class="mt-5 grid grid-cols-2 gap-3 border-t border-mustBorder-light pt-4 sm:grid-cols-4 dark:border-white/5">
                    <div v-for="s in monthStats" :key="s.label" class="rounded-xl bg-gray-50 px-3 py-2.5 ring-1 ring-gray-100 dark:bg-white/5 dark:ring-white/5">
                        <dt class="flex items-center gap-1.5 text-xs text-text-muted dark:text-text-dark-muted"><component :is="s.icon" class="h-3.5 w-3.5" /> {{ s.label }}</dt>
                        <dd class="mt-0.5 text-lg font-bold text-mustBlue dark:text-text-inverted">{{ s.value }}</dd>
                    </div>
                </dl>
                <p v-if="monthStats.length" class="mt-2 text-xs text-text-muted dark:text-text-dark-muted">Recorded so far this month.</p>
            </section>

            <section v-if="tracksAttention" :class="[panel, 'flex flex-col', trend ? '' : 'lg:col-span-3']">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-mustBlue dark:text-text-inverted">Needs attention</h2>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="attentionTotal ? 'bg-yl-gold/15 text-amber-800 dark:text-yl-gold' : 'bg-mustGreen/15 text-yl-green-deep dark:text-accent-light'">
                        {{ attentionTotal || 'All clear' }}
                    </span>
                </div>

                <div v-if="!attention.length" class="flex flex-1 flex-col items-center justify-center gap-2 py-10 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-mustGreen/10"><CheckCircle2 class="h-7 w-7 text-mustGreen" /></span>
                    <p class="font-medium text-mustBlue dark:text-text-inverted">You're all caught up</p>
                    <p class="text-sm text-text-muted">Nothing is overdue right now.</p>
                </div>

                <div v-else class="-mx-2 max-h-[30rem] flex-1 space-y-4 overflow-y-auto px-2">
                    <div v-for="group in attention" :key="group.key">
                        <p class="mb-1.5 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-text-muted dark:text-text-dark-muted">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg" :class="group.tone"><component :is="group.icon" class="h-3.5 w-3.5" /></span>
                            {{ group.title }} · {{ group.items.length }}
                        </p>
                        <ul class="space-y-1">
                            <li v-for="item in group.items" :key="`${group.key}-${item.id}`">
                                <Link :href="item.href" class="group flex items-center justify-between gap-3 rounded-xl px-2.5 py-2 text-sm transition hover:bg-gray-50 dark:hover:bg-white/5">
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-mustBlue dark:text-text-inverted">{{ item.name }}</span>
                                        <span class="block truncate text-xs text-text-muted dark:text-text-dark-muted">{{ item.meta }}</span>
                                    </span>
                                    <span class="flex shrink-0 items-center gap-1.5">
                                        <span v-if="item.badge" class="rounded-full px-2 py-0.5 text-xs font-medium bg-gray-700 text-white dark:bg-gray-300 dark:text-gray-900">{{ item.badge }}</span>
                                        <ChevronRight class="h-4 w-4 text-text-muted transition group-hover:translate-x-0.5" />
                                    </span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>

        <!-- Organisation overview (Admin) -->
        <div v-if="overview" class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section :class="panel">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-mustBlue dark:text-text-inverted">Students</h2>
                    <span class="text-xs text-text-muted dark:text-text-dark-muted">{{ totalStudents }} in total</span>
                </div>
                <div class="mb-5 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-mustGreen/10 px-3 py-2.5">
                        <p class="text-xl font-bold text-mustBlue dark:text-text-inverted">{{ overview.graduates }}</p>
                        <p class="text-xs text-text-muted dark:text-text-dark-muted">Graduates</p>
                    </div>
                    <div class="rounded-xl bg-yl-gold/15 px-3 py-2.5">
                        <p class="text-xl font-bold text-mustBlue dark:text-text-inverted">{{ overview.families }}</p>
                        <p class="text-xs text-text-muted dark:text-text-dark-muted">Families reached</p>
                    </div>
                </div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-text-muted dark:text-text-dark-muted">By status</p>
                <ul class="space-y-2.5 text-sm">
                    <li v-for="row in overview.byStatus" :key="row.status" class="grid grid-cols-[6.5rem_1fr_2rem] items-center gap-3">
                        <span class="text-text-muted dark:text-text-dark-muted">{{ row.label }}</span>
                        <span class="h-2 rounded-full bg-mustGreen/15"><span class="block h-2 rounded-full bg-mustGreen" :style="{ width: `${(row.count / maxStatus) * 100}%` }"></span></span>
                        <span class="text-right font-semibold text-mustBlue dark:text-text-inverted">{{ row.count }}</span>
                    </li>
                </ul>
                <template v-if="overview.byLevel.length">
                    <p class="mb-2 mt-5 text-xs font-semibold uppercase tracking-wider text-text-muted dark:text-text-dark-muted">Active, by level ({{ thisYear }})</p>
                    <ul class="space-y-2.5 text-sm">
                        <li v-for="row in overview.byLevel" :key="row.level" class="grid grid-cols-[6.5rem_1fr_2rem] items-center gap-3">
                            <span class="text-text-muted dark:text-text-dark-muted">{{ row.label }}</span>
                            <span class="h-2 rounded-full bg-yl-gold/15"><span class="block h-2 rounded-full bg-yl-gold" :style="{ width: `${(row.count / maxLevel) * 100}%` }"></span></span>
                            <span class="text-right font-semibold text-mustBlue dark:text-text-inverted">{{ row.count }}</span>
                        </li>
                    </ul>
                </template>
                <p v-else class="mt-5 text-xs text-text-muted dark:text-text-dark-muted">
                    No levels recorded for {{ thisYear }} yet.
                    <Link v-if="can('change student status')" :href="route('students.promote.index')" class="text-mustGreen hover:underline">Move students into this year</Link>
                </p>
            </section>

            <section :class="panel">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-mustBlue dark:text-text-inverted">Spending by program</h2>
                    <label>
                        <span class="sr-only">Year</span>
                        <select class="text-input !py-1.5 font-medium" aria-label="Year" :value="overview.year" @change="changeYear($event.target.value)">
                            <option v-for="y in [thisYear, thisYear - 1, thisYear - 2]" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </label>
                </div>
                <p class="text-xs text-text-muted dark:text-text-dark-muted">Total in {{ overview.year }}</p>
                <p class="mb-4 text-2xl font-bold tracking-tight text-mustBlue dark:text-text-inverted">{{ money(totalSpend) }}</p>
                <ul class="space-y-3.5 text-sm">
                    <li v-for="row in tracked" :key="row.program">
                        <div class="mb-1 flex justify-between gap-3">
                            <span class="min-w-0 text-text-muted sm:truncate dark:text-text-dark-muted">{{ row.program }}</span>
                            <span class="shrink-0 font-semibold text-mustBlue dark:text-text-inverted">{{ money(row.amount) }}</span>
                        </div>
                        <span class="block h-2 rounded-full bg-mustGreen/15 dark:bg-white/10">
                            <span class="block h-2 rounded-full bg-mustGreen dark:bg-accent-light" :style="{ width: `${(row.amount / maxSpend) * 100}%` }"></span>
                        </span>
                    </li>
                </ul>
                <p class="mt-4 text-xs text-text-muted dark:text-text-dark-muted">Mentorship and career guidance have no direct spending, so they are not shown.</p>
            </section>

            <section :class="panel">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="flex items-center gap-2 text-base font-semibold text-mustBlue dark:text-text-inverted"><Activity class="h-4 w-4" /> Recent activity</h2>
                    <Link :href="route('admin.activity-logs.index')" class="text-xs font-medium text-mustGreen hover:underline">View all</Link>
                </div>
                <p v-if="!overview.activity.length" class="text-sm text-text-muted">Nothing yet.</p>
                <ol v-else class="relative space-y-4 border-l border-mustBorder-light pl-5 dark:border-white/10">
                    <li v-for="a in overview.activity.slice(0, 7)" :key="a.id" class="relative text-sm">
                        <span class="absolute -left-[33px] flex h-6 w-6 items-center justify-center rounded-full bg-mustGreen text-[10px] font-bold text-white ring-4 ring-white dark:bg-mustGreen dark:ring-black/40">
                            {{ initials(a.who) }}
                        </span>
                        <p><span class="font-semibold text-mustBlue dark:text-text-inverted">{{ a.who }}</span> <span class="text-text-muted dark:text-text-dark-muted">{{ a.what.toLowerCase() }}</span></p>
                        <p class="text-xs text-text-muted dark:text-text-dark-muted">{{ date.formatDate(a.when) }}</p>
                    </li>
                </ol>
            </section>
        </div>

        <!-- Shortcuts -->
        <section v-if="shortcuts.length" :class="panel">
            <h2 class="mb-4 text-base font-semibold text-mustBlue dark:text-text-inverted">Quick actions</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-7">
                <Link
                    v-for="s in shortcuts"
                    :key="s.label"
                    :href="s.href"
                    class="group flex flex-col items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-4 text-center text-sm font-medium text-mustBlue transition hover:-translate-y-0.5 hover:border-mustGreen hover:shadow-sm dark:border-white/10 dark:text-text-inverted"
                >
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-mustGreen/10 text-mustGreen transition group-hover:bg-mustGreen group-hover:text-white"><component :is="s.icon" class="h-5 w-5" /></span>
                    {{ s.label }}
                </Link>
            </div>
        </section>

        <p v-if="nothingToShow" :class="panel" class="text-sm text-text-muted">Use the menu to get started.</p>
    </AuthenticatedLayout>
</template>

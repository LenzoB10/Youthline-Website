<script setup>
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { date } from '@/Helpers';
import { formatMoney } from '@/Helpers/money';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ShoppingBasket, Users } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    year: { type: Number, required: true },
    years: { type: Array, default: () => [] },
    currency: { type: String, default: 'MWK' },
    totals: { type: Object, required: true },
    levels: { type: Array, default: () => [] },
    programs: { type: Array, default: () => [] },
    students: { type: Array, default: () => [] },
    recent: { type: Array, default: () => [] },
});

const today = new Date().toISOString().slice(0, 10);
const money = (v) => formatMoney(v, props.currency);
const monthLabel = (ym) => new Date(`${ym}-01T00:00:00`).toLocaleDateString(undefined, { month: 'long' });
const maxMonth = computed(() => Math.max(1, ...props.totals.months.map((m) => m.total)));

const selectedYear = ref(props.year);
watch(selectedYear, (y) => router.get(route('support.basic-needs.index'), { year: y }, { preserveScroll: true, preserveState: true }));
const yearOptions = computed(() => props.years.map((y) => ({ value: y, label: String(y) })));

// --- Group handout ---
const filterLevel = ref('');
const filterProgram = ref('');
const levelOptions = computed(() => [{ value: '', label: 'All levels' }, ...props.levels]);
const programOptions = computed(() => [{ value: '', label: 'All programs' }, ...props.programs]);
const visible = computed(() =>
    props.students.filter(
        (s) => (!filterLevel.value || s.level === filterLevel.value) && (!filterProgram.value || s.program_ids.includes(Number(filterProgram.value))),
    ),
);

const form = useForm({ given_on: today, amount: '', note: '', student_ids: [] });
const selected = computed(() => new Set(form.student_ids));
const toggle = (id) => {
    form.student_ids = selected.value.has(id) ? form.student_ids.filter((x) => x !== id) : [...form.student_ids, id];
};
const allVisibleSelected = computed(() => visible.value.length > 0 && visible.value.every((s) => selected.value.has(s.id)));
const toggleAll = () => {
    const ids = visible.value.map((s) => s.id);
    form.student_ids = allVisibleSelected.value
        ? form.student_ids.filter((id) => !ids.includes(id))
        : [...new Set([...form.student_ids, ...ids])];
};
const groupTotal = computed(() => (Number(form.amount) || 0) * form.student_ids.length);
const submit = () => form.post(route('support.basic-needs.group'), { preserveScroll: true, onSuccess: () => form.reset('student_ids', 'note') });
</script>

<template>
    <Head title="Basic needs" />

    <AuthenticatedLayout>
        <h1 class="page-heading !mb-1">Basic needs</h1>
        <p class="mb-6 text-sm text-text-muted dark:text-text-dark-muted">Money given to students for groceries and other basic needs. Record one student on their profile, or many at once below.</p>

        <!-- Totals -->
        <section class="card-container mb-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 class="page-heading-2 !mb-0 flex items-center gap-2"><ShoppingBasket class="h-4 w-4" /> Given in {{ year }}</h2>
                <div class="w-36">
                    <label for="bn_year" class="sr-only">Year</label>
                    <SelectInput id="bn_year" v-model="selectedYear" :options="yearOptions" />
                </div>
            </div>
            <dl class="mb-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-mustGreen/5 p-3">
                    <dt class="text-xs uppercase tracking-wider text-text-muted">Total given</dt>
                    <dd class="text-xl font-bold text-mustGreen">{{ money(totals.total) }}</dd>
                </div>
                <div class="rounded-lg bg-mustGreen/5 p-3">
                    <dt class="text-xs uppercase tracking-wider text-text-muted">Students supported</dt>
                    <dd class="text-xl font-bold text-mustBlue dark:text-text-inverted">{{ totals.students }}</dd>
                </div>
                <div class="rounded-lg bg-mustGreen/5 p-3">
                    <dt class="text-xs uppercase tracking-wider text-text-muted">Times given</dt>
                    <dd class="text-xl font-bold text-mustBlue dark:text-text-inverted">{{ totals.records }}</dd>
                </div>
            </dl>
            <p v-if="!totals.months.length" class="text-sm text-text-muted">Nothing given in {{ year }} yet.</p>
            <ul v-else class="space-y-2 text-sm">
                <li v-for="m in totals.months" :key="m.month" class="grid grid-cols-[6rem_1fr_9rem] items-center gap-3">
                    <span class="text-text-muted">{{ monthLabel(m.month) }}</span>
                    <span class="h-2.5 rounded-full bg-mustGreen/15"><span class="block h-2.5 rounded-full bg-mustGreen" :style="{ width: `${(m.total / maxMonth) * 100}%` }"></span></span>
                    <span class="text-right"><strong>{{ money(m.total) }}</strong> <span class="text-xs text-text-muted">· {{ m.students }}</span></span>
                </li>
            </ul>
        </section>

        <!-- Group handout -->
        <form class="card-container mb-6" @submit.prevent="submit">
            <h2 class="page-heading-2 flex items-center gap-2"><Users class="h-4 w-4" /> Group handout</h2>
            <p class="mb-4 text-sm text-text-muted">Give the same amount to many students at once — for example grocery money for everyone this month. Each student gets their own record.</p>

            <div class="mb-4 grid gap-3 sm:grid-cols-[12rem_12rem_1fr]">
                <div>
                    <InputLabel for="g_date" value="Date" />
                    <TextInput id="g_date" v-model="form.given_on" type="date" :max="today" class="mt-1 block w-full" required />
                    <InputError class="mt-1" :message="form.errors.given_on" />
                </div>
                <div>
                    <InputLabel for="g_amount" :value="`Amount each (${currency})`" />
                    <TextInput id="g_amount" v-model="form.amount" type="number" min="0.01" step="0.01" placeholder="e.g. 20000" class="mt-1 block w-full" required />
                    <InputError class="mt-1" :message="form.errors.amount" />
                </div>
                <div>
                    <InputLabel for="g_note" value="Note (optional)" />
                    <TextInput id="g_note" v-model="form.note" placeholder="e.g. November groceries" class="mt-1 block w-full" />
                    <InputError class="mt-1" :message="form.errors.note" />
                </div>
            </div>

            <div class="mb-3 flex flex-wrap items-end gap-3 border-t border-mustBorder-light pt-4 dark:border-mustBorder-dark">
                <div class="w-48">
                    <InputLabel for="g_level" value="Filter by level" />
                    <SelectInput id="g_level" v-model="filterLevel" :options="levelOptions" />
                </div>
                <div class="w-64">
                    <InputLabel for="g_program" value="Filter by program" />
                    <SelectInput id="g_program" v-model="filterProgram" :options="programOptions" />
                </div>
                <p class="ml-auto text-sm text-text-muted"><strong class="text-mustBlue dark:text-text-inverted">{{ form.student_ids.length }}</strong> selected</p>
            </div>

            <div class="max-h-96 overflow-y-auto rounded-lg border border-mustBorder-light dark:border-mustBorder-dark">
                <label class="sticky top-0 flex items-center gap-3 border-b border-mustBorder-light bg-background-card px-3 py-2 text-sm font-medium dark:border-mustBorder-dark dark:bg-background-dark-card">
                    <Checkbox :model-value="allVisibleSelected" @update:model-value="toggleAll" />
                    Select all {{ visible.length }} shown (active students)
                </label>
                <p v-if="!visible.length" class="px-3 py-6 text-center text-sm text-text-muted">No active students match these filters.</p>
                <label v-for="s in visible" :key="s.id" class="flex cursor-pointer items-center gap-3 px-3 py-1.5 text-sm hover:bg-mustGreen/5">
                    <Checkbox :model-value="selected.has(s.id)" @update:model-value="toggle(s.id)" />
                    <span class="flex-1">{{ s.name }}</span>
                    <span class="text-xs text-text-muted">{{ s.level_label ?? 'No level this year' }}</span>
                </label>
            </div>
            <InputError class="mt-2" :message="form.errors.student_ids" />

            <div class="mt-4 flex flex-wrap items-center justify-end gap-3">
                <span v-if="groupTotal" class="text-sm text-text-muted">Total: <strong class="text-mustBlue dark:text-text-inverted">{{ money(groupTotal) }}</strong></span>
                <button type="submit" class="btn-primary" :disabled="form.processing || !form.student_ids.length">
                    Record for {{ form.student_ids.length }} {{ form.student_ids.length === 1 ? 'student' : 'students' }}
                </button>
            </div>
        </form>

        <!-- Recent -->
        <section class="card-container">
            <h2 class="page-heading-2">Recently recorded</h2>
            <p v-if="!recent.length" class="text-sm text-text-muted">Nothing yet.</p>
            <ul v-else class="divide-y divide-mustBorder-light text-sm dark:divide-mustBorder-dark">
                <li v-for="r in recent" :key="r.id" class="flex flex-wrap gap-x-3 py-2">
                    <span class="font-medium">{{ r.what }}</span>
                    <Link :href="route('students.show', r.student_id) + '#needs'" class="text-mustGreen hover:underline">{{ r.student }}</Link>
                    <span class="text-text-muted">{{ date.formatDate(r.given_on) }}<template v-if="r.note"> · {{ r.note }}</template> · {{ r.recorded_by ?? '—' }}</span>
                </li>
            </ul>
        </section>
    </AuthenticatedLayout>
</template>

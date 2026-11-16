<script setup>
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { date } from '@/Helpers';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

const props = defineProps({
    settings: { type: Object, required: true },
});

const form = useForm({
    currency: props.settings.currency,
    require_payment_approval: props.settings.require_payment_approval,
    payment_approval_limit: props.settings.payment_approval_limit,
    require_scholarship_approval: props.settings.require_scholarship_approval,
    require_repair_approval: props.settings.require_repair_approval,
    repair_approval_limit: props.settings.repair_approval_limit,
    counselling_notes_visibility: props.settings.counselling_notes_visibility,
    grade_drop_threshold: props.settings.grade_drop_threshold,
    mentorship_overdue_days: props.settings.mentorship_overdue_days,
    home_visit_interval_months: props.settings.home_visit_interval_months,
    business_checkin_days: props.settings.business_checkin_days,
});

const submit = () =>
    form
        .transform((data) => ({ ...data, currency: String(data.currency).toUpperCase() }))
        .put(route('admin.settings.approvals.update'), { preserveScroll: true });

const visibilityOptions = [
    {
        value: 'author',
        label: 'Only the manager who wrote the note',
        help: 'Most private. Recommended — protects student trust.',
    },
    {
        value: 'all_managers',
        label: 'All managers',
        help: 'Better continuity when a student changes manager, but less private.',
    },
];
</script>

<template>
    <Head title="Approvals & Policies" />

    <AuthenticatedLayout>
        <Link :href="route('admin.settings.index')" class="mb-4 inline-flex items-center gap-1 text-sm text-mustBlue dark:text-text-dark-muted">
            <ArrowLeft class="h-4 w-4" /> Settings
        </Link>
        <h1 class="page-heading">Approvals & Policies</h1>
        <p class="mb-6 text-sm text-text-muted dark:text-text-dark-muted">
            Decide what needs Admin sign-off. These rules apply to payments, scholarships and repairs
            recorded by managers.
            <span v-if="settings.updated_by">
                Last changed by {{ settings.updated_by }} on {{ date.formatDate(settings.updated_at) }}.
            </span>
        </p>

        <form class="max-w-3xl space-y-6" @submit.prevent="submit">
            <!-- Payments -->
            <section class="card-container">
                <h2 class="page-heading-2 !mb-1">Payments</h2>
                <p class="mb-4 text-sm text-text-muted">
                    Payments above the limit start as <em>pending approval</em> until an Admin approves them.
                </p>
                <label class="mb-4 flex items-center gap-2 text-sm">
                    <Checkbox v-model="form.require_payment_approval" />
                    Require Admin approval for large payments
                </label>
                <div class="grid gap-4 sm:grid-cols-[8rem_1fr]">
                    <div>
                        <InputLabel for="currency" value="Currency" />
                        <TextInput id="currency" v-model="form.currency" maxlength="3" class="mt-1 block w-full uppercase" required />
                        <InputError class="mt-2" :message="form.errors.currency" />
                    </div>
                    <div>
                        <InputLabel for="payment_limit" value="Approval needed above" />
                        <TextInput
                            id="payment_limit"
                            v-model="form.payment_approval_limit"
                            type="number"
                            min="0"
                            step="0.01"
                            class="mt-1 block w-full"
                            :disabled="!form.require_payment_approval"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.payment_approval_limit" />
                    </div>
                </div>
            </section>

            <!-- Scholarships & repairs -->
            <section class="card-container">
                <h2 class="page-heading-2 !mb-1">Scholarships and home repairs</h2>
                <label class="mt-3 flex items-start gap-2 text-sm">
                    <Checkbox v-model="form.require_scholarship_approval" class="mt-0.5" />
                    <span>
                        Require Admin approval for every new scholarship
                        <span class="block text-text-muted">A new scholarship commits funds for years, so a second check is advised.</span>
                    </span>
                </label>
                <label class="mt-4 flex items-start gap-2 text-sm">
                    <Checkbox v-model="form.require_repair_approval" class="mt-0.5" />
                    <span>Require Admin approval for large repair budgets</span>
                </label>
                <div class="mt-3 max-w-xs">
                    <InputLabel for="repair_limit" :value="`Approval needed above (${form.currency})`" />
                    <TextInput
                        id="repair_limit"
                        v-model="form.repair_approval_limit"
                        type="number"
                        min="0"
                        step="0.01"
                        class="mt-1 block w-full"
                        :disabled="!form.require_repair_approval"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.repair_approval_limit" />
                </div>
            </section>

            <!-- Academic alerts -->
            <section class="card-container">
                <h2 class="page-heading-2 !mb-1">Grade-drop alerts</h2>
                <p class="mb-4 text-sm text-text-muted">
                    When a student's term average falls by at least this many points on the previous term, everyone who
                    records results gets an alert and the student shows on the dashboard.
                </p>
                <div class="max-w-xs">
                    <InputLabel for="grade_drop_threshold" value="Alert when the average drops by (points)" />
                    <TextInput
                        id="grade_drop_threshold"
                        v-model="form.grade_drop_threshold"
                        type="number"
                        min="1"
                        max="100"
                        step="0.5"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.grade_drop_threshold" />
                </div>
            </section>

            <!-- Support reminders -->
            <section class="card-container">
                <h2 class="page-heading-2 !mb-1">Support reminders</h2>
                <p class="mb-4 text-sm text-text-muted">When students and families show up as due on the dashboard.</p>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <InputLabel for="mentorship_overdue_days" value="Mentorship overdue after (days)" />
                        <TextInput id="mentorship_overdue_days" v-model="form.mentorship_overdue_days" type="number" min="7" max="365" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.mentorship_overdue_days" />
                    </div>
                    <div>
                        <InputLabel for="home_visit_interval_months" value="Home visit every (months)" />
                        <TextInput id="home_visit_interval_months" v-model="form.home_visit_interval_months" type="number" min="1" max="36" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.home_visit_interval_months" />
                    </div>
                    <div>
                        <InputLabel for="business_checkin_days" value="Business check-in every (days)" />
                        <TextInput id="business_checkin_days" v-model="form.business_checkin_days" type="number" min="7" max="365" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.business_checkin_days" />
                    </div>
                </div>
            </section>

            <!-- Counselling notes -->
            <section class="card-container">
                <h2 class="page-heading-2 !mb-1">Counselling notes</h2>
                <p class="mb-4 text-sm text-text-muted">Who can read the private notes from mentorship and counselling sessions.</p>
                <fieldset class="space-y-3">
                    <legend class="sr-only">Counselling note visibility</legend>
                    <label
                        v-for="option in visibilityOptions"
                        :key="option.value"
                        class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm transition"
                        :class="form.counselling_notes_visibility === option.value ? 'border-mustGreen bg-mustGreen/5' : 'border-mustBorder-light dark:border-mustBorder-dark'"
                    >
                        <input
                            v-model="form.counselling_notes_visibility"
                            type="radio"
                            name="counselling_notes_visibility"
                            :value="option.value"
                            class="mt-0.5 text-mustGreen focus:ring-mustGreen"
                        />
                        <span>
                            <span class="font-medium text-mustBlue dark:text-text-inverted">{{ option.label }}</span>
                            <span class="block text-text-muted">{{ option.help }}</span>
                        </span>
                    </label>
                </fieldset>
                <InputError class="mt-2" :message="form.errors.counselling_notes_visibility" />
            </section>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary" :disabled="form.processing">Save settings</button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

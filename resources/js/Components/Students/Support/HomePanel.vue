<script setup>
import { date } from '@/Helpers';
import { can } from '@/Helpers/can';
import { formatMoney } from '@/Helpers/money';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, HandHeart, Home } from 'lucide-vue-next';

// A summary of the student's family. Visits and support are recorded on the
// family's own page (Support → Families), because families are supported in
// their own right — some have no student at all.
defineProps({
    student: { type: Object, required: true },
    data: { type: Object, required: true },
    currency: { type: String, default: 'MWK' },
});
</script>

<template>
    <section v-if="!data.household" class="card-container py-10 text-center text-sm text-text-muted">
        {{ student.first_name }} isn't linked to a family yet.
        <Link v-if="can('update student')" :href="route('students.edit', student.id)" class="text-mustGreen hover:underline">Link a family</Link>
    </section>

    <section v-else class="card-container">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="page-heading-2 !mb-0 flex items-center gap-2"><Home class="h-4 w-4" /> {{ data.household.name }} family</h2>
                <p class="text-sm text-text-muted">
                    Home visits and the support given are recorded on the family's page.
                    <span v-if="!data.isSupported" class="ml-1 rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-700">No longer supported</span>
                </p>
            </div>
            <Link
                v-if="can('manage home visits')"
                :href="route('support.families.show', data.household.id)"
                class="btn-primary-sm inline-flex items-center gap-1"
            >
                Open family page <ArrowRight class="h-4 w-4" />
            </Link>
        </div>

        <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg bg-mustGreen/5 p-3">
                <dt class="text-xs uppercase tracking-wider text-text-muted">Last visit</dt>
                <dd class="font-semibold text-mustBlue dark:text-text-inverted">{{ data.lastVisit ? date.formatDate(data.lastVisit) : 'Never visited' }}</dd>
            </div>
            <div class="rounded-lg p-3" :class="data.visitOverdue ? 'bg-gray-100 dark:bg-white/5' : 'bg-mustGreen/5'">
                <dt class="text-xs uppercase tracking-wider text-text-muted">Next visit due</dt>
                <dd class="font-semibold" :class="data.visitOverdue ? 'text-red-700' : 'text-mustBlue dark:text-text-inverted'">
                    {{ data.nextVisitDue ? date.formatDate(data.nextVisitDue) : '—' }}
                    <span v-if="data.visitOverdue" class="ml-1 text-xs font-normal">(due)</span>
                </dd>
            </div>
            <div class="rounded-lg bg-mustGreen/5 p-3">
                <dt class="text-xs uppercase tracking-wider text-text-muted">Visits so far</dt>
                <dd class="font-semibold text-mustBlue dark:text-text-inverted">{{ data.visits }}</dd>
            </div>
            <div class="rounded-lg bg-mustGreen/5 p-3">
                <dt class="flex items-center gap-1 text-xs uppercase tracking-wider text-text-muted"><HandHeart class="h-3 w-3" /> Support given</dt>
                <dd class="font-semibold text-mustBlue dark:text-text-inverted">{{ data.supportTotal ? formatMoney(data.supportTotal, currency) : 'None yet' }}</dd>
            </div>
        </dl>

        <p v-if="data.needsIdentified" class="mt-4 text-sm">
            <span class="text-text-muted">Needs found at the last visit:</span> {{ data.needsIdentified }}
        </p>
    </section>
</template>

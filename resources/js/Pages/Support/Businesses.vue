<script setup>
import { date } from '@/Helpers';
import { formatMoney } from '@/Helpers/money';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Store, Eye } from 'lucide-vue-next';

const props = defineProps({
    currency: { type: String, default: 'MWK' },
    totals: { type: Object, required: true },
    checkinDays: { type: Number, default: 90 },
    businesses: { type: Array, default: () => [] },
});

const money = (v) => formatMoney(v, props.currency);
</script>

<template>
    <Head title="Businesses" />

    <AuthenticatedLayout>
        <h1 class="page-heading !mb-1">Youth businesses</h1>
        <p class="mb-6 text-sm text-text-muted dark:text-text-dark-muted">
            Businesses are added from each student's profile (Business tab). A running business is due a check-in every {{ checkinDays }} days.
        </p>

        <dl class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="card-container !mb-0">
                <dt class="text-xs uppercase tracking-wider text-text-muted">Businesses started</dt>
                <dd class="text-2xl font-bold text-mustBlue dark:text-text-inverted">{{ totals.started }}</dd>
            </div>
            <div class="card-container !mb-0">
                <dt class="text-xs uppercase tracking-wider text-text-muted">Still running</dt>
                <dd class="text-2xl font-bold text-mustGreen">{{ totals.running }}</dd>
            </div>
            <div class="card-container !mb-0">
                <dt class="text-xs uppercase tracking-wider text-text-muted">Money given</dt>
                <dd class="text-xl font-bold text-mustBlue dark:text-text-inverted">{{ money(totals.given) }}</dd>
            </div>
            <div class="card-container !mb-0">
                <dt class="text-xs uppercase tracking-wider text-text-muted">Loans repaid / outstanding</dt>
                <dd class="text-lg font-bold text-mustBlue dark:text-text-inverted">{{ money(totals.repaid) }} <span class="text-sm font-normal text-red-700">/ {{ money(totals.outstanding) }}</span></dd>
            </div>
        </dl>

        <div class="card-container overflow-x-auto">
            <p v-if="!businesses.length" class="flex items-center justify-center gap-2 py-8 text-sm text-text-muted"><Store class="h-4 w-4" /> No businesses recorded yet.</p>
            <table v-else class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                        <th class="px-3 py-2">Business</th>
                        <th class="px-3 py-2">Owner</th>
                        <th class="px-3 py-2">Started</th>
                        <th class="px-3 py-2 text-right">Given</th>
                        <th class="px-3 py-2 text-right">Loan balance</th>
                        <th class="px-3 py-2">Last check-in</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="b in businesses" :key="b.id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                        <td class="px-3 py-2 font-medium text-mustBlue dark:text-text-inverted">
                            {{ b.name }}
                            <span v-if="b.business_type" class="block text-xs font-normal text-text-muted">{{ b.business_type }}</span>
                        </td>
                        <td class="px-3 py-2"><span class="text-mustBlue dark:text-text-inverted">{{ b.student }}</span></td>
                        <td class="px-3 py-2">{{ date.formatDate(b.start_date) }}</td>
                        <td class="px-3 py-2 text-right">{{ money(b.amount_given) }} <span class="block text-xs text-text-muted">{{ b.funding_label }}</span></td>
                        <td class="px-3 py-2 text-right" :class="b.balance > 0 ? 'text-red-700' : 'text-text-muted'">{{ b.balance > 0 ? money(b.balance) : '—' }}</td>
                        <td class="px-3 py-2">
                            {{ b.last_checkin ? date.formatDate(b.last_checkin) : 'None' }}
                            <span v-if="b.checkin_due" class="ml-1 rounded-full bg-yl-gold/15 px-2 py-0.5 text-xs font-medium text-amber-800">Due</span>
                        </td>
                        <td class="px-3 py-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="b.is_running ? 'bg-mustGreen/15 text-yl-green-deep dark:text-accent-light' : 'bg-gray-200 text-gray-700 dark:bg-gray-500/20 dark:text-gray-300'">{{ b.is_running ? 'Running' : 'Stopped' }}</span>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <Link :href="route('students.show', b.student_id) + '#business'" class="action-icon inline-flex" :aria-label="`View ${b.student}`" title="View">
                                <Eye class="h-[19px] w-[19px]" />
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AuthenticatedLayout>
</template>

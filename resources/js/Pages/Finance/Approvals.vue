<script setup>
import ReasonModal from '@/Components/Common/ReasonModal.vue';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import { date } from '@/Helpers';
import { formatMoney } from '@/Helpers/money';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Check, CircleCheck, ExternalLink, Eye, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    currency: { type: String, default: 'MWK' },
    scholarships: { type: Array, default: () => [] },
});

const money = (amount) => formatMoney(amount, props.currency);

// approving = { kind: 'scholarship', item }
const approving = ref(null);
const rejecting = ref(null);

const group = { scholarship: 'scholarships' };
const approve = () => {
    const { kind, item } = approving.value;
    router.put(route(`finance.approvals.${group[kind]}.approve`, item.id), {}, { preserveScroll: true });
    approving.value = null;
};
const rejectUrl = () => (rejecting.value ? route(`finance.approvals.${group[rejecting.value.kind]}.reject`, rejecting.value.item.id) : null);
</script>

<template>
    <Head title="Approvals" />

    <AuthenticatedLayout>
        <h1 class="page-heading">Approvals</h1>
        <p class="mb-6 text-sm text-text-muted dark:text-text-dark-muted">
            New scholarships recorded by managers wait here for your decision.
            The person who recorded each one is told what you decide.
            <Link :href="route('admin.settings.approvals.edit')" class="text-mustGreen hover:underline">Change the rules</Link>
        </p>

        <div v-if="!scholarships.length" class="card-container py-12 text-center">
            <CircleCheck class="mx-auto h-10 w-10 text-mustGreen" />
            <p class="mt-2 font-medium text-mustBlue dark:text-text-inverted">Nothing is waiting for approval.</p>
        </div>

        <!-- Scholarships -->
        <section v-if="scholarships.length" class="card-container mb-6">
            <h2 class="page-heading-2">New scholarships ({{ scholarships.length }})</h2>
            <p class="mb-4 text-sm text-text-muted">A student's first fee entry. Approving it lets payments be recorded for them.</p>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                            <th class="px-3 py-2">Student</th>
                            <th class="px-3 py-2">Term</th>
                            <th class="px-3 py-2">School</th>
                            <th class="px-3 py-2 text-right">Fees</th>
                            <th class="px-3 py-2 text-right">Youthline pays</th>
                            <th class="px-3 py-2">Added by</th>
                            <th class="px-3 py-2"><span class="sr-only">Decision</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in scholarships" :key="s.id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                            <td class="px-3 py-2">
                                <span class="font-medium text-mustBlue dark:text-text-inverted">{{ s.student }}</span>
                                <span v-if="s.notes" class="block text-xs text-text-muted">{{ s.notes }}</span>
                            </td>
                            <td class="px-3 py-2">{{ s.term }}</td>
                            <td class="px-3 py-2 text-text-muted">{{ s.school ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ money(s.amount_owed) }}</td>
                            <td class="px-3 py-2 text-right font-semibold">{{ money(s.youthline_share) }}</td>
                            <td class="px-3 py-2 text-text-muted">{{ s.created_by ?? '—' }}<span class="block text-xs">{{ date.formatDate(s.created_at) }}</span></td>
                            <td class="px-3 py-2">
                                <div class="flex justify-end gap-2">
                                    <Link :href="route('students.show', s.student_id) + '#academics'" class="action-icon inline-flex items-center px-1" :aria-label="`View ${s.student}`" title="View">
                                        <Eye class="h-[19px] w-[19px]" />
                                    </Link>
                                    <button type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="approving = { kind: 'scholarship', item: s }">
                                        <Check class="h-4 w-4" /> Approve
                                    </button>
                                    <button type="button" class="btn-danger-sm inline-flex items-center gap-1" @click="rejecting = { kind: 'scholarship', item: s }">
                                        <X class="h-4 w-4" /> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <ConfirmationModal :show="!!approving" type="success" :close="() => (approving = null)" @confirm="approve">
            <template #title>Approve scholarship</template>
            <template #message>
                <template v-if="approving">
                    Approve the new scholarship for <strong>{{ approving.item.student }}</strong> ({{ money(approving.item.youthline_share) }} for {{ approving.item.term }})?
                </template>
            </template>
        </ConfirmationModal>

        <ReasonModal
            :show="!!rejecting"
            :url="rejectUrl()"
            title="Reject scholarship"
            intro="The manager who recorded it will see your reason."
            label="Reason"
            confirm-text="Reject"
            @close="rejecting = null"
        />
    </AuthenticatedLayout>
</template>

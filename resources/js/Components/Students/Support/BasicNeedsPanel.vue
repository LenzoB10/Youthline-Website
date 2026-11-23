<script setup>
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { date } from '@/Helpers';
import { can } from '@/Helpers/can';
import { formatMoney } from '@/Helpers/money';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ShoppingBasket, Trash2, Users } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    student: { type: Object, required: true },
    data: { type: Object, required: true },
    currency: { type: String, default: 'MWK' },
});

const canRecord = can('manage basic needs');
const canDelete = can('delete support records');
const today = new Date().toISOString().slice(0, 10);
const money = (v) => formatMoney(v, props.currency);

const form = useForm({ given_on: today, amount: '', note: '' });
const submit = () =>
    form.post(route('students.distributions.store', props.student.id), {
        preserveScroll: true,
        onSuccess: () => form.reset('amount', 'note'),
    });

const deleting = ref(null);
const destroy = () => {
    router.delete(route('students.distributions.destroy', [props.student.id, deleting.value.id]), { preserveScroll: true });
    deleting.value = null;
};
</script>

<template>
    <section class="card-container">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="page-heading-2 !mb-0 flex items-center gap-2"><ShoppingBasket class="h-4 w-4" /> Basic needs</h2>
                <p class="text-sm text-text-muted">Money given to {{ student.first_name }} for groceries and other basic needs.</p>
            </div>
            <Link v-if="canRecord" :href="route('support.basic-needs.index')" class="btn-outline-sm inline-flex items-center gap-1">
                <Users class="h-4 w-4" /> Group handout
            </Link>
        </div>

        <dl class="mb-5 grid gap-3 sm:grid-cols-2">
            <div class="rounded-lg bg-mustGreen/5 p-3">
                <dt class="text-xs uppercase tracking-wider text-text-muted">Given this year</dt>
                <dd class="text-lg font-bold text-mustGreen">{{ money(data.thisYear) }}</dd>
            </div>
            <div class="rounded-lg bg-mustGreen/5 p-3">
                <dt class="text-xs uppercase tracking-wider text-text-muted">Given in total</dt>
                <dd class="text-lg font-bold text-mustBlue dark:text-text-inverted">{{ money(data.total) }}</dd>
            </div>
        </dl>

        <p v-if="canRecord && !data.canReceive" class="mb-5 rounded-lg border border-dashed border-mustBorder-light p-4 text-sm text-text-muted dark:border-mustBorder-dark">
            {{ student.first_name ?? 'This student' }} has graduated, so no more basic needs can be given. Graduates can still be supported with business money on the Business tab.
        </p>
        <form
            v-else-if="canRecord"
            class="mb-5 grid gap-3 rounded-lg border border-dashed border-mustBorder-light p-4 sm:grid-cols-[11rem_11rem_1fr_auto] sm:items-end dark:border-mustBorder-dark"
            @submit.prevent="submit"
        >
            <div>
                <InputLabel for="bn_date" value="Date" />
                <TextInput id="bn_date" v-model="form.given_on" type="date" :max="today" class="mt-1 block w-full" required />
                <InputError class="mt-1" :message="form.errors.given_on" />
            </div>
            <div>
                <InputLabel for="bn_amount" :value="`Amount (${currency})`" />
                <TextInput id="bn_amount" v-model="form.amount" type="number" min="0.01" step="0.01" placeholder="e.g. 20000" class="mt-1 block w-full" required />
                <InputError class="mt-1" :message="form.errors.amount" />
            </div>
            <div>
                <InputLabel for="bn_note" value="Note (optional)" />
                <TextInput id="bn_note" v-model="form.note" placeholder="e.g. Groceries for November" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="form.errors.note" />
            </div>
            <button type="submit" class="btn-primary-sm" :disabled="form.processing">Record</button>
        </form>

        <p v-if="!data.items.length" class="py-6 text-center text-sm text-text-muted">Nothing recorded yet.</p>
        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                        <th class="px-3 py-2">Date</th>
                        <th class="px-3 py-2 text-right">Amount</th>
                        <th class="px-3 py-2">Note</th>
                        <th class="px-3 py-2">Recorded by</th>
                        <th class="px-3 py-2"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in data.items" :key="row.id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                        <td class="px-3 py-2">{{ date.formatDate(row.given_on) }}</td>
                        <td class="px-3 py-2 text-right font-medium text-mustBlue dark:text-text-inverted">{{ row.what }}</td>
                        <td class="px-3 py-2 text-text-muted">{{ row.note ?? '—' }}</td>
                        <td class="px-3 py-2 text-text-muted">{{ row.recorded_by ?? '—' }}</td>
                        <td class="px-3 py-2 text-right">
                            <button v-if="canDelete" type="button" class="text-red-500 hover:text-red-700" :aria-label="`Delete entry from ${row.given_on}`" @click="deleting = row">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ConfirmationModal :show="!!deleting" :close="() => (deleting = null)" @confirm="destroy">
            <template #title>Delete entry</template>
            <template #message>Delete {{ deleting?.what }} given on {{ date.formatDate(deleting?.given_on) }}?</template>
        </ConfirmationModal>
    </section>
</template>

<script setup>
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import ResultModal from '@/Components/Students/ResultModal.vue';
import { can } from '@/Helpers/can';
import { formatPercent } from '@/Helpers/money';
import { router } from '@inertiajs/vue3';
import { ExternalLink, Plus, SquarePen, Trash2, TrendingDown, TrendingUp } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    student: { type: Object, required: true },
    results: { type: Array, default: () => [] },
    terms: { type: Array, default: () => [] },
    resultTypes: { type: Array, default: () => [] },
    dropThreshold: { type: Number, default: 10 },
});

const canRecord = can('record results');
const canDelete = can('delete results');

const showModal = ref(false);
const editing = ref(null);
const openNew = () => {
    editing.value = null;
    showModal.value = true;
};
const openEdit = (result) => {
    editing.value = result;
    showModal.value = true;
};

const deleting = ref(null);
const destroy = () => {
    router.delete(route('students.results.destroy', [props.student.id, deleting.value.id]), { preserveScroll: true });
    deleting.value = null;
};

const changeText = (change) => `${change > 0 ? '+' : ''}${Number(change).toLocaleString('en-US', { maximumFractionDigits: 1 })}`;
</script>

<template>
    <section class="card-container">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="page-heading-2 !mb-0">Results</h2>
                <p class="text-sm text-text-muted">
                    Term, MSCE and university results. A fall of {{ dropThreshold }} points or more on the previous term is flagged.
                </p>
            </div>
            <button v-if="canRecord" type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="openNew">
                <Plus class="h-4 w-4" /> Record result
            </button>
        </div>

        <p v-if="!results.length" class="py-6 text-center text-sm text-text-muted">No results recorded yet.</p>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                        <th class="px-3 py-2">Term</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">Result</th>
                        <th class="px-3 py-2">Change</th>
                        <th class="px-3 py-2">Position</th>
                        <th class="px-3 py-2">Subjects</th>
                        <th class="px-3 py-2"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in results"
                        :key="row.id"
                        class="border-b border-mustBorder-light align-top last:border-0 dark:border-mustBorder-dark"
                        :class="{ 'bg-red-50/60 dark:bg-red-500/5': row.is_drop }"
                    >
                        <td class="px-3 py-2 font-medium text-mustBlue dark:text-text-inverted">
                            {{ row.term || '—' }}
                            <span v-if="row.level" class="block text-xs font-normal text-text-muted">{{ row.level }}</span>
                        </td>
                        <td class="px-3 py-2">{{ row.exam_type_label }}</td>
                        <td class="px-3 py-2 font-semibold">
                            <template v-if="row.exam_type === 'msce'">{{ row.msce_points }} points</template>
                            <template v-else>{{ formatPercent(row.average) }}</template>
                        </td>
                        <td class="px-3 py-2">
                            <span v-if="row.change === null" class="text-text-muted">—</span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="row.is_drop ? 'bg-red-100 text-red-800' : row.change < 0 ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800'"
                            >
                                <component :is="row.change < 0 ? TrendingDown : TrendingUp" class="h-3 w-3" />
                                {{ changeText(row.change) }}
                                <template v-if="row.is_drop"> · sharp drop</template>
                            </span>
                        </td>
                        <td class="px-3 py-2 text-text-muted">{{ row.position || '—' }}</td>
                        <td class="px-3 py-2">
                            <details v-if="row.grades.length">
                                <summary class="cursor-pointer text-mustGreen">{{ row.grades.length }} subjects</summary>
                                <ul class="mt-2 space-y-0.5 text-xs">
                                    <li v-for="g in row.grades" :key="g.subject" class="flex justify-between gap-4">
                                        <span>{{ g.subject }}</span><span class="font-semibold">{{ g.grade || '—' }}</span>
                                    </li>
                                </ul>
                                <p v-if="row.remarks" class="mt-2 text-xs italic text-text-muted">“{{ row.remarks }}”</p>
                            </details>
                            <span v-else class="text-text-muted">—</span>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-end gap-3">
                                <a v-if="row.report_card_url" :href="row.report_card_url" target="_blank" rel="noopener" class="action-icon" :aria-label="`Open report card for ${row.term}`" title="Report card">
                                    <ExternalLink class="h-4 w-4" />
                                </a>
                                <button v-if="canRecord" type="button" class="action-icon" :aria-label="`Edit result for ${row.term}`" @click="openEdit(row)">
                                    <SquarePen class="h-4 w-4" />
                                </button>
                                <button v-if="canDelete" type="button" class="text-red-500 transition hover:text-red-700" :aria-label="`Delete result for ${row.term}`" @click="deleting = row">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ResultModal
            :show="showModal"
            :student="student"
            :result="editing"
            :last-result="results[0] ?? null"
            :terms="terms"
            :result-types="resultTypes"
            @close="showModal = false"
        />

        <ConfirmationModal :show="!!deleting" :close="() => (deleting = null)" @confirm="destroy">
            <template #title>Delete result</template>
            <template #message>Delete the {{ deleting?.exam_type_label }} result for <strong>{{ deleting?.term }}</strong>? The report card stays in the student's documents.</template>
        </ConfirmationModal>
    </section>
</template>

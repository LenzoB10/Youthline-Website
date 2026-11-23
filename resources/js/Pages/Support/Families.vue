<script setup>
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import FamilyForm from '@/Components/Support/FamilyForm.vue';
import { date } from '@/Helpers';
import { formatMoney } from '@/Helpers/money';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarClock, CircleCheck, Eye, HandHeart, Home, Plus, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    currency: { type: String, default: 'MWK' },
    households: { type: Array, default: () => [] },
    due: { type: Array, default: () => [] },
    supportTotals: { type: Object, required: true },
    recentSupport: { type: Array, default: () => [] },
});

const money = (v) => formatMoney(v, props.currency);
const adding = ref(false);

// --- All families ---
const search = ref('');
const kind = ref('supported');
const kinds = computed(() => [
    { key: 'supported', label: 'Currently supported', count: props.households.filter((h) => h.is_supported).length },
    { key: 'student', label: 'Student families', count: props.households.filter((h) => h.kind === 'student').length },
    { key: 'community', label: 'Community families', count: props.households.filter((h) => h.kind === 'community').length },
    { key: 'ended', label: 'No longer supported', count: props.households.filter((h) => !h.is_supported).length },
    { key: 'all', label: 'All', count: props.households.length },
]);
const shownHouseholds = computed(() => {
    const term = search.value.trim().toLowerCase();
    return props.households.filter((h) => {
        const matchesKind =
            kind.value === 'all' ||
            (kind.value === 'supported' && h.is_supported) ||
            (kind.value === 'ended' && !h.is_supported) ||
            h.kind === kind.value;
        const matchesTerm = !term || [h.name, h.area, ...h.students].join(' ').toLowerCase().includes(term);
        return matchesKind && matchesTerm;
    });
});

</script>

<template>
    <Head title="Families" />

    <AuthenticatedLayout>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="page-heading !mb-1">Families</h1>
                <p class="text-sm text-text-muted dark:text-text-dark-muted">
                    Every family Youthline supports — students' homes and community families. Open a family to record visits and the support given.
                </p>
            </div>
            <button type="button" class="btn-primary inline-flex items-center gap-1" @click="adding = true"><Plus class="h-4 w-4" /> Add family</button>
        </div>

        <!-- All families -->
        <section class="card-container mb-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 class="page-heading-2 !mb-0 flex items-center gap-2"><Home class="h-4 w-4" /> Families</h2>
                <div class="relative w-full max-w-xs">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" />
                    <label for="family_search" class="sr-only">Search families</label>
                    <input id="family_search" v-model="search" type="search" placeholder="Search family, area or student" class="text-input w-full !pl-9" />
                </div>
            </div>
            <div class="mb-4 flex flex-wrap gap-1 text-sm" role="group" aria-label="Show families">
                <button
                    v-for="k in kinds"
                    :key="k.key"
                    type="button"
                    class="rounded-full px-3 py-1 transition"
                    :class="kind === k.key ? 'bg-mustGreen text-white' : 'bg-mustGreen/10 text-mustGreen hover:bg-mustGreen/20'"
                    :aria-pressed="kind === k.key"
                    @click="kind = k.key"
                >
                    {{ k.label }} ({{ k.count }})
                </button>
            </div>
            <p v-if="!shownHouseholds.length" class="py-4 text-sm text-text-muted">No families here.</p>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                            <th class="px-3 py-2">Family</th>
                            <th class="px-3 py-2">Students</th>
                            <th class="px-3 py-2">Last visit</th>
                            <th class="px-3 py-2">Next visit due</th>
                            <th class="px-3 py-2 text-right">Support given</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="h in shownHouseholds" :key="h.id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark" :class="{ 'opacity-60': !h.is_supported }">
                            <td class="px-3 py-2">
                                <span class="font-medium text-mustBlue dark:text-text-inverted">{{ h.name }}</span>
                                <span class="ml-2 rounded-full px-2 py-0.5 text-xs" :class="h.kind === 'student' ? 'bg-yl-gold/15 text-amber-800' : 'bg-gray-100 text-gray-700'">
                                    {{ h.kind === 'student' ? 'Student' : 'Community' }}
                                </span>
                                <span v-if="h.area" class="block text-xs text-text-muted">{{ h.area }}</span>
                            </td>
                            <td class="px-3 py-2 text-text-muted">{{ h.students.join(', ') || '—' }}</td>
                            <td class="px-3 py-2">{{ h.last_visit ? date.formatDate(h.last_visit) : 'Never' }}</td>
                            <td class="px-3 py-2">{{ h.next_visit_due ? date.formatDate(h.next_visit_due) : '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ h.support_total ? money(h.support_total) : '—' }}</td>
                            <td class="px-3 py-2 text-right">
                                <Link :href="route('support.families.show', h.id)" class="action-icon inline-flex" :aria-label="`View ${h.name}`" title="View">
                                    <Eye class="h-[19px] w-[19px]" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Visits due -->
        <section class="card-container mb-6">
            <h2 class="page-heading-2 flex items-center gap-2"><CalendarClock class="h-4 w-4" /> Home visits due ({{ due.length }})</h2>
            <p class="mb-4 text-sm text-text-muted">Supported families never visited, overdue, or due within two weeks.</p>
            <p v-if="!due.length" class="flex items-center gap-2 py-4 text-sm text-text-muted"><CircleCheck class="h-4 w-4 text-mustGreen" /> Every family is up to date.</p>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                            <th class="px-3 py-2">Family</th>
                            <th class="px-3 py-2">Students</th>
                            <th class="px-3 py-2">Last visit</th>
                            <th class="px-3 py-2">Next visit due</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="h in due" :key="h.household_id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                            <td class="px-3 py-2">
                                <span class="font-medium text-mustBlue dark:text-text-inverted">{{ h.household }}</span>
                                <span v-if="h.area" class="block text-xs text-text-muted">{{ h.area }}</span>
                            </td>
                            <td class="px-3 py-2 text-text-muted">{{ h.students.join(', ') || '—' }}</td>
                            <td class="px-3 py-2">{{ h.last_visit ? date.formatDate(h.last_visit) : 'Never' }}</td>
                            <td class="px-3 py-2">
                                <span :class="h.is_overdue ? 'font-semibold text-red-700' : ''">{{ h.next_visit_due ? date.formatDate(h.next_visit_due) : 'Now' }}</span>
                                <span v-if="h.is_overdue" class="ml-1 rounded-full px-2 py-0.5 text-xs bg-gray-700 text-white dark:bg-gray-300 dark:text-gray-900">Overdue</span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <Link :href="route('support.families.show', h.household_id)" class="action-icon inline-flex" :aria-label="`View ${h.household}`" title="View">
                                    <Eye class="h-[19px] w-[19px]" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Recent support -->
        <section class="card-container">
            <h2 class="page-heading-2 flex items-center gap-2"><HandHeart class="h-4 w-4" /> Support given to families</h2>
            <p class="mb-4 text-sm text-text-muted">
                In {{ supportTotals.year }}:
                <strong class="text-mustBlue dark:text-text-inverted">{{ money(supportTotals.given) }}</strong>
                to {{ supportTotals.families }} {{ supportTotals.families === 1 ? 'family' : 'families' }}
                ({{ supportTotals.entries }} {{ supportTotals.entries === 1 ? 'entry' : 'entries' }}). Latest first.
            </p>
            <p v-if="!recentSupport.length" class="py-4 text-sm text-text-muted">No support recorded yet. Open a family to record it.</p>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Family</th>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2">What was given</th>
                            <th class="px-3 py-2 text-right">Amount</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in recentSupport" :key="r.id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                            <td class="px-3 py-2 whitespace-nowrap">{{ date.formatDate(r.given_on) }}</td>
                            <td class="px-3 py-2"><span class="font-medium text-mustBlue dark:text-text-inverted">{{ r.household }}</span></td>
                            <td class="px-3 py-2"><span class="rounded-full bg-mustGreen/15 px-2 py-0.5 text-xs text-yl-green-deep dark:text-accent-light">{{ r.type_label }}</span></td>
                            <td class="px-3 py-2">{{ r.description }}</td>
                            <td class="px-3 py-2 text-right">{{ r.amount === null ? '—' : money(r.amount) }}</td>
                            <td class="px-3 py-2 text-right">
                                <Link :href="route('support.families.show', r.household_id)" class="action-icon inline-flex" :aria-label="`View ${r.household}`" title="View">
                                    <Eye class="h-[19px] w-[19px]" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <Modal maxWidth="lg" :show="adding" :closeOnClickOutside="false" @close="adding = false">
            <div class="p-6">
                <ModalHeader title="Add a family" :close="() => (adding = false)" />
                <p class="mb-4 text-sm text-text-muted">For a family Youthline supports directly. A student's family is created when you add the student.</p>
                <FamilyForm @saved="adding = false" @cancel="adding = false" />
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

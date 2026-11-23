<script setup>
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import { formatMoney } from '@/Helpers/money';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SponsorForm from '@/Pages/Sponsors/Partials/SponsorForm.vue';
import { Head, Link } from '@inertiajs/vue3';
import { HeartHandshake, Plus, Eye } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    currency: { type: String, default: 'MWK' },
    types: { type: Array, default: () => [] },
    sponsors: { type: Array, default: () => [] },
});

const money = (v) => formatMoney(v, props.currency);
const showForm = ref(false);
const search = ref('');
const shown = computed(() => {
    const term = search.value.trim().toLowerCase();
    return term ? props.sponsors.filter((s) => [s.name, s.email].join(' ').toLowerCase().includes(term)) : props.sponsors;
});
</script>

<template>
    <Head title="Sponsors" />

    <AuthenticatedLayout>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="page-heading !mb-1">Sponsors</h1>
                <p class="text-sm text-text-muted dark:text-text-dark-muted">People and organisations who sponsor students. Open one to link students and download their progress report.</p>
            </div>
            <button type="button" class="btn-primary inline-flex items-center gap-1" @click="showForm = true"><Plus class="h-4 w-4" /> Add sponsor</button>
        </div>

        <div class="card-container">
            <label for="sponsor_search" class="sr-only">Search sponsors</label>
            <input id="sponsor_search" v-model="search" type="search" placeholder="Search by name or email" class="text-input mb-4 block w-full max-w-sm" />

            <p v-if="!shown.length" class="flex items-center justify-center gap-2 py-8 text-sm text-text-muted"><HeartHandshake class="h-4 w-4" /> No sponsors found.</p>

            <!-- Phones: one card per sponsor -->
            <ul v-if="shown.length" class="divide-y divide-mustBorder-light md:hidden dark:divide-mustBorder-dark">
                <li v-for="s in shown" :key="`m-${s.id}`" class="py-4 first:pt-1 last:pb-1">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-mustBlue dark:text-text-inverted">{{ s.name }}</p>
                            <p class="text-xs text-text-muted">{{ s.type_label }}</p>
                        </div>
                        <Link :href="route('sponsors.show', s.id)" class="btn-outline-sm shrink-0" :aria-label="`View ${s.name}`">
                            <Eye class="h-4 w-4" /> View
                        </Link>
                    </div>
                    <p v-if="s.email" class="mt-2 break-all text-sm text-text-muted">{{ s.email }}</p>
                    <p v-if="s.phone" class="text-sm text-text-muted">{{ s.phone }}</p>
                    <dl class="mt-3 grid grid-cols-2 gap-3 rounded-lg bg-gray-50 p-3 text-sm dark:bg-white/5">
                        <div>
                            <dt class="text-xs text-text-muted">Students now</dt>
                            <dd class="font-semibold text-mustBlue dark:text-text-inverted">{{ s.students_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-text-muted">Committed per year</dt>
                            <dd class="font-semibold text-mustBlue dark:text-text-inverted">{{ s.yearly_total ? money(s.yearly_total) : '—' }}</dd>
                        </div>
                    </dl>
                </li>
            </ul>

            <!-- Tablets and computers: the table -->
            <div v-if="shown.length" class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                            <th class="px-3 py-2">Sponsor</th>
                            <th class="px-3 py-2">Contact</th>
                            <th class="px-3 py-2 text-right">Students now</th>
                            <th class="px-3 py-2 text-right">Committed per year</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in shown" :key="s.id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                            <td class="px-3 py-2">
                                <span class="font-medium text-mustBlue dark:text-text-inverted">{{ s.name }}</span>
                                <span class="block text-xs text-text-muted">{{ s.type_label }}</span>
                            </td>
                            <td class="px-3 py-2 text-text-muted">{{ [s.email, s.phone].filter(Boolean).join(' · ') || '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ s.students_count }}</td>
                            <td class="px-3 py-2 text-right">{{ s.yearly_total ? money(s.yearly_total) : '—' }}</td>
                            <td class="px-3 py-2 text-right">
                                <Link :href="route('sponsors.show', s.id)" class="action-icon inline-flex" :aria-label="`View ${s.name}`" title="View">
                                    <Eye class="h-[19px] w-[19px]" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Modal maxWidth="lg" :show="showForm" :closeOnClickOutside="false" @close="showForm = false">
            <div class="p-6">
                <ModalHeader title="Add a sponsor" :close="() => (showForm = false)" />
                <SponsorForm :types="types" @saved="showForm = false" @cancel="showForm = false" />
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

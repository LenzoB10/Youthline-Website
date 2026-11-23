<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Quote, SquarePen, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    testimonials: { type: Array, default: () => [] },
});

const deleting = ref(null);
const destroy = () => {
    router.delete(route('admin.website.testimonials.destroy', deleting.value.id), { preserveScroll: true });
    deleting.value = null;
};
</script>

<template>
    <Head title="Testimonials" />

    <AuthenticatedLayout>
        <BackLink :href="route('admin.website.index')">Website content</BackLink>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="page-heading !mb-1">Testimonials</h1>
                <p class="text-sm text-text-muted dark:text-text-dark-muted">
                    Quotes from students, families and sponsors, shown as moving cards on the home page. Only published ones appear.
                </p>
            </div>
            <Link :href="route('admin.website.testimonials.create')" class="btn-primary">
                <Plus class="h-4 w-4" /> New testimonial
            </Link>
        </div>

        <div class="card-container overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                        <th class="px-3 py-2">Person</th>
                        <th class="px-3 py-2">Quote</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Order</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!testimonials.length">
                        <td colspan="5" class="px-3 py-10 text-center text-text-muted">
                            <Quote class="mx-auto mb-2 h-6 w-6" /> No testimonials yet. Add the first one to show the section on the home page.
                        </td>
                    </tr>
                    <tr v-for="t in testimonials" :key="t.id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-3">
                                <img v-if="t.photo" :src="t.photo" :alt="t.name" class="h-9 w-9 rounded-full object-cover" />
                                <span v-else class="flex h-9 w-9 items-center justify-center rounded-full bg-mustGreen/10 text-xs font-bold text-mustGreen">
                                    {{ t.name?.charAt(0) }}
                                </span>
                                <span>
                                    <span class="block font-medium text-mustBlue dark:text-text-inverted">{{ t.name }}</span>
                                    <span v-if="t.role" class="block text-xs text-text-muted">{{ t.role }}</span>
                                </span>
                            </div>
                        </td>
                        <td class="max-w-md px-3 py-3 text-text-muted"><span class="line-clamp-2">“{{ t.quote }}”</span></td>
                        <td class="px-3 py-3">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="t.is_published ? 'bg-mustGreen/15 text-yl-green-deep dark:text-accent-light' : 'bg-gray-200 text-gray-700 dark:bg-gray-500/20 dark:text-gray-300'"
                            >
                                {{ t.is_published ? 'Published' : 'Hidden' }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-text-muted">{{ t.display_order }}</td>
                        <td class="px-3 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <Link :href="route('admin.website.testimonials.edit', t.id)" class="action-icon" :aria-label="`Edit ${t.name}`" title="Edit">
                                    <SquarePen class="h-4 w-4" />
                                </Link>
                                <button type="button" class="action-icon text-red-500" :aria-label="`Delete ${t.name}`" title="Delete" @click="deleting = t">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ConfirmationModal :show="!!deleting" :close="() => (deleting = null)" @confirm="destroy">
            <template #title>Delete testimonial</template>
            <template #message>Delete the testimonial from {{ deleting?.name }}? This can't be undone.</template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

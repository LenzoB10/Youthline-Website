<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2, ExternalLink, Plus, SquarePen, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    partners: { type: Array, default: () => [] },
});

const deleting = ref(null);
const destroy = () => {
    router.delete(route('admin.website.partners.destroy', deleting.value.id), { preserveScroll: true });
    deleting.value = null;
};
</script>

<template>
    <Head title="Partners" />

    <AuthenticatedLayout>
        <BackLink :href="route('admin.website.index')">Website content</BackLink>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="page-heading !mb-1">Partners</h1>
                <p class="text-sm text-text-muted dark:text-text-dark-muted">
                    Organisations that support Youthline. Their logos show on the home page; only add ones that agreed to be listed.
                </p>
            </div>
            <Link :href="route('admin.website.partners.create')" class="btn-primary">
                <Plus class="h-4 w-4" /> New partner
            </Link>
        </div>

        <div class="card-container overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                        <th class="px-3 py-2">Partner</th>
                        <th class="px-3 py-2">Website</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Order</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!partners.length">
                        <td colspan="5" class="px-3 py-10 text-center text-text-muted">
                            <Building2 class="mx-auto mb-2 h-6 w-6" /> No partners yet. Add the first one to show the logo strip on the home page.
                        </td>
                    </tr>
                    <tr v-for="p in partners" :key="p.id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark">
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-16 items-center justify-center rounded-md border border-mustBorder-light bg-white p-1 dark:border-mustBorder-dark">
                                    <img v-if="p.logo" :src="p.logo" :alt="p.name" class="max-h-full max-w-full object-contain" />
                                    <Building2 v-else class="h-4 w-4 text-text-muted" />
                                </span>
                                <span class="font-medium text-mustBlue dark:text-text-inverted">{{ p.name }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-3">
                            <a v-if="p.website_url" :href="p.website_url" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-mustGreen hover:underline">
                                Visit <ExternalLink class="h-3.5 w-3.5" />
                            </a>
                            <span v-else class="text-text-muted">—</span>
                        </td>
                        <td class="px-3 py-3">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="p.is_published ? 'bg-mustGreen/15 text-yl-green-deep dark:text-accent-light' : 'bg-gray-200 text-gray-700 dark:bg-gray-500/20 dark:text-gray-300'"
                            >
                                {{ p.is_published ? 'Published' : 'Hidden' }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-text-muted">{{ p.display_order }}</td>
                        <td class="px-3 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <Link :href="route('admin.website.partners.edit', p.id)" class="action-icon" :aria-label="`Edit ${p.name}`" title="Edit">
                                    <SquarePen class="h-4 w-4" />
                                </Link>
                                <button type="button" class="action-icon text-red-500" :aria-label="`Delete ${p.name}`" title="Delete" @click="deleting = p">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ConfirmationModal :show="!!deleting" :close="() => (deleting = null)" @confirm="destroy">
            <template #title>Delete partner</template>
            <template #message>Remove {{ deleting?.name }} from the website? This can't be undone.</template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

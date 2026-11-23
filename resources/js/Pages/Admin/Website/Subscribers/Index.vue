<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    subscribers: { type: Array, default: () => [] },
});

const showDelete = ref(false);
const deleteId = ref(null);

const confirmDelete = (id) => {
    deleteId.value = id;
    showDelete.value = true;
};

const destroy = () => {
    router.delete(route('admin.website.subscribers.destroy', deleteId.value), { preserveScroll: true });
    showDelete.value = false;
};

const formatDate = (value) =>
    value ? new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '';
</script>

<template>
    <Head title="Newsletter Subscribers" />

    <AuthenticatedLayout>
        <BackLink :href="route('admin.website.index')">Website content</BackLink>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="page-heading !mb-1">Newsletter Subscribers</h1>
                <p class="text-sm text-text-muted dark:text-text-dark-muted">
                    {{ subscribers.length }} {{ subscribers.length === 1 ? 'person has' : 'people have' }} subscribed.
                </p>
            </div>
            <a
                v-if="subscribers.length"
                :href="route('admin.website.subscribers.export')"
                class="btn-primary inline-flex items-center gap-1"
            >
                <Download class="h-4 w-4" /> Export CSV
            </a>
        </div>

        <div class="card-container overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                        <th class="px-3 py-2">Email</th>
                        <th class="px-3 py-2">Subscribed</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!subscribers.length">
                        <td colspan="3" class="px-3 py-8 text-center text-text-muted">No subscribers yet.</td>
                    </tr>
                    <tr
                        v-for="subscriber in subscribers"
                        :key="subscriber.id"
                        class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark"
                    >
                        <td class="px-3 py-2 font-medium text-mustBlue dark:text-text-inverted">{{ subscriber.email }}</td>
                        <td class="px-3 py-2 text-text-muted">{{ formatDate(subscriber.subscribed_at) }}</td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-end">
                                <button type="button" class="action-icon text-red-500" @click="confirmDelete(subscriber.id)">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ConfirmationModal :show="showDelete" :close="() => (showDelete = false)" @confirm="destroy">
            <template #title>Remove subscriber</template>
            <template #message>This removes the email from your newsletter list.</template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

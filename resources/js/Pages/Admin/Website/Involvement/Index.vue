<script setup>
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    requests: { type: Array, default: () => [] },
});

const showDelete = ref(false);
const deleteId = ref(null);

const confirmDelete = (id) => {
    deleteId.value = id;
    showDelete.value = true;
};

const destroy = () => {
    router.delete(route('admin.website.involvement.destroy', deleteId.value), { preserveScroll: true });
    showDelete.value = false;
};

const setStatus = (request, status) => {
    router.put(route('admin.website.involvement.update', request.id), { status }, { preserveScroll: true });
};

const formatDate = (value) =>
    value ? new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '';

const typeLabel = (type) => (type ? type.charAt(0).toUpperCase() + type.slice(1) : '');
</script>

<template>
    <Head title="Get Involved Requests" />

    <AuthenticatedLayout>
        <Link :href="route('admin.website.index')" class="mb-4 inline-flex items-center gap-1 text-sm text-mustBlue dark:text-text-dark-muted">
            <ArrowLeft class="h-4 w-4" /> Website content
        </Link>
        <div class="mb-6">
            <h1 class="page-heading !mb-1">Get Involved Requests</h1>
            <p class="text-sm text-text-muted dark:text-text-dark-muted">
                People who want to volunteer, mentor, partner, sponsor or donate.
            </p>
        </div>

        <div class="card-container overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                        <th class="px-3 py-2">Person</th>
                        <th class="px-3 py-2">Interest</th>
                        <th class="px-3 py-2">Message</th>
                        <th class="px-3 py-2">Received</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!requests.length">
                        <td colspan="6" class="px-3 py-8 text-center text-text-muted">No requests yet.</td>
                    </tr>
                    <tr
                        v-for="request in requests"
                        :key="request.id"
                        class="border-b border-mustBorder-light align-top last:border-0 dark:border-mustBorder-dark"
                    >
                        <td class="px-3 py-3">
                            <p class="font-medium text-mustBlue dark:text-text-inverted">{{ request.name }}</p>
                            <p v-if="request.email" class="text-xs text-text-muted">{{ request.email }}</p>
                            <p v-if="request.phone" class="text-xs text-text-muted">{{ request.phone }}</p>
                        </td>
                        <td class="px-3 py-3">
                            <span class="rounded-display">{{ typeLabel(request.type) }}</span>
                        </td>
                        <td class="max-w-sm px-3 py-3 text-text-muted dark:text-text-dark-muted">
                            {{ request.message }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 text-text-muted">{{ formatDate(request.created_at) }}</td>
                        <td class="px-3 py-3">
                            <select
                                :value="request.status"
                                class="text-input !py-1 text-xs"
                                @change="setStatus(request, $event.target.value)"
                            >
                                <option value="new">New</option>
                                <option value="contacted">Contacted</option>
                                <option value="accepted">Accepted</option>
                            </select>
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a
                                    v-if="request.email"
                                    :href="`mailto:${request.email}`"
                                    class="text-xs font-medium text-mustGreen hover:underline"
                                >
                                    Email
                                </a>
                                <button type="button" class="action-icon text-red-500" @click="confirmDelete(request.id)">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ConfirmationModal :show="showDelete" :close="() => (showDelete = false)" @confirm="destroy">
            <template #title>Delete request</template>
            <template #message>This permanently removes the request. This cannot be undone.</template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

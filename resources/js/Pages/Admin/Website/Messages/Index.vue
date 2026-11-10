<script setup>
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    messages: { type: Array, default: () => [] },
});

const showDelete = ref(false);
const deleteId = ref(null);

const confirmDelete = (id) => {
    deleteId.value = id;
    showDelete.value = true;
};

const destroy = () => {
    router.delete(route('admin.website.messages.destroy', deleteId.value), { preserveScroll: true });
    showDelete.value = false;
};

const setStatus = (message, status) => {
    router.put(route('admin.website.messages.update', message.id), { status }, { preserveScroll: true });
};

const formatDate = (value) =>
    value ? new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '';
</script>

<template>
    <Head title="Contact Messages" />

    <AuthenticatedLayout>
        <Link :href="route('admin.website.index')" class="mb-4 inline-flex items-center gap-1 text-sm text-mustBlue dark:text-text-dark-muted">
            <ArrowLeft class="h-4 w-4" /> Website content
        </Link>
        <div class="mb-6">
            <h1 class="page-heading !mb-1">Contact Messages</h1>
            <p class="text-sm text-text-muted dark:text-text-dark-muted">
                Messages sent through the public Contact Us form.
            </p>
        </div>

        <div class="card-container overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                        <th class="px-3 py-2">From</th>
                        <th class="px-3 py-2">Message</th>
                        <th class="px-3 py-2">Received</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!messages.length">
                        <td colspan="5" class="px-3 py-8 text-center text-text-muted">No messages yet.</td>
                    </tr>
                    <tr
                        v-for="message in messages"
                        :key="message.id"
                        class="border-b border-mustBorder-light align-top last:border-0 dark:border-mustBorder-dark"
                    >
                        <td class="px-3 py-3">
                            <p class="font-medium text-mustBlue dark:text-text-inverted">{{ message.name }}</p>
                            <p v-if="message.email" class="text-xs text-text-muted">{{ message.email }}</p>
                            <p v-if="message.phone" class="text-xs text-text-muted">{{ message.phone }}</p>
                        </td>
                        <td class="max-w-md px-3 py-3 text-text-muted dark:text-text-dark-muted">
                            {{ message.message }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 text-text-muted">{{ formatDate(message.created_at) }}</td>
                        <td class="px-3 py-3">
                            <select
                                :value="message.status"
                                class="text-input !py-1 text-xs"
                                @change="setStatus(message, $event.target.value)"
                            >
                                <option value="new">New</option>
                                <option value="replied">Replied</option>
                            </select>
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a
                                    v-if="message.email"
                                    :href="`mailto:${message.email}`"
                                    class="text-xs font-medium text-mustGreen hover:underline"
                                >
                                    Reply
                                </a>
                                <button type="button" class="action-icon text-red-500" @click="confirmDelete(message.id)">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ConfirmationModal :show="showDelete" :close="() => (showDelete = false)" @confirm="destroy">
            <template #title>Delete message</template>
            <template #message>This permanently removes the message. This cannot be undone.</template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

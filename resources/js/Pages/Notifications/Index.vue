<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { date } from '@/Helpers';
import { Check, Trash2 } from 'lucide-vue-next';
import PrimaryButton from '@/Components/Common/Buttons/PrimaryButton.vue';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';

const props = defineProps({
    notifications: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const showDeleteModal = ref(false);
const showDeleteAllModal = ref(false);
const notificationToDelete = ref(null);

const openDeleteModal = (notification) => {
    notificationToDelete.value = notification;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    notificationToDelete.value = null;
};

const openDeleteAllModal = () => {
    showDeleteAllModal.value = true;
};

const closeDeleteAllModal = () => {
    showDeleteAllModal.value = false;
};

const markAsRead = (id) => {
    router.post(route('notifications.markAsRead', id), {}, { preserveScroll: true });
};

const deleteNotification = () => {
    if (notificationToDelete.value) {
        router.delete(route('notifications.destroy', notificationToDelete.value.id), { preserveScroll: true });
        closeDeleteModal();
    }
};

const deleteAll = () => {
    router.delete(route('notifications.destroyAll'), { preserveScroll: true });
    closeDeleteAllModal();
};

const hasNotifications = computed(() => props.notifications.length > 0);
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Notifications" />
        <!-- Header -->
        <div class="mb-1 flex items-center justify-between">
            <h1 class="page-heading">Notifications</h1>
            <div class="flex gap-2">
                <PrimaryButton 
                    v-if="hasNotifications" 
                    class="!bg-red-600 hover:!bg-red-700" 
                    @click="openDeleteAllModal"
                >
                    <Trash2 class="mr-1 inline h-4 w-4" /> Delete All
                </PrimaryButton>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="overflow-hidden rounded-lg border border-mustBorder-light bg-background-card dark:border-mustBorder-dark dark:bg-background-dark-card">
            <!-- Notifications -->
            <div>
                <div
                    v-for="notification in notifications"
                    :key="notification.id"
                    class="flex items-center px-4 py-3 transition-colors border-b border-mustBorder-light dark:border-mustBorder-dark cursor-pointer group"
                    :class="[
                        notification.read ? 'bg-background-card dark:bg-background-dark-card' : 'bg-background-soft dark:bg-background-dark-soft',
                        'hover:bg-accent/10 dark:hover:bg-accent/20',
                    ]"
                >
                    <!-- Unread dot -->
                    <span v-if="!notification.read" class="mr-2 h-2 w-2 rounded-full bg-accent flex-shrink-0"></span>
                    <!-- Main content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 justify-between">
                            <span
                                class="truncate text-sm font-medium"
                                :class="notification.read ? 'text-text-muted dark:text-text-dark-muted font-normal' : 'text-text-base dark:text-text-inverted font-bold'"
                            >
                                {{ notification.title }}
                            </span>
                            <span class="flex items-center gap-2 ml-2 flex-shrink-0">
                                <span class="text-xs text-text-muted dark:text-text-dark-muted">
                                    {{ date.smartFormatDate(notification.created_at) }}
                                </span>
                                <button
                                    v-if="!notification.read"
                                    class="action-icon opacity-0 group-hover:opacity-100"
                                    @click.stop="markAsRead(notification.id)"
                                    :title="'Mark as Read'"
                                >
                                    <Check class="h-5 w-5" />
                                </button>
                                <button
                                    class="action-icon opacity-0 group-hover:opacity-100 hover:text-red-500"
                                    @click.stop="openDeleteModal(notification)"
                                    :title="'Delete'"
                                >
                                    <Trash2 class="h-5 w-5" />
                                </button>
                            </span>
                        </div>
                        <span
                            class="block mt-0.5 text-xs"
                            :class="notification.read ? 'text-text-muted dark:text-text-dark-muted' : 'text-text-base dark:text-text-inverted'"
                            v-html="notification.message"
                        ></span>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!hasNotifications" class="px-4 py-8 text-center">
                <p class="text-sm text-text-muted dark:text-text-dark-muted">No notifications found</p>
            </div>
        </div>

        <!-- Delete Single Notification Modal -->
        <ConfirmationModal
            :show="showDeleteModal"
            :close="closeDeleteModal"
            type="danger"
            @confirm="deleteNotification"
        >
            <template #title>
                Delete Notification
            </template>
            <template #message>
                Are you sure you want to delete this notification? This action cannot be undone.
            </template>
            <template #confirmText>
                Delete
            </template>
        </ConfirmationModal>

        <!-- Delete All Notifications Modal -->
        <ConfirmationModal
            :show="showDeleteAllModal"
            :close="closeDeleteAllModal"
            type="danger"
            @confirm="deleteAll"
        >
            <template #title>
                Delete All Notifications
            </template>
            <template #message>
                Are you sure you want to delete all notifications? This action cannot be undone.
            </template>
            <template #confirmText>
                Delete All
            </template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

<style scoped>
@reference '../../../css/app.css';

.action-icon {
    @apply p-1 rounded-full text-text-muted hover:text-accent dark:hover:text-accent-dark transition-colors focus:outline-none focus:ring-2 focus:ring-accent;
}
</style> 
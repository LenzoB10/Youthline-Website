<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import DataTable from '@/Components/Common/Datatable.vue';
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import { date } from '@/Helpers';
import { Eye } from 'lucide-vue-next';

defineProps({
    activityLogs: Object,
});

const selectedLog = ref(null);
const showLogModal = ref(false);

const openLogModal = (log) => {
    selectedLog.value = log;
    showLogModal.value = true;
};

const closeModal = () => {
    showLogModal.value = false;
    setTimeout(() => {
        selectedLog.value = null;
    }, 200);
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Activity Logs" />
        <h1 class="page-heading">Activity Logs</h1>

        <DataTable
            :data="activityLogs"
            :columns="[
                { key: 'causer_name', label: 'User' },
                { key: 'event', label: 'Event' },
                { key: 'subject_type', label: 'Affected Table' },
                { key: 'created_at', label: 'Date' },
            ]"
            :route-name="route().current()"
        >
            <!-- Custom column rendering -->
            <template #column-created_at="{ item }">
                {{ date.formatDateTime(item.created_at) }}
            </template>
            <template #column-causer_name="{ item }">
                {{ item.causer_name ?? 'System' }}
            </template>
            <template #column-subject_type="{ item }">
                {{ item.subject_type ?? '-' }}
            </template>
            <template #actions="{ item }">
                <button
                    v-can="'see audit log details'"
                    @click="openLogModal(item)"
                    class="action-icon"
                >
                    <Eye class="h-[19px] w-[19px]" />
                </button>
            </template>
        </DataTable>

        <Modal :show="showLogModal" @close="closeModal" maxWidth="2xl">
            <div class="p-6">
                <ModalHeader title="Activity Log Details" :close="closeModal" />
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex flex-wrap justify-between gap-2">
                        <p>
                            <strong class="font-semibold text-text-dark-muted"
                                >User:
                            </strong>
                            <span
                                class="text-text-base dark:text-text-inverted"
                                >{{ selectedLog.causer_name ?? 'System' }}</span
                            >
                        </p>
                        <p>
                            <strong class="font-semibold text-text-dark-muted"
                                >Event:</strong
                            >
                            <span
                                class="text-text-base dark:text-text-inverted"
                                >{{ selectedLog.event ?? '-' }}</span
                            >
                        </p>
                        <p v-if="selectedLog.table">
                            <strong class="font-semibold text-text-dark-muted"
                                >Table:
                            </strong>
                            <span
                                class="text-text-base dark:text-text-inverted"
                                >{{ selectedLog.table ?? '-' }}</span
                            >
                        </p>
                        <p>
                            <strong class="font-semibold text-text-dark-muted"
                                >Date:
                            </strong>
                            <span
                                class="text-text-base dark:text-text-inverted"
                                >{{
                                    date.formatDateTime(selectedLog.created_at)
                                }}</span
                            >
                        </p>
                    </div>
                    <p v-if="selectedLog.description != ''">
                        <strong class="font-semibold text-text-dark-muted"
                            >Details</strong
                        >
                    </p>
                    <pre
                        v-if="selectedLog.description != ''"
                        class="overflow-auto rounded bg-gray-100 p-3 text-xs text-gray-800 dark:bg-background-dark-soft dark:text-text-inverted"
                        v-text="selectedLog.description"
                    ></pre>
                    <p v-if="selectedLog.properties != [] || selectedLog.attribute_changes != []">
                        <strong class="font-semibold text-text-dark-muted"
                            >Changes</strong
                        >
                    </p>
                    <pre
                        v-if="selectedLog.properties != [] || selectedLog.attribute_changes != []"
                        class="overflow-auto rounded bg-gray-100 p-3 text-xs text-gray-800 dark:bg-background-dark-soft dark:text-text-inverted"
                        v-text="JSON.stringify({ ...selectedLog.properties, ...selectedLog.attribute_changes }, null, 2)"
                    ></pre>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

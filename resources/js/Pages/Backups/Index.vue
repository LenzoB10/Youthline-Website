<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '@/Components/Common/Datatable.vue';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import { date } from '@/Helpers';
import { CalendarClock, Database, Download, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    backups: Array,
    settings: Object,
});

const settingsForm = useForm({
    backup_enabled: props.settings?.backup_enabled ?? true,
    backup_time: (props.settings?.backup_time ?? '01:00').slice(0, 5),
    backup_retention_days: props.settings?.backup_retention_days ?? 30,
    monthly_backup_enabled: props.settings?.monthly_backup_enabled ?? true,
    monthly_backup_retention_months: props.settings?.monthly_backup_retention_months ?? 12,
});

const saveSettings = () => {
    settingsForm.put(route('admin.backups.update-settings'), {
        preserveScroll: true,
    });
};

const creating = ref(false);

const createBackup = () => {
    if (creating.value) return;

    creating.value = true;

    router.post(route('admin.backups.store'), {}, {
        onFinish: () => {
            creating.value = false;
        },
    });
};

const formatSize = (bytes) => {
    if (!bytes && bytes !== 0) return '-';

    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let size = bytes;
    let unit = 0;

    while (size >= 1024 && unit < units.length - 1) {
        size /= 1024;
        unit++;
    }

    return `${size.toFixed(unit === 0 ? 0 : 2)} ${units[unit]}`;
};

const showDeleteModal = ref(false);
const deleteFilename = ref(null);

const confirmDelete = (filename) => {
    deleteFilename.value = filename;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deleteFilename.value = null;
};

const deleteBackup = () => {
    router.delete(route('admin.backups.destroy', deleteFilename.value), {
        onSuccess: () => {
            closeDeleteModal();
        },
    });
};
</script>

<template>
    <Head title="Database Backups" />

    <AuthenticatedLayout>
        <div class="flex items-center justify-between">
            <h1 class="page-heading">Database Backups</h1>
            <button
                v-can="'create backup'"
                @click="createBackup"
                class="btn-primary-sm"
                :disabled="creating"
            >
                <Database class="mr-1 h-4 w-4" />
                {{ creating ? 'Creating...' : 'Create Backup' }}
            </button>
        </div>

        <div
            v-can="'update backup settings'"
            class="card-container mb-4"
        >
            <div class="mb-3 flex items-center gap-2">
                <CalendarClock class="h-5 w-5 text-mustGreen" />
                <h2 class="text-base font-semibold text-text-base dark:text-text-inverted">
                    Automatic Backup Settings
                </h2>
            </div>

            <form @submit.prevent="saveSettings">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <div class="flex items-center gap-2 sm:flex-1">
                        <Checkbox v-model="settingsForm.backup_enabled" />
                        <InputLabel value="Enable automatic backups" />
                    </div>

                    <div class="sm:w-48">
                        <InputLabel for="backup_time" value="Backup time (24h)" />
                        <TextInput
                            id="backup_time"
                            type="time"
                            class="mt-1 block w-full"
                            v-model="settingsForm.backup_time"
                        />
                        <InputError class="mt-2" :message="settingsForm.errors.backup_time" />
                    </div>

                    <div class="sm:w-48">
                        <InputLabel for="backup_retention_days" value="Delete backups older than (days)" />
                        <TextInput
                            id="backup_retention_days"
                            type="number"
                            min="1"
                            max="365"
                            class="mt-1 block w-full"
                            v-model="settingsForm.backup_retention_days"
                        />
                        <InputError class="mt-2" :message="settingsForm.errors.backup_retention_days" />
                    </div>

                    <button
                        type="submit"
                        class="btn-primary-sm"
                        :disabled="settingsForm.processing"
                    >
                        {{ settingsForm.processing ? 'Saving...' : 'Save Settings' }}
                    </button>
                </div>

                <div class="mt-4 flex flex-col gap-4 border-t border-border-base pt-4 sm:flex-row sm:items-end">
                    <div class="flex items-center gap-2 sm:flex-1">
                        <Checkbox v-model="settingsForm.monthly_backup_enabled" />
                        <InputLabel value="Enable monthly backup (1st of month)" />
                    </div>

                    <div class="sm:w-48">
                        <InputLabel
                            for="monthly_backup_retention_months"
                            value="Keep monthly backups for (months)"
                        />
                        <TextInput
                            id="monthly_backup_retention_months"
                            type="number"
                            min="1"
                            max="120"
                            class="mt-1 block w-full"
                            v-model="settingsForm.monthly_backup_retention_months"
                        />
                        <InputError
                            class="mt-2"
                            :message="settingsForm.errors.monthly_backup_retention_months"
                        />
                    </div>
                </div>

                <p class="mt-3 text-xs text-text-muted">
                    Backups run automatically every day at the configured time, plus a
                    monthly backup on the 1st of each month. Backups older than their
                    retention period are deleted automatically.
                </p>
            </form>
        </div>

        <DataTable
            :data="backups"
            :columns="[
                { key: 'filename', label: 'Filename' },
                { key: 'type', label: 'Type' },
                { key: 'size', label: 'Size' },
                { key: 'created_at', label: 'Created' },
            ]"
            :route-name="'admin.backups.index'"
        >
            <template #column-type="{ item }">
                <span
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="item.type === 'monthly'
                        ? 'bg-yl-gold/15 text-amber-800 dark:text-yl-gold'
                        : 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300'"
                >
                    {{ item.type === 'monthly' ? 'Monthly' : 'Daily' }}
                </span>
            </template>
            <template #column-size="{ item }">
                {{ formatSize(item.size) }}
            </template>
            <template #column-created_at="{ item }">
                {{ date.formatDateTime(item.created_at) }}
            </template>

            <template #actions="{ item }">
                <div class="flex justify-end gap-3">
                    <a
                        v-can="'download backup'"
                        :href="route('admin.backups.download', item.id)"
                        class="action-icon"
                        title="Download backup"
                    >
                        <Download class="h-4 w-4" />
                    </a>

                    <button
                        v-can="'delete backup'"
                        @click="confirmDelete(item.id)"
                        class="text-red-500 transition duration-300 hover:text-red-700"
                        title="Delete backup"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </template>
        </DataTable>

        <ConfirmationModal
            :show="showDeleteModal"
            :close="closeDeleteModal"
            @confirm="deleteBackup"
        >
            <template #message>
                Are you sure you want to delete this backup? This action cannot
                be undone.
            </template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

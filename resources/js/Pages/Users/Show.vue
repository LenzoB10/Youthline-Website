<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import BackButton from '@/Components/Common/Buttons/BackButton.vue';
import UserDetailsItem from '@/Pages/Users/Partials/UserDetailsItem.vue';
import PermissionsList from '@/Pages/Roles/Partials/PermissionsList.vue';
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import { ref, computed } from 'vue';
import DataTable from '@/Components/Common/Datatable.vue';
import { date } from '@/Helpers';

const props = defineProps({
    user: Object,
    roles: Array,
    all_permissions: Array,
    user_permissions_via_role: Array,
    user_direct_permissions: Array,
    logs: Array,
});

const form = useForm({
    role: '',
    permissions: [],
});

const showEditRoleModal = ref(false);
const search = ref('');
const showEditPermissionsModal = ref(false);
const showConfirmModal = ref(false);
const selectedId = ref(null);

const openConfirmModal = (id) => {
    selectedId.value = id;
    showConfirmModal.value = true;
};

const closeConfirmModal = () => {
    showConfirmModal.value = false;
    setTimeout(() => {
        selectedId.value = null;
    }, 200);
};

const allUserPermissions = computed(() => {
    return [
        ...props.user_permissions_via_role,
        ...props.user_direct_permissions,
    ];
});

const openEditRoleModal = () => {
    form.role = props.user.roles[0]?.name;
    showEditRoleModal.value = true;
};

const closeEditRoleModal = () => {
    showEditRoleModal.value = false;
};

const openEditPermissionsModal = () => {
    form.permissions = props.user_permissions_via_role.map((p) => p.id);
    search.value = '';
    showEditPermissionsModal.value = true;
};

const closeEditPermissionsModal = () => {
    form.permissions = [];
    search.value = '';
    showEditPermissionsModal.value = false;
};

const filteredPermissions = computed(() => {
    return props.all_permissions.filter((permission) =>
        permission.name.toLowerCase().includes(search.value.toLowerCase()),
    );
});

const updateRole = () => {
    form.put(route('admin.user.update-role', props.user.id), {
        onSuccess: () => {
            form.reset();
            showEditRoleModal.value = false;
        },
    });
};

const updatePermissions = () => {
    form.put(route('admin.user.update-permissions', props.user.id), {
        onSuccess: () => {
            form.reset();
            showEditPermissionsModal.value = false;
        },
    });
};

const confirmUserStatusAction = () => {
    if (props.user.status == 'Active') {
        ban();
    } else {
        activate();
    }
    closeConfirmModal();
};

const activate = () => {
    if (props.user.status == 'Active') {
        return;
    }
    form.put(route('admin.user.activate', props.user.id), {
        onSuccess: () => {
            form.reset();
        },
    });
};

const ban = () => {
    if (props.user.status == 'Banned') {
        return;
    }
    form.put(route('admin.user.ban', props.user.id), {
        onSuccess: () => {},
    });
};

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
        <Head title="User Details" />
        <div class="flex items-center justify-between">
            <h1 class="page-heading">User Details</h1>
            <BackButton routeName="admin.users.index" />
        </div>

        <div class="card-container">
            <div class="grid grid-cols-1 items-center gap-4 md:grid-cols-3">
                <UserDetailsItem :title="user.email" :icon="'email'" />
                <UserDetailsItem :title="user.roles[0]?.name" :icon="'role'" />
                <UserDetailsItem :title="user.status" :icon="'status'" />
            </div>

            <div class="mt-4">
                <h5 class="page-heading-2">Permissions</h5>
                <PermissionsList :permissions="allUserPermissions" />
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-end gap-2">
                <button
                    v-can="'add user permissions'"
                    class="btn-outline-sm"
                    @click="openEditPermissionsModal"
                >
                    Update Permissions
                </button>
                <button
                    v-can="'update user role'"
                    class="btn-primary-sm"
                    @click="openEditRoleModal"
                >
                    Update Role
                </button>

                <button
                    v-can="'suspend user'"
                    @click="openConfirmModal(user.id)"
                    :class="
                        'rounded-md px-2 py-1 text-sm font-medium text-white transition duration-300 ' +
                        (user.status == 'active'
                            ? 'bg-red-600 hover:bg-red-700'
                            : 'bg-mustBlue hover:bg-green-700')
                    "
                >
                    {{ user.status == 'active' ? 'DEACTIVATE' : 'ACTIVATE' }}
                </button>
            </div>
        </div>

        <!-- User activity logs -->
        <div v-can="'see user audit logs'" class="mt-4">
            <h1 class="page-heading-2">User Activity Logs</h1>
            <div class="card-container">
                <DataTable
                    :data="logs"
                    :columns="[
                        { key: 'causer_name', label: 'User' },
                        { key: 'event', label: 'Event' },
                        { key: 'subject_type', label: 'Affected Table' },
                        { key: 'created_at', label: 'Date' },
                    ]"
                    :serverSide="false"
                >
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
                            class="mr-3 text-mustGreen hover:text-yl-green-dark"
                        >
                            Show
                        </button>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- Edit Role Modal -->
        <Modal
            :show="showEditRoleModal"
            :closeOnClickOutside="false"
            @close="closeEditRoleModal"
            maxWidth="sm"
        >
            <div class="p-6">
                <ModalHeader
                    title="Update User Role"
                    :close="closeEditRoleModal"
                />
                <form @submit.prevent="updateRole">
                    <div class="mt-2">
                        <InputLabel for="role" value="Role" />
                        <SelectInput
                            v-model="form.role"
                            required
                            :options="
                                roles.map((role) => ({
                                    value: role.name,
                                    label: role.name,
                                }))
                            "
                            placeholder="Select a role"
                        />
                        <InputError :message="form.errors.role" />
                    </div>
                    <div class="mt-4 flex justify-end space-x-2">
                        <button
                            type="button"
                            class="btn-outline-sm"
                            @click="closeEditRoleModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="btn-primary-sm"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Updating...' : 'Update' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Permissions Modal -->
        <Modal
            :show="showEditPermissionsModal"
            :closeOnClickOutside="false"
            @close="closeEditPermissionsModal"
            maxWidth="xl"
        >
            <div class="p-6">
                <ModalHeader
                    title="Update User Permissions"
                    :close="closeEditPermissionsModal"
                />
                <form @submit.prevent="updatePermissions">
                    <p class="mb-3 text-sm text-text-muted">
                        Note that you cannot remove a permission if its assigned
                        from the user role.
                    </p>
                    <div class="mt-2">
                        <!-- Search Input -->
                        <div class="mt-2">
                            <TextInput
                                id="search"
                                type="search"
                                class="mt-1 block w-full"
                                v-model="search"
                                placeholder="Search permissions..."
                            />
                        </div>

                        <!-- Permissions List -->
                        <div class="mt-4">
                            <div
                                class="grid max-h-60 grid-cols-2 gap-3 overflow-y-auto rounded-md border border-gray-200 p-2 dark:border-gray-600 md:grid-cols-3"
                            >
                                <label
                                    v-for="permission in filteredPermissions"
                                    :key="permission.id"
                                    class="flex cursor-pointer items-center space-x-2 rounded-md p-2 hover:bg-gray-100 dark:hover:bg-accent/10"
                                >
                                    <Checkbox
                                        v-model:modelValue="form.permissions"
                                        :value="permission.id"
                                        :checked="
                                            allUserPermissions
                                                .map((p) => p.id)
                                                .includes(permission.id)
                                        "
                                        :disabled="
                                            user_permissions_via_role
                                                .map((p) => p.id)
                                                .includes(permission.id)
                                        "
                                    />
                                    <span
                                        class="dark:text-mustText-neutral text-gray-500"
                                        >{{ permission.name }}</span
                                    >
                                </label>
                            </div>
                            <div
                                v-if="form.errors.permissions"
                                class="mt-1 text-sm text-red-500"
                            >
                                {{ form.errors.permissions }}
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end space-x-2">
                        <button
                            type="button"
                            class="btn-outline-sm"
                            @click="closeEditPermissionsModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="btn-primary-sm"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Updating...' : 'Update' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Activity Log Modal -->
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

        <!-- Confirm Modal -->
        <ConfirmationModal
            :show="showConfirmModal"
            :close="closeConfirmModal"
            @confirm="confirmUserStatusAction"
            :type="user.status == 'Active' ? 'danger' : 'success'"
        >
            <template #title>
                {{
                    user.status == 'Active'
                        ? 'Deactivate User'
                        : 'Activate User'
                }}
            </template>
            <template #message>
                {{
                    user.status == 'Active'
                        ? 'Are you sure you want to deactivate this user?'
                        : 'Are you sure you want to activate this user?'
                }}
            </template>
            <template #confirmText>
                {{ user.status == 'Active' ? 'DEACTIVATE' : 'ACTIVATE' }}
            </template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

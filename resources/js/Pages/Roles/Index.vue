<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import PermissionsList from './Partials/PermissionsList.vue';
import RoleItem from './Partials/RoleItem.vue';
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    roles: {
        type: Array,
        required: true,
    },
    permissions: {
        type: Array,
        required: true,
    },
});

defineEmits(['editRole', 'confirmDelete']);

const confirmDelete = () => {
    router.delete(route('admin.roles.destroy', deleteId.value), {
        preserveScroll: true,
    });
    closeConfirmDeleteModal();
};

const selectedRole = ref(null);
const showEditRoleModal = ref(false);
const showDeleteModal = ref(false);
const deleteId = ref(null);

const openCreateRoleModal = () => {
    showEditRoleModal.value = true;
};

const openEditRoleModal = (role) => {
    selectedRole.value = role;
    (form.id = role.id), (form.name = role.name);
    form.permissions = role.permissions.map((permission) => permission.id);
    showEditRoleModal.value = true;
};

const closeEditRoleModal = () => {
    showEditRoleModal.value = false;
    form.reset();

    setTimeout(() => {
        selectedRole.value = null;
    }, 200);
};

const openConfirmDeleteModal = (id) => {
    deleteId.value = id;
    showDeleteModal.value = true;
};

const closeConfirmDeleteModal = () => {
    showDeleteModal.value = false;
    deleteId.value = null;
};

const search = ref('');

const form = useForm({
    id: '',
    name: '',
    permissions: [],
});

const filteredPermissions = computed(() => {
    return props.permissions.filter((permission) =>
        permission.name.toLowerCase().includes(search.value.toLowerCase()),
    );
});

const submit = () => {
    if (form.id) {
        form.put(route('admin.roles.update', selectedRole.value.id), {
            data: {
                name: form.name,
                permissions: form.permissions,
            },
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                closeEditRoleModal();
            },
        });
    } else {
        form.post(route('admin.roles.store'), {
            data: {
                name: form.name,
                permissions: form.permissions,
            },
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                closeEditRoleModal();
            },
        });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Roles" />
        <div class="flex items-center justify-between">
            <h1 class="page-heading">Roles</h1>
            <button
                v-can="'add role'"
                @click="openCreateRoleModal"
                class="btn-primary-sm"
            >
                Create Role
            </button>
        </div>

        <!-- Roles List -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <RoleItem
                :roles="roles"
                @editRole="openEditRoleModal"
                @confirmDelete="openConfirmDeleteModal"
            />
        </div>

        <!-- Permissions List -->
        <h2 class="page-heading mt-4">All Permissions</h2>
        <div class="card-container">
            <PermissionsList :permissions="permissions" />
        </div>

        <!-- Role Modal -->
        <Modal
            :show="showEditRoleModal"
            :closeOnClickOutside="false"
            @close="closeEditRoleModal"
            maxWidth="2xl"
        >
            <div class="p-6">
                <ModalHeader
                    :title="form.id ? 'Edit Role' : 'Create Role'"
                    :close="closeEditRoleModal"
                />
                <form @submit.prevent="submit">
                    <div class="mt-2">
                        <div class="mb-3">
                            <InputLabel for="name" value="Name" />
                            <TextInput
                                id="name"
                                type="name"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                required
                                autofocus
                                autocomplete="name"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.name"
                            />
                        </div>

                        <InputLabel for="permissions" value="Permissions" />
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

                            <!-- Action Buttons -->
                            <div class="mt-4 flex justify-end space-x-2">
                                <button
                                    type="button"
                                    @click="closeEditRoleModal"
                                    class="btn-outline-sm"
                                >
                                    Close
                                </button>
                                <button
                                    type="submit"
                                    class="btn-primary-sm"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing"
                                        >Saving...</span
                                    >
                                    <span v-else>Save</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </Modal>

        <ConfirmationModal
            :show="showDeleteModal"
            :close="closeConfirmDeleteModal"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>

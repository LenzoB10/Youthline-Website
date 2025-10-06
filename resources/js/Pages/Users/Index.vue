<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import DataTable from '@/Components/Common/Datatable.vue';
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import { date } from '@/Helpers';
import { ref } from 'vue';
import { Eye, SquarePen, Trash2 } from 'lucide-vue-next';

defineProps({
    users: Object,
    filters: Object,
    roles: Array,
});

const editUser = (user) => {
    form.name = user.name;
    form.email = user.email;
    form.account_type = user.account_type;
    form.role = user.role;
    form.id = user.id;

    showCreateUserModal.value = true;
};

const deleteUser = (user) => {
    router.delete(route('admin.users.destroy', user));
    showDeleteModal.value = false;
};

const openCreateUserModal = () => {
    form.reset();
    showCreateUserModal.value = true;
};

const closeCreateUserModal = () => {
    showCreateUserModal.value = false;
    form.reset();
};

const showCreateUserModal = ref(false);

const showDeleteModal = ref(false);
const deleteId = ref(null);

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deleteId.value = null;
};

const confirmDelete = (id) => {
    deleteId.value = id;
    showDeleteModal.value = true;
};

const form = useForm({
    name: '',
    email: '',
    account_type: '',
    role: null,
    id: null,
});

const submit = () => {
    if (form.id) {
        form.put(route('admin.users.update', form.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                closeCreateUserModal();
            },
        });
    } else {
        form.post(route('admin.users.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                closeCreateUserModal();
            },
        });
    }
};
</script>

<template>
    <Head title="Users" />

    <AuthenticatedLayout>
        <div class="flex items-center justify-between">
            <h1 class="page-heading">Users</h1>
            <button
                v-can="'add user'"
                @click="openCreateUserModal"
                class="btn-primary-sm"
            >
                Create User
            </button>
        </div>

        <DataTable
            :data="users"
            :columns="[
                { key: 'name', label: 'Name' },
                { key: 'email', label: 'Email' },
                { key: 'status', label: 'Status' },
                { key: 'created_at', label: 'Joined' },
            ]"
            :route-name="route().current()"
            :filters-config="{
                status: {
                    component: 'select',
                    label: 'Status',
                    options: [
                        { value: 'active', label: 'Active' },
                        { value: 'inactive', label: 'Inactive' },
                    ],
                    placeholder: 'Filter by status',
                }
            }"
        >
            <!-- Custom column rendering -->
            <template #column-created_at="{ item }">
                {{ date.formatDate(item.created_at) }}
            </template>
            <template #column-status="{ item }">
                <span
                    :class="
                        'rounded-full px-2 py-1 text-xs font-medium ' +
                        (item.status == 'active'
                            ? 'bg-green-100 text-green-800'
                            : 'bg-red-100 text-red-800')
                    "
                >
                    {{ item.status }}
                </span>
            </template>

            <!-- Actions column -->
            <template #actions="{ item }">
                <div class="flex gap-3">
                    <Link
                        v-can="'see user details'"
                        :href="route('admin.users.show', item.id)"
                        class="action-icon"
                    >
                        <Eye class="h-[19px] w-[19px]" />
                    </Link>

                    <button
                        v-can="'update user'"
                        @click="editUser(item)"
                        class="action-icon"
                    >
                        <SquarePen class="h-4 w-4" />
                    </button>

                    <button
                        v-can="'delete user'"
                        @click="confirmDelete(item.id)"
                        class="text-red-500 transition duration-300 hover:text-red-700"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Create|Edit User Modal -->
        <Modal
            maxWidth="lg"
            :show="showCreateUserModal"
            :closeOnClickOutside="false"
            @close="closeCreateUserModal"
        >
            <div class="p-6">
                <ModalHeader
                    title="Create User"
                    :close="closeCreateUserModal"
                />
                <p class="mb-3 text-sm text-text-muted" v-if="!form.id">
                    Password will be generated automatically and sent to the
                    user's email.
                </p>
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
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.name"
                            />
                        </div>
                        <div class="mb-3">
                            <InputLabel for="email" value="Email" />

                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.email"
                                required
                                autocomplete="email"
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.email"
                            />
                        </div>
                        <div class="flex space-x-2">
                            
                            <div class="mb-3 w-1/2">
                                <InputLabel for="role_id" value="Role" />

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

                                <InputError
                                    class="mt-2"
                                    :message="form.errors.role"
                                />
                            </div>
                        </div>
                        <!-- Action Buttons -->
                        <div class="mt-4 flex justify-end space-x-2">
                            <button
                                type="button"
                                @click="closeCreateUserModal"
                                class="btn-outline-sm"
                            >
                                Close
                            </button>
                            <button
                                type="submit"
                                class="btn-primary-sm"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Saving...</span>
                                <span v-else>Save</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </Modal>

        <ConfirmationModal
            :show="showDeleteModal"
            :close="closeDeleteModal"
            @confirm="deleteUser(deleteId)"
        />
    </AuthenticatedLayout>
</template>

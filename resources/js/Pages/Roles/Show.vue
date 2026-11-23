<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import BackButton from '@/Components/Common/Buttons/BackButton.vue';
import PermissionsList from './Partials/PermissionsList.vue';
import NoRecordFound from '@/Components/Common/NoRecordFound.vue';
import DataTable from '@/Components/Common/Datatable.vue';
defineProps({
    role: Object,
});

const columns = [
    {
        label: 'Name',
        key: 'name',
    },
    {
        label: 'Email',
        key: 'email',
    },
    {
        label: 'Account Type',
        key: 'account_type',
    },
    {
        label: 'Status',
        key: 'status',
    },
];
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Role Details" />
        <div class="flex items-center justify-between">
            <h1 class="page-heading">Role Details : {{ role.name }}</h1>
            <BackButton routeName="admin.roles.index" />
        </div>

        <h2 class="page-heading-2">Role Permissions</h2>
        <div class="card-container">
            <PermissionsList :permissions="role.permissions" />
        </div>

        <h2 class="page-heading-2 mt-2">Role Users</h2>
        <div class="card-container">
            <NoRecordFound
                v-if="role.users.length === 0"
                message="No user is assigned to this role"
                description="You can assign users to this role by editing their profile and adding the role to their roles list"
            />
            <div v-else>
                <div v-if="role.users.length < 15">
                    <div
                        v-for="user in role.users"
                        :key="user.id"
                        class="flex flex-wrap gap-2"
                    >
                        <Link
                            :href="route('admin.users.show', user.id)"
                            class="rounded-display"
                        >
                            {{ user.name }}
                        </Link>
                    </div>
                </div>
                <div v-else>
                    <DataTable
                        :data="role.users"
                        :columns="columns"
                        :serverSide="false"
                    >
                        <template #column-status="{ item }">
                            <span
                                :class="
                                    'rounded-full px-2 py-1 text-xs font-medium ' +
                                    (item.status == 'Active'
                                        ? 'bg-mustGreen/15 text-yl-green-deep dark:text-accent-light'
                                        : 'bg-gray-200 text-gray-700 dark:bg-gray-500/20 dark:text-gray-300')
                                "
                            >
                                {{ item.status }}
                            </span>
                        </template>
                        <template #actions="{ item }">
                            <Link
                                v-can="'see user details'"
                                :href="route('admin.users.show', item.id)"
                                class="mr-3 text-mustGreen hover:text-yl-green-dark"
                            >
                                Show
                            </Link>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

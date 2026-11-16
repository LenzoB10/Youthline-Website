<script setup>
import DataTable from '@/Components/Common/Datatable.vue';
import StatusBadge from '@/Components/Students/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpCircle, Eye, UserPlus } from 'lucide-vue-next';

defineProps({
    students: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    levels: { type: Array, default: () => [] },
    programs: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Students" />

    <AuthenticatedLayout>
        <div class="mb-1 flex flex-wrap items-center justify-between gap-3">
            <h1 class="page-heading !mb-0">Students</h1>
            <div class="flex flex-wrap gap-2">
                <Link v-can="'change student status'" :href="route('students.promote.index')" class="btn-outline-sm inline-flex items-center gap-1">
                    <ArrowUpCircle class="h-4 w-4" /> Promote students
                </Link>
                <Link v-can="'add student'" :href="route('students.create')" class="btn-primary-sm inline-flex items-center gap-1">
                    <UserPlus class="h-4 w-4" /> Add student
                </Link>
            </div>
        </div>
        <p class="mb-4 text-sm text-text-muted dark:text-text-dark-muted">
            Search by student or guardian name, or phone number. Level shows this academic year's level.
        </p>

        <DataTable
            :data="students"
            :columns="[
                { key: 'last_name', label: 'Student' },
                { key: 'status', label: 'Status' },
                { key: 'level', label: 'Level' },
                { key: 'institution', label: 'School / university' },
                { key: 'created_at', label: 'Registered' },
            ]"
            :route-name="route().current()"
            :filters="filters"
            sort-by="last_name"
            sort-direction="asc"
            :filters-config="{
                status: { component: 'select', label: 'Status', options: statuses, placeholder: 'All statuses' },
                level: { component: 'select', label: 'Level', options: levels, placeholder: 'All levels' },
                program: { component: 'select', label: 'Program', options: programs, placeholder: 'All programs' },
            }"
        >
            <template #column-last_name="{ item }">
                <Link :href="route('students.show', item.id)" class="font-medium text-mustBlue hover:underline dark:text-text-inverted">
                    {{ item.full_name }}
                </Link>
                <p v-if="item.guardian_name" class="text-xs text-text-muted">Guardian: {{ item.guardian_name }}</p>
            </template>
            <template #column-status="{ item }">
                <StatusBadge :status="item.status" :label="item.status_label" />
            </template>
            <template #column-level="{ item }">{{ item.level ?? '—' }}</template>
            <template #column-institution="{ item }">{{ item.institution ?? '—' }}</template>
            <template #column-created_at="{ item }">
                {{ item.created_at ? new Date(item.created_at).toLocaleDateString() : '—' }}
            </template>

            <template #actions="{ item }">
                <Link :href="route('students.show', item.id)" class="action-icon" :aria-label="`Open ${item.full_name}`">
                    <Eye class="h-[19px] w-[19px]" />
                </Link>
            </template>
        </DataTable>
    </AuthenticatedLayout>
</template>

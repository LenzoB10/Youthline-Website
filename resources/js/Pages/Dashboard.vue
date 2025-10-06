<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import DashboardCard from '@/Components/Dashboard.vue';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="grid gap-2 sm:grid-cols-2 md:gap-6 lg:grid-cols-4">
            <DashboardCard
                title="Total Applications"
                value="1,245"
                icon="users"
            />
            <DashboardCard title="New This Week" value="156" icon="users" />
            <DashboardCard title="Under Review" value="432" icon="hourglass" />
            <DashboardCard title="Admission Rate" value="35%" icon="percent" />
        </div>
    </AuthenticatedLayout>
</template>

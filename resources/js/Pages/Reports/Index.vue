<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { FileSpreadsheet, FileText, HeartHandshake, Lock } from 'lucide-vue-next';

defineProps({
    reports: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Reports" />

    <AuthenticatedLayout>
        <h1 class="page-heading !mb-1">Reports and exports</h1>
        <p class="mb-6 text-sm text-text-muted dark:text-text-dark-muted">Download live data as an Excel workbook or a PDF. Files are built the moment you click.</p>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article v-for="r in reports" :key="r.key" class="card-container !mb-0 flex flex-col">
                <h2 class="flex items-center gap-2 font-semibold text-mustBlue dark:text-text-inverted">
                    {{ r.title }}
                    <span v-if="r.admin_only" class="inline-flex items-center gap-1 rounded-full bg-mustBlue/10 px-2 py-0.5 text-xs font-normal text-mustBlue dark:text-text-inverted"><Lock class="h-3 w-3" /> Admin</span>
                </h2>
                <p class="mt-1 flex-1 text-sm text-text-muted">{{ r.description }}</p>
                <div class="mt-4 flex gap-2">
                    <a :href="route('reports.export', { report: r.key, format: 'xlsx' })" class="btn-primary-sm inline-flex items-center gap-1"><FileSpreadsheet class="h-4 w-4" /> Excel</a>
                    <a :href="route('reports.export', { report: r.key, format: 'pdf' })" class="btn-outline-sm inline-flex items-center gap-1"><FileText class="h-4 w-4" /> PDF</a>
                </div>
            </article>

            <article v-if="$page.props.auth.permissions?.includes('manage sponsors')" class="card-container !mb-0 flex flex-col">
                <h2 class="flex items-center gap-2 font-semibold text-mustBlue dark:text-text-inverted"><HeartHandshake class="h-4 w-4" /> Sponsor progress reports</h2>
                <p class="mt-1 flex-1 text-sm text-text-muted">One PDF per sponsor: each student's level, results, fees and support, plus your progress update.</p>
                <Link :href="route('sponsors.index')" class="btn-outline-sm mt-4 self-start">Open sponsors</Link>
            </article>
        </div>
    </AuthenticatedLayout>
</template>

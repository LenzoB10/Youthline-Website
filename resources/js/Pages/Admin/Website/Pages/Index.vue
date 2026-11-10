<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, SquarePen } from 'lucide-vue-next';

defineProps({
    pages: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Pages" />

    <AuthenticatedLayout>
        <Link :href="route('admin.website.index')" class="mb-4 inline-flex items-center gap-1 text-sm text-mustBlue dark:text-text-dark-muted">
            <ArrowLeft class="h-4 w-4" /> Website content
        </Link>
        <h1 class="page-heading">Pages</h1>
        <p class="mb-6 text-sm text-text-muted dark:text-text-dark-muted">
            Edit the text shown on each public page. Keys like <strong>vision</strong>,
            <strong>mission</strong> and <strong>commitment</strong> appear on the About page.
        </p>

        <div class="card-container overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                        <th class="px-3 py-2">Key</th>
                        <th class="px-3 py-2">Title</th>
                        <th class="px-3 py-2">Summary</th>
                        <th class="px-3 py-2 text-right">Edit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="page in pages"
                        :key="page.id"
                        class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark"
                    >
                        <td class="px-3 py-2 font-mono text-xs text-mustGreen">{{ page.key }}</td>
                        <td class="px-3 py-2 font-medium text-mustBlue dark:text-text-inverted">{{ page.title }}</td>
                        <td class="max-w-md truncate px-3 py-2 text-text-muted">{{ page.summary }}</td>
                        <td class="px-3 py-2 text-right">
                            <Link :href="route('admin.website.pages.edit', page.id)" class="action-icon inline-flex">
                                <SquarePen class="h-4 w-4" />
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AuthenticatedLayout>
</template>

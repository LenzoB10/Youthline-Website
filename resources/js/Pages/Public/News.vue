<script setup>
import NewsCard from '@/Components/Public/NewsCard.vue';
import PageHeader from '@/Components/Public/PageHeader.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    page: { type: Object, default: null },
    posts: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="News and Stories" />

    <PublicLayout>
        <PageHeader
            eyebrow="Updates"
            :title="page?.title || 'News and Stories'"
            :subtitle="page?.summary || ''"
        />

        <section class="py-16">
            <div class="yl-container">
                <div v-if="posts.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <NewsCard v-for="post in posts" :key="post.id" :post="post" />
                </div>
                <div v-else class="rounded-2xl border border-dashed border-gray-200 py-20 text-center">
                    <p class="text-yl-ink/60">No stories have been published yet. Please check back soon.</p>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

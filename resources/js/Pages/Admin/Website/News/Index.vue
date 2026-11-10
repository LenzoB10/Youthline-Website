<script setup>
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Eye, EyeOff, Plus, SquarePen, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    posts: { type: Array, default: () => [] },
});

const categoryLabel = {
    news: 'News',
    success_story: 'Success story',
    activity: 'Activity',
};

const togglePublish = (post) => {
    router.put(route('admin.website.news.publish', post.id), {}, { preserveScroll: true });
};

const showDelete = ref(false);
const deleteId = ref(null);

const confirmDelete = (id) => {
    deleteId.value = id;
    showDelete.value = true;
};

const destroy = () => {
    router.delete(route('admin.website.news.destroy', deleteId.value), { preserveScroll: true });
    showDelete.value = false;
    deleteId.value = null;
};
</script>

<template>
    <Head title="News & Stories" />

    <AuthenticatedLayout>
        <Link :href="route('admin.website.index')" class="mb-4 inline-flex items-center gap-1 text-sm text-mustBlue dark:text-text-dark-muted">
            <ArrowLeft class="h-4 w-4" /> Website content
        </Link>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="page-heading !mb-0">News &amp; Stories</h1>
            <Link :href="route('admin.website.news.create')" class="btn-primary inline-flex items-center gap-1">
                <Plus class="h-4 w-4" /> New post
            </Link>
        </div>

        <div class="card-container overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                        <th class="px-3 py-2">Title</th>
                        <th class="px-3 py-2">Category</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!posts.length">
                        <td colspan="4" class="px-3 py-8 text-center text-text-muted">No posts yet.</td>
                    </tr>
                    <tr
                        v-for="post in posts"
                        :key="post.id"
                        class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark"
                    >
                        <td class="px-3 py-2 font-medium text-mustBlue dark:text-text-inverted">{{ post.title }}</td>
                        <td class="px-3 py-2 text-text-muted">{{ categoryLabel[post.category] ?? post.category }}</td>
                        <td class="px-3 py-2">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                :class="post.is_published ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'"
                            >
                                {{ post.is_published ? 'Published' : 'Draft' }}
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    class="action-icon"
                                    :title="post.is_published ? 'Unpublish' : 'Publish'"
                                    @click="togglePublish(post)"
                                >
                                    <EyeOff v-if="post.is_published" class="h-4 w-4" />
                                    <Eye v-else class="h-4 w-4" />
                                </button>
                                <Link :href="route('admin.website.news.edit', post.id)" class="action-icon">
                                    <SquarePen class="h-4 w-4" />
                                </Link>
                                <button type="button" class="action-icon text-red-500" @click="confirmDelete(post.id)">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ConfirmationModal :show="showDelete" :close="() => (showDelete = false)" @confirm="destroy">
            <template #title>Delete post</template>
            <template #message>Are you sure you want to delete this post? This cannot be undone.</template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

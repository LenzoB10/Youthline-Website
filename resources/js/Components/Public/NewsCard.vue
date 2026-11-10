<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    post: { type: Object, required: true },
});

const categoryLabel = {
    news: 'News',
    success_story: 'Success story',
    activity: 'Activity',
};

const label = computed(() => categoryLabel[props.post.category] ?? 'News');
</script>

<template>
    <article class="flex h-full flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition hover:shadow-md">
        <img
            v-if="post.image"
            :src="post.image"
            :alt="post.title"
            class="h-44 w-full object-cover"
        />
        <div v-else class="h-44 w-full bg-yl-green/10" />
        <div class="flex flex-1 flex-col p-6">
            <div class="flex items-center gap-3 text-xs">
                <span class="rounded-full bg-yl-green/10 px-3 py-1 font-semibold text-yl-green">
                    {{ label }}
                </span>
                <span class="text-yl-ink/50">{{ post.published_human }}</span>
            </div>
            <h3 class="mt-3 text-lg font-bold text-yl-green-deep">{{ post.title }}</h3>
            <p class="mt-2 flex-1 text-sm text-yl-ink/70">{{ post.excerpt }}</p>
            <Link
                :href="route('news.show', post.id)"
                class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-yl-green hover:text-yl-green-dark"
            >
                Read more <ArrowRight class="h-4 w-4" />
            </Link>
        </div>
    </article>
</template>

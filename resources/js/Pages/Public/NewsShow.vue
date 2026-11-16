<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
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
const paragraphs = computed(() =>
    (props.post.body ?? '').split('\n\n').filter((line) => line.trim().length > 0),
);
</script>

<template>
    <Head :title="post.title" />

    <PublicLayout>
        <article class="py-16">
            <div class="yl-enter yl-container max-w-3xl">
                <Link :href="route('news')" class="inline-flex items-center gap-1 text-sm font-semibold text-yl-green hover:text-yl-green-dark">
                    <ArrowLeft class="h-4 w-4" /> All news and stories
                </Link>

                <div class="mt-6 flex items-center gap-3 text-xs">
                    <span class="bg-yl-green/10 px-3 py-1 font-semibold text-yl-green">{{ label }}</span>
                    <span class="text-yl-ink/50">{{ post.published_human }}</span>
                </div>

                <h1 class="mt-4 text-3xl font-bold text-yl-green-deep sm:text-4xl">{{ post.title }}</h1>

                <img
                    v-if="post.image"
                    :src="post.image"
                    :alt="post.title"
                    class="mt-8 h-64 w-full object-cover object-[center_35%] sm:h-80 lg:h-[26rem]"
                />

                <div class="mt-8">
                    <p v-for="(para, i) in paragraphs" :key="i" class="mb-5 text-yl-ink/80">
                        {{ para }}
                    </p>
                </div>
            </div>
        </article>
    </PublicLayout>
</template>

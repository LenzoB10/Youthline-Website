<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    post: { type: Object, required: true },
});
</script>

<template>
    <article class="group">
        <Link :href="route('news.show', post.id)" class="block focus:outline-none">
            <div class="aspect-[16/9] overflow-hidden bg-yl-green/10">
                <img
                    v-if="post.image"
                    :src="post.image"
                    alt=""
                    loading="lazy"
                    decoding="async"
                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                />
                <div v-else class="flex h-full items-center justify-center">
                    <img src="/imgs/youthline-logo-sm.png" alt="" class="h-16 w-auto opacity-40" />
                </div>
            </div>
            <h3
                class="mt-5 text-xl font-bold leading-snug text-yl-ink transition group-hover:text-yl-green group-focus-visible:text-yl-green group-focus-visible:underline"
            >
                {{ post.title }}
            </h3>
        </Link>
        <p class="mt-2 text-sm text-yl-ink/55">
            <time :datetime="post.published_at" :title="post.published_long">{{ post.published_relative }}</time>
        </p>
    </article>
</template>

<script setup>
import NewsCard from '@/Components/Public/NewsCard.vue';
import PageHeader from '@/Components/Public/PageHeader.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    page: { type: Object, default: null },
    posts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    activeCategory: { type: String, default: null },
    totalPosts: { type: Number, default: 0 },
});

const links = computed(() => [
    { key: null, label: 'All stories', count: props.totalPosts },
    ...props.categories,
]);

const activeLabel = computed(
    () => props.categories.find((c) => c.key === props.activeCategory)?.label ?? null,
);

const hrefFor = (key) => (key ? route('news', { category: key }) : route('news'));

// The newest post (in the chosen category) leads the page as a full-width hero;
// the rest are listed below it.
const featured = computed(() => props.posts[0] ?? null);
const others = computed(() => props.posts.slice(1));

const featuredEyebrow = computed(() => {
    const singular = { news: 'Latest news', success_story: 'Latest success story', activity: 'Latest activity' };
    return singular[props.activeCategory] ?? 'Latest story';
});
</script>

<template>
    <Head :title="activeLabel ? `${activeLabel} - News and Stories` : 'News and Stories'" />

    <PublicLayout>
        <!-- Featured story hero -->
        <section v-if="featured" class="relative isolate overflow-hidden bg-yl-forest text-white">
            <h1 class="sr-only">{{ activeLabel ? `${activeLabel} - News and Stories` : 'News and Stories' }}</h1>
            <Transition name="hero-fade" mode="out-in">
                <img
                    :key="featured.id"
                    :src="featured.image || '/imgs/web/lg/IMG20261028134009.jpg'"
                    alt=""
                    fetchpriority="high"
                    class="absolute inset-0 -z-20 h-full w-full object-cover"
                />
            </Transition>
            <div class="absolute inset-0 -z-10 bg-gradient-to-r from-yl-forest via-yl-forest/85 to-yl-forest/30"></div>

            <div class="yl-container py-24 sm:py-32">
                <!-- Keyed so the text animates in again when the category changes -->
                <div :key="featured.id" class="yl-enter max-w-3xl">
                    <p class="text-sm font-semibold uppercase tracking-wider text-yl-gold">{{ featuredEyebrow }}</p>
                    <p class="mt-4 inline-flex bg-yl-gold px-3 py-1 text-sm font-medium text-yl-forest">
                        <time :datetime="featured.published_at">{{ featured.published_long }}</time>
                        <span class="mx-2" aria-hidden="true">|</span>
                        {{ featured.reading_minutes }} {{ featured.reading_minutes === 1 ? 'minute' : 'minutes' }}
                    </p>
                    <h2 class="mt-5 text-4xl font-bold leading-tight sm:text-5xl">{{ featured.title }}</h2>
                    <p v-if="featured.excerpt" class="mt-5 text-lg leading-relaxed text-white/85">{{ featured.excerpt }}</p>
                    <Link
                        :href="route('news.show', featured.id)"
                        class="mt-8 inline-flex items-center gap-2 rounded-lg bg-yl-gold px-6 py-3.5 font-semibold text-yl-forest transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-yl-forest"
                    >
                        Read the full story <ChevronRight class="h-5 w-5" />
                    </Link>
                </div>
            </div>
        </section>

        <!-- Nothing published yet: plain photo banner -->
        <PageHeader v-else title="News and Stories" image="/imgs/web/lg/IMG20261028134009.jpg" />

        <section class="py-16 sm:py-20">
            <div class="yl-container grid items-start gap-10 lg:grid-cols-[1fr_20rem]">
                <!-- Stories -->
                <div>
                    <h2 v-if="others.length" class="mb-6 text-2xl font-bold text-yl-green-deep">
                        {{ activeLabel ? `More ${activeLabel.toLowerCase()}` : 'More stories' }}
                    </h2>
                    <div v-if="others.length" :key="activeCategory ?? 'all'" v-reveal.stagger="120" class="grid gap-x-6 gap-y-12 sm:grid-cols-2">
                        <NewsCard v-for="post in others" :key="post.id" :post="post" />
                    </div>
                    <div v-else-if="featured" class="border border-dashed border-gray-200 py-16 text-center">
                        <p class="text-yl-ink/60">That's the only story here so far. More are on the way.</p>
                    </div>
                    <div v-else class="border border-dashed border-gray-200 py-20 text-center">
                        <p class="text-yl-ink/60">
                            {{ activeLabel ? 'Nothing has been published in this category yet.' : 'No stories have been published yet. Please check back soon.' }}
                        </p>
                    </div>
                </div>

                <!-- Categories -->
                <aside v-reveal:right class="border border-gray-100 bg-white p-6 shadow-sm lg:sticky lg:top-24">
                    <h2 class="text-lg font-bold text-yl-green-deep">Categories</h2>
                    <nav class="mt-4" aria-label="News categories">
                        <ul class="divide-y divide-gray-100">
                            <li v-for="link in links" :key="link.key ?? 'all'">
                                <Link
                                    :href="hrefFor(link.key)"
                                    preserve-scroll
                                    class="group flex items-center justify-between gap-3 py-3 text-sm font-medium transition"
                                    :class="activeCategory === link.key ? 'text-yl-green' : 'text-yl-ink/75 hover:text-yl-green'"
                                    :aria-current="activeCategory === link.key ? 'page' : undefined"
                                >
                                    <span class="flex items-center gap-2">
                                        <ChevronRight
                                            class="h-4 w-4 transition group-hover:translate-x-0.5"
                                            :class="activeCategory === link.key ? 'text-yl-green' : 'text-yl-ink/30'"
                                            aria-hidden="true"
                                        />
                                        {{ link.label }}
                                    </span>
                                    <span
                                        class="px-2.5 py-0.5 text-xs font-semibold"
                                        :class="activeCategory === link.key ? 'bg-yl-green text-white' : 'bg-yl-green/10 text-yl-green'"
                                    >
                                        {{ link.count }}
                                    </span>
                                </Link>
                            </li>
                        </ul>
                    </nav>
                </aside>
            </div>
        </section>
    </PublicLayout>
</template>

<style scoped>
.hero-fade-enter-active,
.hero-fade-leave-active {
    transition: opacity 0.4s ease;
}
.hero-fade-enter-from,
.hero-fade-leave-to {
    opacity: 0;
}
</style>

<script setup>
import NewsCard from '@/Components/Public/NewsCard.vue';
import ProgramCard from '@/Components/Public/ProgramCard.vue';
import SectionHeading from '@/Components/Public/SectionHeading.vue';
import StatTile from '@/Components/Public/StatTile.vue';
import ValueCard from '@/Components/Public/ValueCard.vue';
import { imageForMetric } from '@/Helpers/impactImage';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

defineProps({
    intro: { type: Object, default: null },
    aboutSummary: { type: String, default: null },
    programs: { type: Array, default: () => [] },
    values: { type: Array, default: () => [] },
    impact: { type: Array, default: () => [] },
    news: { type: Array, default: () => [] },
});

const page = usePage();
const site = computed(() => page.props.site ?? {});

// Optimised copies live in /imgs/web/{lg,sm}/ (originals are 4-5 MB each).
const heroImages = [
    'IMG20261028134004.jpg',
    'IMG20261028134006.jpg',
    'IMG20261028134007.jpg',
    'IMG20261028134008.jpg',
    'IMG20261028134009.jpg',
].map((file) => ({ large: `/imgs/web/lg/${file}`, thumb: `/imgs/web/sm/${file}` }));

const activeIndex = ref(0);
const paused = ref(false);
let timer = null;

const stop = () => {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
};

const restart = () => {
    stop();
    // Respect users who ask for reduced motion: no auto-advance.
    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
        return;
    }
    timer = setInterval(() => {
        if (!paused.value) {
            activeIndex.value = (activeIndex.value + 1) % heroImages.length;
        }
    }, 4000);
};

const go = (index) => {
    activeIndex.value = index;
    restart();
};

onMounted(restart);
onBeforeUnmount(stop);
</script>

<template>
    <Head title="Home" />

    <PublicLayout>
        <!-- Hero -->
        <section class="bg-yl-cream">
            <div class="yl-container grid items-center gap-10 py-16 lg:grid-cols-2 lg:py-24">
                <div>
                    <p class="yl-eyebrow">Youthline</p>
                    <h1 class="mt-4 text-4xl font-bold leading-tight text-yl-green-deep sm:text-5xl">
                        {{ site.tagline || 'Empowering Young People. Transforming Lives. Building Futures.' }}
                    </h1>
                    <p class="mt-6 max-w-xl text-lg text-yl-ink/70">
                        We help vulnerable young people access education, essential resources,
                        mentorship, and the opportunities they need to build brighter futures.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <Link :href="route('get-involved')" class="yl-btn-accent">Donate now</Link>
                        <Link :href="route('programs')" class="yl-btn-outline">
                            Our programs <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>
                </div>
                <div
                    class="grid grid-cols-[4rem_1fr] items-stretch gap-3 sm:grid-cols-[5rem_1fr] sm:gap-4"
                    role="region"
                    aria-roledescription="carousel"
                    aria-label="Photos of Youthline's work"
                    @mouseenter="paused = true"
                    @mouseleave="paused = false"
                    @focusin="paused = true"
                    @focusout="paused = false"
                >
                    <div class="flex flex-col gap-3 sm:gap-4">
                        <button
                            v-for="(img, i) in heroImages"
                            :key="i"
                            type="button"
                            class="overflow-hidden rounded-xl ring-2 transition focus:outline-none focus-visible:ring-yl-green"
                            :class="i === activeIndex ? 'ring-yl-gold' : 'opacity-70 ring-transparent hover:opacity-100'"
                            :aria-label="`Show photo ${i + 1} of ${heroImages.length}`"
                            :aria-current="i === activeIndex ? 'true' : undefined"
                            @click="go(i)"
                        >
                            <img
                                :src="img.thumb"
                                alt=""
                                width="320"
                                height="427"
                                decoding="async"
                                class="aspect-square w-full object-cover"
                            />
                        </button>
                    </div>
                    <div class="relative overflow-hidden rounded-3xl shadow-md" aria-live="off">
                        <Transition name="fade">
                            <img
                                :key="activeIndex"
                                :src="heroImages[activeIndex].large"
                                alt="Young people supported by Youthline"
                                width="1200"
                                height="1600"
                                fetchpriority="high"
                                class="absolute inset-0 h-full w-full object-cover"
                            />
                        </Transition>
                    </div>
                </div>
            </div>
        </section>

        <!-- Impact strip -->
        <section v-if="impact.length" class="border-b border-gray-100 bg-white py-14">
            <div class="yl-container grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <StatTile
                    v-for="metric in impact"
                    :key="metric.key"
                    :value="metric.value"
                    :label="metric.label"
                    :image="imageForMetric(metric.key)"
                />
            </div>
            <div class="yl-container mt-8 text-center">
                <Link :href="route('impact')" class="yl-btn-outline">
                    See our full impact <ArrowRight class="h-4 w-4" />
                </Link>
            </div>
        </section>

        <!-- About preview -->
        <section class="py-20">
            <div class="yl-container grid items-center gap-10 lg:grid-cols-2">
                <div>
                    <p class="yl-eyebrow">About Youthline</p>
                    <h2 class="yl-h2 mt-3">More than financial support</h2>
                    <p class="mt-4 text-yl-ink/70">{{ aboutSummary }}</p>
                    <Link :href="route('about')" class="yl-btn-outline mt-6">
                        Learn about us <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <ValueCard v-for="value in values.slice(0, 4)" :key="value.id" :value="value" />
                </div>
            </div>
        </section>

        <!-- Programs preview -->
        <section class="bg-yl-cream py-20">
            <div class="yl-container">
                <SectionHeading
                    eyebrow="What we do"
                    title="Our Programs"
                    subtitle="Six connected programs that support a young person’s whole journey."
                />
                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <ProgramCard v-for="program in programs" :key="program.id" :program="program" />
                </div>
            </div>
        </section>

        <!-- Latest news -->
        <section v-if="news.length" class="py-20">
            <div class="yl-container">
                <SectionHeading
                    eyebrow="Updates"
                    title="News and Stories"
                    subtitle="The latest from Youthline and the young people we serve."
                />
                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <NewsCard v-for="post in news" :key="post.id" :post="post" />
                </div>
                <div class="mt-10 text-center">
                    <Link :href="route('news')" class="yl-btn-outline">
                        All news and stories <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="bg-yl-green-deep py-16 text-white">
            <div class="yl-container flex flex-col items-center justify-between gap-6 text-center sm:flex-row sm:text-left">
                <div>
                    <h2 class="text-2xl font-bold sm:text-3xl">Help a young person thrive</h2>
                    <p class="mt-2 text-white/80">Your support changes a life, a family, and a community.</p>
                </div>
                <Link :href="route('get-involved')" class="yl-btn-accent shrink-0">Get involved</Link>
            </div>
        </section>
    </PublicLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.6s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
.fade-leave-active {
    position: absolute;
    inset: 0;
}
</style>

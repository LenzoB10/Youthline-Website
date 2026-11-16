<script setup>
import ImpactGlance from '@/Components/Public/ImpactGlance.vue';
import ProgramCard from '@/Components/Public/ProgramCard.vue';
import ValueCard from '@/Components/Public/ValueCard.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, CalendarDays } from 'lucide-vue-next';
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
    'IMG20261028134006.jpg',
    'IMG20261028134007.jpg',
    'IMG20261028134008.jpg',
].map((file) => ({ large: `/imgs/web/lg/${file}` }));

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
    }, 5000);
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

    <PublicLayout transparent-header>
        <!-- Hero -->
        <section
            class="relative isolate flex min-h-[100svh] items-center overflow-hidden bg-yl-forest text-white"
            role="region"
            aria-roledescription="carousel"
            aria-label="Photos of Youthline's work"
            @focusin="paused = true"
            @focusout="paused = false"
        >
            <!-- Background photos (cross-fade) -->
            <div class="absolute inset-0 -z-10" aria-live="off">
                <Transition name="fade">
                    <img
                        :key="activeIndex"
                        :src="heroImages[activeIndex].large"
                        alt="Young people supported by Youthline"
                        width="1200"
                        height="1600"
                        fetchpriority="high"
                        class="yl-slow-zoom absolute inset-0 h-full w-full object-cover object-[center_30%]"
                    />
                </Transition>
                <!-- Darken the photo so the white text and see-through navbar stay readable -->
                <div class="absolute inset-0 bg-gradient-to-r from-yl-forest/90 via-yl-forest/65 to-yl-forest/25"></div>
                <div class="absolute inset-x-0 top-0 h-40 bg-gradient-to-b from-black/50 to-transparent"></div>
            </div>

            <div class="yl-container w-full pb-24 pt-32 lg:pb-28 lg:pt-36">
                <div class="max-w-2xl">
                    <p class="yl-enter yl-enter-1 text-sm font-semibold uppercase tracking-wider text-yl-gold">Youthline</p>
                    <h1 class="yl-enter yl-enter-2 mt-4 text-4xl font-bold leading-tight sm:text-5xl lg:text-6xl">
                        {{ site.tagline || 'Empowering Young People. Transforming Lives. Building Futures.' }}
                    </h1>
                    <p class="yl-enter yl-enter-3 mt-6 max-w-xl text-lg text-white/85">
                        We help vulnerable young people access education, essential resources,
                        mentorship, and the opportunities they need to build brighter futures.
                    </p>
                    <div class="yl-enter yl-enter-4 mt-8 flex flex-wrap gap-4">
                        <Link :href="route('get-involved')" class="yl-btn-accent">Donate now</Link>
                        <Link
                            :href="route('programs')"
                            class="inline-flex items-center justify-center gap-2 rounded-full border border-white/70 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-yl-forest"
                        >
                            Our programs <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Photo selector -->
            <div class="absolute inset-x-0 bottom-8">
                <div class="yl-container flex gap-2">
                    <button
                        v-for="(img, i) in heroImages"
                        :key="i"
                        type="button"
                        class="h-1.5 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-yl-forest"
                        :class="i === activeIndex ? 'w-10 bg-yl-gold' : 'w-5 bg-white/50 hover:bg-white/80'"
                        :aria-label="`Show photo ${i + 1} of ${heroImages.length}`"
                        :aria-current="i === activeIndex ? 'true' : undefined"
                        @click="go(i)"
                    ></button>
                </div>
            </div>
        </section>

        <!-- About preview -->
        <section class="py-20">
            <div class="yl-container grid items-center gap-10 lg:grid-cols-2">
                <div v-reveal:left>
                    <p class="yl-eyebrow">About Youthline</p>
                    <h2 class="yl-h2 mt-3">More than financial support</h2>
                    <p class="mt-4 text-yl-ink/70">{{ aboutSummary }}</p>
                    <Link :href="route('about')" class="yl-btn-outline mt-6">
                        Learn about us <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
                <div v-reveal.stagger class="grid grid-cols-2 gap-4">
                    <ValueCard v-for="value in values.slice(0, 4)" :key="value.id" :value="value" />
                </div>
            </div>
        </section>

        <!-- Programs preview -->
        <section class="bg-yl-cream py-20">
            <div class="yl-container">
                <div v-reveal class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="yl-eyebrow">What we do</p>
                        <h2 class="yl-h2 mt-3">Our Programs</h2>
                        <p class="mt-3 max-w-2xl text-yl-ink/70">
                            Six connected programs that support a young person’s whole journey.
                        </p>
                    </div>
                    <Link
                        :href="route('programs')"
                        class="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-yl-green transition hover:text-yl-green-dark"
                    >
                        View all programs <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
                <div v-reveal.stagger="150" class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <ProgramCard v-for="program in programs.slice(0, 3)" :key="program.id" :program="program" />
                </div>
            </div>
        </section>

        <!-- Impact at a glance -->
        <ImpactGlance :metrics="impact" />

        <!-- News and stories -->
        <section v-if="news.length" class="bg-yl-cream py-20">
            <div class="yl-container">
                <div v-reveal class="flex items-end justify-between gap-4">
                    <h2 class="yl-h2 !text-yl-green">News and Stories</h2>
                    <Link
                        :href="route('news')"
                        class="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-yl-green transition hover:text-yl-green-dark"
                    >
                        View all <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>

                <div v-reveal.stagger="120" class="mt-10 grid border-l border-t border-gray-200 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="post in news"
                        :key="post.id"
                        :href="route('news.show', post.id)"
                        class="group flex flex-col border-b border-r border-gray-200 bg-white/40 transition hover:bg-white"
                    >
                        <div class="aspect-[4/3] overflow-hidden bg-yl-green/10">
                            <img
                                v-if="post.image"
                                :src="post.image"
                                alt=""
                                loading="lazy"
                                decoding="async"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                            />
                        </div>
                        <div class="flex flex-1 flex-col px-6 pb-10 pt-6">
                            <p class="flex items-center gap-2.5 text-xs font-medium uppercase tracking-widest text-yl-ink/70">
                                <CalendarDays class="h-4 w-4 text-yl-ink/80" aria-hidden="true" />
                                <time :datetime="post.published_at">{{ post.published_long }}</time>
                            </p>
                            <h3 class="mt-3 line-clamp-2 text-lg font-bold leading-snug text-yl-green-deep group-hover:text-yl-green">
                                {{ post.title }}
                            </h3>
                            <p class="mt-3 line-clamp-2 text-yl-ink/70">{{ post.excerpt }}</p>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="bg-yl-green-deep py-16 text-white">
            <div v-reveal:zoom class="yl-container flex flex-col items-center justify-between gap-6 text-center sm:flex-row sm:text-left">
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

<script setup>
import ImpactGlance from '@/Components/Public/ImpactGlance.vue';
import ProgramCard from '@/Components/Public/ProgramCard.vue';
import TestimonialMarquee from '@/Components/Public/TestimonialMarquee.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Building2, CalendarDays, GraduationCap, HandCoins, HandHeart, HeartHandshake, MessagesSquare, ShoppingBasket, UserRoundPlus } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    intro: { type: Object, default: null },
    aboutSummary: { type: String, default: null },
    programs: { type: Array, default: () => [] },
    values: { type: Array, default: () => [] },
    impact: { type: Array, default: () => [] },
    news: { type: Array, default: () => [] },
    testimonials: { type: Array, default: () => [] },
    partners: { type: Array, default: () => [] },
});

const page = usePage();
const site = computed(() => page.props.site ?? {});

// About preview: what Youthline does, and the live "young people supported" figure for the badge.
const offers = [
    { icon: GraduationCap, title: 'School fees & scholarships', text: 'Keeping young people in secondary school and university.' },
    { icon: ShoppingBasket, title: 'Food & essentials', text: 'Groceries, uniforms and the basics needed to keep learning.' },
    { icon: MessagesSquare, title: 'Mentorship & counselling', text: 'A trusted adult to guide them through school and life.' },
    { icon: HandHeart, title: 'Family & community support', text: 'Home visits and help for the families behind every student.' },
];
// Ways to help: each opens the Get Involved form with that option already chosen.
const waysToHelp = [
    { type: 'sponsor', icon: GraduationCap, title: 'Sponsor a student', text: 'Cover a young person\'s school fees and receive progress reports on how they are doing.', cta: 'Sponsor a student' },
    { type: 'donor', icon: HandCoins, title: 'Make a donation', text: 'Give once or regularly to provide food, uniforms, mentoring and family support where it is needed most.', cta: 'Donate' },
    { type: 'mentor', icon: UserRoundPlus, title: 'Volunteer or mentor', text: 'Share your time and experience to guide a young person through school, work and life.', cta: 'Get involved' },
    { type: 'partner', icon: HeartHandshake, title: 'Partner with us', text: 'Schools, churches, businesses and foundations can join us to reach more young people.', cta: 'Partner with us' },
];

const supported = computed(() => props.impact.find((metric) => metric.key === 'supported')?.value ?? 0);

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
                <div class="absolute inset-0 bg-yl-forest/50"></div>
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
        <section class="overflow-hidden py-20 sm:py-24">
            <div class="yl-container grid items-center gap-14 lg:grid-cols-2 lg:gap-16">
                <!-- Photos with a live figure (fixed proportions, so nothing hangs out of the section) -->
                <div v-reveal:left class="relative isolate mx-auto aspect-[6/5] w-full max-w-xl">
                    <img
                        src="/imgs/web/md/IMG20261028134052.jpg"
                        alt="Young people supported by Youthline"
                        width="800"
                        height="1067"
                        loading="lazy"
                        decoding="async"
                        class="absolute left-0 top-0 h-[84%] w-[72%] rounded-md object-cover object-[center_30%] shadow-lg"
                    />
                    <img
                        src="/imgs/web/md/IMG20261028134004.jpg"
                        alt=""
                        width="800"
                        height="1067"
                        loading="lazy"
                        decoding="async"
                        class="absolute bottom-0 right-0 h-[52%] w-[48%] rounded-md border-[6px] border-white object-cover object-[center_30%] shadow-xl"
                    />
                    <div
                        v-if="supported"
                        class="yl-slant-card absolute right-[2%] top-[10%] rounded-md bg-yl-green-deep py-5 pl-6 pr-12 text-white shadow-xl sm:py-6 sm:pl-7 sm:pr-14"
                    >
                        <p class="text-4xl font-bold leading-none sm:text-5xl">{{ supported }}<span class="text-yl-gold">+</span></p>
                        <p class="mt-3 text-[11px] font-bold uppercase leading-snug tracking-wider text-white/90 sm:text-xs">
                            Young people<br />supported
                        </p>
                    </div>
                </div>

                <!-- Story and what we do -->
                <div v-reveal:right>
                    <p class="yl-eyebrow">About Youthline</p>
                    <h2 class="yl-h2 mt-3">More than financial support</h2>
                    <p class="mt-5 text-lg leading-relaxed text-yl-ink/75">{{ aboutSummary }}</p>

                    <ul class="mt-8 grid gap-x-8 gap-y-6 sm:grid-cols-2">
                        <li v-for="offer in offers" :key="offer.title" class="group flex gap-4">
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-yl-green/10 text-yl-green transition group-hover:bg-yl-green group-hover:text-white"
                                aria-hidden="true"
                            >
                                <component :is="offer.icon" class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="font-semibold text-yl-green-deep">{{ offer.title }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-yl-ink/65">{{ offer.text }}</p>
                            </div>
                        </li>
                    </ul>

                    <Link :href="route('about')" class="yl-btn-outline mt-10">
                        Learn about us <ArrowRight class="h-4 w-4" />
                    </Link>
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

        <!-- Testimonials: moving cards -->
        <section v-if="testimonials.length" class="bg-white py-20 sm:py-24">
            <div class="yl-container">
                <div v-reveal class="mx-auto max-w-2xl text-center">
                    <p class="yl-eyebrow">Testimonials</p>
                    <h2 class="yl-h2 mt-3">What people say about Youthline</h2>
                    <p class="mt-4 text-yl-ink/70">Students, families and supporters, in their own words.</p>
                </div>
            </div>
            <TestimonialMarquee v-reveal class="mt-12" :testimonials="testimonials" />
        </section>

        <!-- Partners: logo strip, only when partners have been added -->
        <section v-if="partners.length" class="border-t border-gray-100 bg-white py-14">
            <div class="yl-container">
                <p v-reveal class="text-center text-sm font-semibold uppercase tracking-wider text-yl-ink/50">Supported by partners who share our mission</p>
                <ul v-reveal.stagger="80" class="mt-8 flex flex-wrap items-center justify-center gap-x-12 gap-y-8">
                    <li v-for="partner in partners" :key="partner.id">
                        <component
                            :is="partner.url ? 'a' : 'span'"
                            :href="partner.url ?? undefined"
                            :target="partner.url ? '_blank' : undefined"
                            :rel="partner.url ? 'noopener' : undefined"
                            class="flex h-14 items-center opacity-60 grayscale transition duration-300 hover:opacity-100 hover:grayscale-0"
                            :aria-label="partner.name"
                        >
                            <img v-if="partner.logo" :src="partner.logo" :alt="partner.name" loading="lazy" class="max-h-14 w-auto max-w-[10rem] object-contain" />
                            <span v-else class="flex items-center gap-2 text-lg font-bold text-yl-ink"><Building2 class="h-5 w-5" /> {{ partner.name }}</span>
                        </component>
                    </li>
                </ul>
            </div>
        </section>

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

                <div v-reveal.stagger="120" class="mt-10 grid overflow-hidden rounded-md border-l border-t border-gray-200 sm:grid-cols-2 lg:grid-cols-4">
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
                            <div v-else class="flex h-full items-center justify-center bg-gradient-to-br from-yl-green/10 to-yl-gold/10">
                                <img src="/imgs/youthline-logo-sm.png" alt="" class="h-16 w-auto opacity-50 transition duration-500 group-hover:scale-105" />
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col px-6 pb-7 pt-5">
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

        <!-- Ways to help -->
        <section class="bg-white py-20 sm:py-24">
            <div class="yl-container">
                <div v-reveal class="mx-auto max-w-2xl text-center">
                    <p class="yl-eyebrow">Ways to help</p>
                    <h2 class="yl-h2 mt-3">Change a young person's story</h2>
                    <p class="mt-4 text-yl-ink/70">Every kind of support matters. Choose the one that suits you.</p>
                </div>
                <div v-reveal.stagger="120" class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="way in waysToHelp"
                        :key="way.type"
                        :href="route('get-involved', { type: way.type }) + '#involve'"
                        class="group flex flex-col rounded-md border border-gray-100 bg-yl-cream p-7 transition hover:-translate-y-1 hover:border-yl-green/30 hover:bg-white hover:shadow-xl"
                    >
                        <span class="flex h-12 w-12 items-center justify-center rounded-md bg-yl-green text-white transition group-hover:bg-yl-gold group-hover:text-yl-forest" aria-hidden="true">
                            <component :is="way.icon" class="h-6 w-6" />
                        </span>
                        <h3 class="mt-5 text-lg font-bold text-yl-green-deep">{{ way.title }}</h3>
                        <p class="mt-2 flex-1 text-sm leading-relaxed text-yl-ink/70">{{ way.text }}</p>
                        <span class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-yl-green transition group-hover:gap-2.5">
                            {{ way.cta }} <ArrowRight class="h-4 w-4" />
                        </span>
                    </Link>
                </div>
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

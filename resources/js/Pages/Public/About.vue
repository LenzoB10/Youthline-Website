<script setup>
import ImpactGlance from '@/Components/Public/ImpactGlance.vue';
import PageHeader from '@/Components/Public/PageHeader.vue';
import SectionHeading from '@/Components/Public/SectionHeading.vue';
import TeamCard from '@/Components/Public/TeamCard.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, HandHeart, HeartHandshake, Lightbulb, Scale, ShieldCheck, Sparkles, Sprout, Target, TrendingUp, Users } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    page: { type: Object, default: null },
    vision: { type: Object, default: null },
    mission: { type: Object, default: null },
    commitment: { type: Object, default: null },
    values: { type: Array, default: () => [] },
    team: { type: Array, default: () => [] },
    impact: { type: Array, default: () => [] },
});

// Mission, vision and goal as three centred columns. `word` is the coloured half of the title.
const purpose = computed(() => [
    { key: 'mission', word: 'Mission', body: props.mission?.body, icon: Target },
    { key: 'vision', word: 'Vision', body: props.vision?.body, icon: Lightbulb },
    {
        key: 'goal',
        word: 'Goal',
        body: 'Our goal is to reach more students and families who need support in their day-to-day lives.',
        icon: HandHeart,
    },
].filter((item) => item.body));

// Core values have no icon of their own, so pick one from the value's name.
// Values added later in the admin get a general icon.
const VALUE_ICONS = [
    [/compassion|care|kind/i, HeartHandshake],
    [/empower/i, TrendingUp],
    [/integrity|honest|trust|account/i, ShieldCheck],
    [/equity|equal|fair|inclus/i, Scale],
    [/resilien|growth|perseve/i, Sprout],
    [/community|together|partner/i, Users],
];
const valueIcon = (name) => VALUE_ICONS.find(([pattern]) => pattern.test(name ?? ''))?.[1] ?? Sparkles;

// Live "young people supported" figure for the card on the photos.
const supported = computed(() => props.impact.find((metric) => metric.key === 'supported')?.value ?? 0);

const paragraphs = computed(() =>
    (props.page?.body ?? '').split('\n\n').filter((line) => line.trim().length > 0),
);
</script>

<template>
    <Head title="About Us" />

    <PublicLayout>
        <PageHeader title="About Us" image="/imgs/web/lg/IMG20261028134049.jpg" />

        <!-- Narrative -->
        <section class="py-20 sm:py-24">
            <div class="yl-container grid items-center gap-14 lg:grid-cols-2 lg:gap-16">
                <!-- Two overlapping photos with the figure card, laid out in the reference's proportions.
                     Every position is a percentage of this box (and text uses cqw), so spacing stays identical at any size. -->
                <div v-reveal:left class="photo-group relative mx-auto aspect-[564/483] w-full max-w-xl">
                    <img
                        src="/imgs/web/md/IMG20261028134003.jpg"
                        alt="Young people supported by Youthline"
                        width="800"
                        height="1067"
                        loading="lazy"
                        decoding="async"
                        class="absolute left-0 top-0 h-[76.4%] w-[69.9%] rounded-md object-cover object-[center_30%] shadow-lg"
                    />
                    <div
                        v-if="supported"
                        class="yl-slant-card absolute left-[50.9%] top-[10.6%] z-10 flex h-[30%] w-[38.7%] flex-col justify-center rounded-md bg-yl-green-deep pl-[4.5cqw] text-white shadow-xl"
                    >
                        <p class="text-[7.5cqw] font-bold leading-none">{{ supported }}<span class="text-yl-gold">+</span></p>
                        <p class="mt-[2.2cqw] text-[2.1cqw] font-bold uppercase leading-snug tracking-wider text-white/90">
                            Young people<br />supported
                        </p>
                    </div>
                    <img
                        src="/imgs/web/md/IMG20261028134008.jpg"
                        alt=""
                        width="800"
                        height="1067"
                        loading="lazy"
                        decoding="async"
                        class="absolute bottom-0 right-0 z-20 h-[51.1%] w-[58.3%] rounded-md border-[1.2cqw] border-white object-cover object-[center_30%] shadow-xl"
                    />
                </div>

                <!-- Story -->
                <div v-reveal:right>
                    <p class="yl-eyebrow">Who we are</p>
                    <div class="mt-5 space-y-5">
                        <p
                            v-for="(para, i) in paragraphs"
                            :key="i"
                            :class="[
                                'leading-relaxed',
                                i === 0 ? 'text-2xl font-semibold text-yl-green-deep sm:text-3xl' : 'text-yl-ink/75',
                            ]"
                        >
                            {{ para }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Impact at a glance -->
        <ImpactGlance :metrics="impact" image="/imgs/web/lg/IMG20261028134007.jpg" />

        <!-- Our purpose: mission, vision and goal in three columns -->
        <section class="bg-white py-20 sm:py-24">
            <div class="yl-container">
                <SectionHeading v-reveal eyebrow="Our purpose" title="What drives everything we do" />

                <div v-reveal.stagger="150" class="mt-16 grid gap-14 md:grid-cols-3 md:gap-0 md:divide-x md:divide-yl-green/15">
                    <article v-for="item in purpose" :key="item.key" class="group flex flex-col items-center px-4 text-center md:px-10 lg:px-14">
                        <!-- Large icon with a soft gold accent behind it -->
                        <span class="relative flex h-28 w-28 items-center justify-center" aria-hidden="true">
                            <span class="absolute right-3 top-3 h-12 w-12 rounded-full bg-yl-gold/25 transition duration-500 group-hover:scale-125"></span>
                            <component :is="item.icon" class="relative h-20 w-20 text-yl-green-deep transition duration-500 group-hover:-translate-y-1" :stroke-width="1.25" />
                        </span>

                        <h3 class="mt-6 text-xl font-extrabold uppercase tracking-wide text-yl-ink">
                            Our <span class="text-yl-green">{{ item.word }}</span>
                        </h3>

                        <!-- line · dot · line -->
                        <div class="mt-5 flex items-center gap-1.5" aria-hidden="true">
                            <span class="h-px w-10 bg-yl-green/50"></span>
                            <span class="h-1.5 w-1.5 rounded-full bg-yl-green"></span>
                            <span class="h-px w-10 bg-yl-green/50"></span>
                        </div>

                        <component :is="item.icon" class="mt-5 h-5 w-5 text-yl-green/70" aria-hidden="true" />

                        <p class="mt-5 max-w-sm leading-relaxed text-yl-ink/70">{{ item.body }}</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- Core values: a grid separated only by thin lines -->
        <section v-if="values.length" class="bg-yl-cream py-20 sm:py-24">
            <div class="yl-container">
                <h2 v-reveal class="yl-h2 text-center">Our Core Values</h2>

                <ul v-reveal.stagger="100" class="mx-auto mt-14 grid max-w-6xl md:grid-cols-2 lg:grid-cols-3">
                    <li
                        v-for="(value, i) in values"
                        :key="value.id"
                        class="group border-b border-yl-ink/10 px-6 py-10 last:border-b-0 sm:px-10
                            md:max-lg:[&:nth-child(odd)]:border-r md:max-lg:[&:nth-last-child(-n+2)]:border-b-0
                            lg:[&:not(:nth-child(3n))]:border-r lg:[&:nth-last-child(-n+3)]:border-b-0"
                    >
                        <span class="relative inline-flex h-12 w-12 items-center justify-center" aria-hidden="true">
                            <span
                                class="absolute bottom-0 right-0 h-7 w-7 rounded-full transition duration-500 group-hover:scale-125"
                                :class="i % 2 ? 'bg-yl-green/15' : 'bg-yl-gold/30'"
                            ></span>
                            <component
                                :is="valueIcon(value.name)"
                                class="relative h-9 w-9 transition duration-500 group-hover:-translate-y-0.5"
                                :class="i % 2 ? 'text-yl-gold' : 'text-yl-green'"
                                :stroke-width="1.5"
                            />
                        </span>
                        <h3 class="mt-5 text-lg font-bold text-yl-green-deep">{{ value.name }}</h3>
                        <p v-if="value.description" class="mt-3 text-sm leading-relaxed text-yl-ink/65">{{ value.description }}</p>
                    </li>
                </ul>
            </div>
        </section>

        <!-- Commitment -->
        <section v-if="commitment" class="relative overflow-hidden bg-yl-green-deep py-20 text-white sm:py-24">
            <div
                class="absolute inset-0 opacity-25"
                style="background: radial-gradient(60% 60% at 50% 0%, var(--color-yl-green) 0%, transparent 70%)"
            />
            <div v-reveal class="yl-container relative">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="yl-eyebrow !text-white">{{ commitment.summary }}</p>
                    <h2 class="mt-3 text-3xl font-bold sm:text-4xl">{{ commitment.title }}</h2>
                    <p class="mt-5 text-lg leading-relaxed text-white/80">{{ commitment.body }}</p>
                    <Link :href="route('get-involved')" class="yl-btn-accent mt-8">
                        Get involved <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </section>
        <!-- Team -->
        <section v-if="team.length" class="bg-yl-cream py-20 sm:py-24">
            <div class="yl-container">
                <SectionHeading
                    v-reveal
                    eyebrow="Our people"
                    title="Leadership Team"
                    subtitle="The people guiding Youthline's work across Malawi."
                />
                <div v-reveal.stagger="150" class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <TeamCard v-for="member in team" :key="member.id" :member="member" />
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<style scoped>
/* Lets the card text and border scale with the photo group (cqw units). */
.photo-group {
    container-type: inline-size;
}
</style>

<script setup>
import PageHeader from '@/Components/Public/PageHeader.vue';
import SectionHeading from '@/Components/Public/SectionHeading.vue';
import TeamCard from '@/Components/Public/TeamCard.vue';
import ValueCard from '@/Components/Public/ValueCard.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Compass, Target } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    page: { type: Object, default: null },
    vision: { type: Object, default: null },
    mission: { type: Object, default: null },
    commitment: { type: Object, default: null },
    values: { type: Array, default: () => [] },
    team: { type: Array, default: () => [] },
});

const paragraphs = computed(() =>
    (props.page?.body ?? '').split('\n\n').filter((line) => line.trim().length > 0),
);
</script>

<template>
    <Head title="About Us" />

    <PublicLayout>
        <PageHeader
            eyebrow="About Youthline"
            :title="page?.title || 'About Us'"
            :subtitle="page?.summary || ''"
        />

        <!-- Narrative -->
        <section class="py-20 sm:py-24">
            <div class="yl-container">
                <div class="mx-auto max-w-3xl">
                    <p class="yl-eyebrow">Who we are</p>
                    <div class="mt-6 space-y-6">
                        <p
                            v-for="(para, i) in paragraphs"
                            :key="i"
                            :class="[
                                'leading-relaxed',
                                i === 0 ? 'text-xl font-medium text-yl-green-deep sm:text-2xl' : 'text-yl-ink/75',
                            ]"
                        >
                            {{ para }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Vision & Mission -->
        <section class="bg-yl-cream py-20 sm:py-24">
            <div class="yl-container">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="group relative overflow-hidden rounded-3xl bg-white p-8 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg sm:p-10">
                        <div class="absolute inset-x-0 top-0 h-1.5 bg-yl-green" />
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-yl-green/10 text-yl-green">
                            <Compass class="h-7 w-7" />
                        </span>
                        <h2 class="mt-6 text-2xl font-bold text-yl-green-deep">{{ vision?.title || 'Our Vision' }}</h2>
                        <p class="mt-4 leading-relaxed text-yl-ink/75">{{ vision?.body }}</p>
                    </div>
                    <div class="group relative overflow-hidden rounded-3xl bg-white p-8 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg sm:p-10">
                        <div class="absolute inset-x-0 top-0 h-1.5 bg-yl-gold" />
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-yl-gold/15 text-yl-gold">
                            <Target class="h-7 w-7" />
                        </span>
                        <h2 class="mt-6 text-2xl font-bold text-yl-green-deep">{{ mission?.title || 'Our Mission' }}</h2>
                        <p class="mt-4 leading-relaxed text-yl-ink/75">{{ mission?.body }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Core values -->
        <section class="py-20 sm:py-24">
            <div class="yl-container">
                <SectionHeading
                    eyebrow="What guides us"
                    title="Our Core Values"
                    subtitle="The principles behind everything we do for young people and their families."
                />
                <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <ValueCard v-for="value in values" :key="value.id" :value="value" />
                </div>
            </div>
        </section>

        <!-- Team -->
        <section v-if="team.length" class="bg-yl-cream py-20 sm:py-24">
            <div class="yl-container">
                <SectionHeading
                    eyebrow="Our people"
                    title="Leadership Team"
                    subtitle="The people guiding Youthline's work across Malawi."
                />
                <div class="mx-auto mt-14 grid max-w-5xl gap-10 sm:grid-cols-2 lg:grid-cols-3">
                    <TeamCard v-for="member in team" :key="member.id" :member="member" />
                </div>
            </div>
        </section>

        <!-- Commitment -->
        <section v-if="commitment" class="relative overflow-hidden bg-yl-green-deep py-20 text-white sm:py-24">
            <div
                class="absolute inset-0 opacity-25"
                style="background: radial-gradient(60% 60% at 50% 0%, var(--color-yl-green) 0%, transparent 70%)"
            />
            <div class="yl-container relative">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="yl-eyebrow text-yl-gold">{{ commitment.summary }}</p>
                    <h2 class="mt-3 text-3xl font-bold sm:text-4xl">{{ commitment.title }}</h2>
                    <p class="mt-5 text-lg leading-relaxed text-white/80">{{ commitment.body }}</p>
                    <Link :href="route('get-involved')" class="yl-btn-accent mt-8">
                        Get involved <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

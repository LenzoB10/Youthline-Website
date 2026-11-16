<script setup>
import ImpactGlance from '@/Components/Public/ImpactGlance.vue';
import PageHeader from '@/Components/Public/PageHeader.vue';
import SectionHeading from '@/Components/Public/SectionHeading.vue';
import TeamCard from '@/Components/Public/TeamCard.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, CircleCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    page: { type: Object, default: null },
    vision: { type: Object, default: null },
    mission: { type: Object, default: null },
    commitment: { type: Object, default: null },
    values: { type: Array, default: () => [] },
    team: { type: Array, default: () => [] },
    impact: { type: Array, default: () => [] },
});

const purposeTabs = computed(() => [
    { key: 'mission', label: 'Our Mission', title: props.mission?.title || 'Our Mission', body: props.mission?.body },
    { key: 'vision', label: 'Our Vision', title: props.vision?.title || 'Our Vision', body: props.vision?.body },
    ...(props.values.length ? [{ key: 'values', label: 'Core Values', title: 'Our Core Values' }] : []),
    {
        key: 'goal',
        label: 'Our Goal',
        title: 'Our Goal',
        body: 'Our goal is to reach more students and families who need support in their day-to-day lives.',
    },
]);
const activePurpose = ref('mission');
const currentPurpose = computed(() => purposeTabs.value.find((tab) => tab.key === activePurpose.value));
const movePurpose = (step) => {
    const keys = purposeTabs.value.map((tab) => tab.key);
    const next = keys[(keys.indexOf(activePurpose.value) + step + keys.length) % keys.length];
    activePurpose.value = next;
    document.getElementById(`purpose-tab-${next}`)?.focus();
};

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
                <!-- Two overlapping photos -->
                <div v-reveal:left class="relative mx-auto w-full max-w-xl pb-16 pr-10 sm:pb-24 sm:pr-16">
                    <img
                        src="/imgs/web/md/IMG20261028134003.jpg"
                        alt="Young people supported by Youthline"
                        width="800"
                        height="1067"
                        loading="lazy"
                        decoding="async"
                        class="aspect-[4/3] w-[85%] object-cover object-[center_30%] shadow-lg"
                    />
                    <img
                        src="/imgs/web/md/IMG20261028134008.jpg"
                        alt=""
                        width="800"
                        height="1067"
                        loading="lazy"
                        decoding="async"
                        class="absolute bottom-0 right-0 aspect-[4/3] w-[60%] border-8 border-white object-cover object-[center_30%] shadow-xl"
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

        <!-- Mission / Vision / Goal (tabs) -->
        <section class="bg-yl-cream py-20 sm:py-24">
            <div class="yl-container grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                <div v-reveal:left>
                    <p class="yl-eyebrow">Our purpose</p>
                    <h2 class="yl-h2 mt-3">What drives everything we do</h2>

                    <div class="mt-8 flex flex-wrap gap-3" role="tablist" aria-label="Mission, vision and goal">
                        <button
                            v-for="tab in purposeTabs"
                            :id="`purpose-tab-${tab.key}`"
                            :key="tab.key"
                            type="button"
                            role="tab"
                            :aria-selected="activePurpose === tab.key ? 'true' : 'false'"
                            :aria-controls="`purpose-panel-${tab.key}`"
                            :tabindex="activePurpose === tab.key ? 0 : -1"
                            class="rounded-full px-6 py-2.5 text-xs font-bold uppercase tracking-wider transition focus:outline-none focus-visible:ring-2 focus-visible:ring-yl-green focus-visible:ring-offset-2"
                            :class="
                                activePurpose === tab.key
                                    ? 'bg-yl-green-deep text-white shadow-md'
                                    : 'bg-white text-yl-green-deep shadow-sm hover:bg-yl-green/10'
                            "
                            @click="activePurpose = tab.key"
                            @keydown.right.prevent="movePurpose(1)"
                            @keydown.left.prevent="movePurpose(-1)"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <Transition name="purpose" mode="out-in">
                        <div
                            :id="`purpose-panel-${currentPurpose.key}`"
                            :key="currentPurpose.key"
                            role="tabpanel"
                            :aria-labelledby="`purpose-tab-${currentPurpose.key}`"
                            class="mt-8"
                        >
                            <h3 class="text-2xl font-bold text-yl-green-deep">{{ currentPurpose.title }}</h3>
                            <ul v-if="currentPurpose.key === 'values'" class="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                                <li v-for="value in values" :key="value.id" class="flex gap-3">
                                    <CircleCheck class="mt-0.5 h-5 w-5 shrink-0 text-yl-green" aria-hidden="true" />
                                    <div>
                                        <p class="font-semibold text-yl-green-deep">{{ value.name }}</p>
                                        <p v-if="value.description" class="mt-1 text-sm leading-relaxed text-yl-ink/70">
                                            {{ value.description }}
                                        </p>
                                    </div>
                                </li>
                            </ul>
                            <p v-else class="mt-4 text-lg leading-relaxed text-yl-ink/75">{{ currentPurpose.body }}</p>
                        </div>
                    </Transition>
                </div>

                <img
                    v-reveal:right
                    src="/imgs/web/md/IMG20261028134009.jpg"
                    alt="Young people supported by Youthline"
                    width="800"
                    height="1067"
                    loading="lazy"
                    decoding="async"
                    class="aspect-[4/3] w-full object-cover object-[center_30%] shadow-lg"
                />
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
                    <p class="yl-eyebrow text-yl-gold">{{ commitment.summary }}</p>
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
.purpose-enter-active,
.purpose-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}
.purpose-enter-from,
.purpose-leave-to {
    opacity: 0;
    transform: translateY(6px);
}
</style>

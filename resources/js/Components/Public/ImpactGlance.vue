<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Award, GraduationCap, House, Users } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref } from 'vue';

// "Youthline at a Glance": headline numbers over a parallax photo, counting up
// when scrolled into view. Used on the Home and About pages.
const props = defineProps({
    metrics: { type: Array, default: () => [] },
    image: { type: String, default: '/imgs/web/lg/IMG20261028134052.jpg' },
});

const metricIcons = { supported: Users, in_school: GraduationCap, graduates: Award, families: House };
const iconForMetric = (key) => metricIcons[key] ?? Users;

// --- "At a glance" statistics: parallax photo + numbers that count up --------
const reducedMotion = () => window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

const statsSection = ref(null);
const parallaxOffset = ref(0);
let parallaxFrame = null;

const updateParallax = () => {
    parallaxFrame = null;
    const el = statsSection.value;
    if (!el) {
        return;
    }
    const rect = el.getBoundingClientRect();
    if (rect.bottom < 0 || rect.top > window.innerHeight) {
        return;
    }
    // -1 when the section enters at the bottom, +1 when it leaves at the top.
    const progress = (window.innerHeight - rect.top) / (window.innerHeight + rect.height) * 2 - 1;
    parallaxOffset.value = progress * rect.height * 0.2;
};
const onParallaxScroll = () => {
    if (!parallaxFrame) {
        parallaxFrame = requestAnimationFrame(updateParallax);
    }
};

const counts = ref(props.metrics.map(() => 0));
let statsObserver = null;

const countUp = () => {
    const targets = props.metrics.map((m) => Number(m.value) || 0);
    if (reducedMotion()) {
        counts.value = targets;
        return;
    }
    const duration = 2000;
    const start = performance.now();
    const step = (now) => {
        const t = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - t, 3); // ease-out: fast start, gentle finish
        counts.value = targets.map((target) => Math.round(target * eased));
        if (t < 1) {
            requestAnimationFrame(step);
        }
    };
    requestAnimationFrame(step);
};

onMounted(() => {
    if (!statsSection.value) {
        return;
    }
    if (!reducedMotion()) {
        updateParallax();
        window.addEventListener('scroll', onParallaxScroll, { passive: true });
        window.addEventListener('resize', onParallaxScroll, { passive: true });
    }
    if ('IntersectionObserver' in window) {
        statsObserver = new IntersectionObserver(
            (entries) => {
                if (entries.some((e) => e.isIntersecting)) {
                    countUp();
                    statsObserver.disconnect(); // count once per visit
                }
            },
            { threshold: 0.35 },
        );
        statsObserver.observe(statsSection.value);
    } else {
        counts.value = props.metrics.map((m) => Number(m.value) || 0);
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onParallaxScroll);
    window.removeEventListener('resize', onParallaxScroll);
    if (parallaxFrame) {
        cancelAnimationFrame(parallaxFrame);
    }
    statsObserver?.disconnect();
});
</script>

<template>
    <section v-if="metrics.length" ref="statsSection" class="relative isolate overflow-hidden py-14 text-white lg:py-16">
        <img
            :src="image"
            alt=""
            width="1200"
            height="1600"
            loading="lazy"
            decoding="async"
            class="absolute inset-x-0 -top-[25%] -z-20 h-[150%] w-full object-cover will-change-transform"
            :style="{ transform: `translate3d(0, ${parallaxOffset}px, 0)` }"
        />
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-yl-forest/95 via-yl-green-deep/85 to-yl-green/75"></div>

        <div class="yl-container grid items-center gap-12 lg:grid-cols-[2fr_3fr]">
            <div v-reveal:left>
                <h2 class="text-3xl font-bold sm:text-4xl">Youthline at a Glance</h2>
                <p class="mt-4 max-w-md text-lg leading-relaxed text-white/85">
                    Every number is a young person getting the education, support and
                    opportunities they need to build a brighter future.
                </p>
                <Link
                    :href="route('impact')"
                    class="mt-6 inline-flex items-center gap-2 font-semibold text-yl-gold transition hover:text-white"
                >
                    See our full impact <ArrowRight class="h-4 w-4" />
                </Link>
            </div>

            <dl v-reveal.stagger="150" class="grid grid-cols-2 gap-y-10 sm:grid-cols-4">
                <div
                    v-for="(metric, i) in metrics"
                    :key="metric.key"
                    class="flex flex-col items-center px-4 text-center"
                    :class="{ 'border-white/40 sm:border-l': i > 0, 'border-l': i % 2 === 1 }"
                >
                    <component :is="iconForMetric(metric.key)" class="h-10 w-10 stroke-[1.5]" aria-hidden="true" />
                    <!-- dt comes first for valid markup; order-last shows the label under the number -->
                    <dt class="order-last mt-2 text-sm font-medium uppercase tracking-wide text-white/90">
                        {{ metric.label }}
                    </dt>
                    <dd class="mt-4 text-4xl font-bold tabular-nums sm:text-5xl">
                        <!-- Screen readers get the real number; the animated one is visual only -->
                        <span class="sr-only">{{ Number(metric.value).toLocaleString() }}</span>
                        <span aria-hidden="true">{{ counts[i].toLocaleString() }}</span>
                    </dd>
                </div>
            </dl>
        </div>
    </section>
</template>

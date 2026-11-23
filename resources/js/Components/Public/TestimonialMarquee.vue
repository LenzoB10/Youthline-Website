<script setup>
import { Quote } from 'lucide-vue-next';
import { computed } from 'vue';

// Portrait testimonial cards that glide sideways in an endless loop.
// Each card has a white frame, the person's photo (or a placeholder picture until one is uploaded)
// and the same dark Youthline-green fade behind the quote, name and role.
// The list is repeated so the loop has no gap; hovering (or keyboard focus) pauses it, and people
// who ask for reduced motion get a still row they can scroll instead.
const props = defineProps({
    testimonials: { type: Array, required: true },
});

const track = computed(() => {
    const base = [];
    // Fill at least ~6 cards so the row is always wider than the screen.
    while (base.length < 6) {
        base.push(...props.testimonials);
    }
    return [...base, ...base]; // second copy makes the -50% loop seamless
});
const duration = computed(() => `${Math.max(30, track.value.length * 4)}s`);
</script>

<template>
    <div class="marquee relative overflow-hidden py-4" role="region" aria-label="Testimonials" tabindex="0">
        <ul class="marquee-track flex w-max gap-6" :style="{ '--marquee-duration': duration }">
            <!-- White frame around each card, like the reference -->
            <li
                v-for="(t, i) in track"
                :key="`${t.id}-${i}`"
                class="w-[17rem] shrink-0 rounded-xl bg-white p-2.5 shadow-md sm:w-[20rem]"
                :aria-hidden="i >= track.length / 2 ? 'true' : undefined"
            >
                <div class="group relative isolate flex aspect-[3/4] flex-col justify-end overflow-hidden rounded-lg bg-slate-200 text-white">
                    <!-- Photo, or a placeholder picture until one is uploaded -->
                    <img
                        v-if="t.photo"
                        :src="t.photo"
                        alt=""
                        loading="lazy"
                        decoding="async"
                        class="absolute inset-0 -z-20 h-full w-full object-cover object-[center_25%] transition duration-700 group-hover:scale-105"
                    />
                    <svg v-else class="absolute left-1/2 top-[44%] -z-20 w-3/4 -translate-x-1/2 -translate-y-1/2" viewBox="0 0 240 150" aria-hidden="true">
                        <rect width="240" height="150" fill="#94a3b8" />
                        <circle cx="62" cy="48" r="16" fill="#e2e8f0" />
                        <path d="M0 150 L80 78 L150 150 Z" fill="#475569" />
                        <path d="M110 150 L178 92 L240 150 Z" fill="#334155" />
                    </svg>

                    <!-- Dark Youthline-green fade behind the text, the same on every card -->
                    <div class="absolute inset-x-0 bottom-0 -z-10 h-[62%] bg-gradient-to-t from-yl-forest via-yl-forest/85 to-transparent" aria-hidden="true"></div>

                    <div class="p-5 sm:p-6">
                        <Quote class="h-5 w-5 text-yl-gold" aria-hidden="true" />
                        <blockquote class="mt-2 line-clamp-4 text-sm leading-relaxed text-white/90">“{{ t.quote }}”</blockquote>
                        <p class="mt-4 text-xl font-bold leading-tight sm:text-2xl">{{ t.name }}</p>
                        <p v-if="t.role" class="mt-1 text-white/80">{{ t.role }}</p>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>

<style scoped>
/* Soft fade at both edges so cards glide in and out. */
.marquee {
    mask-image: linear-gradient(to right, transparent, #000 5%, #000 95%, transparent);
    -webkit-mask-image: linear-gradient(to right, transparent, #000 5%, #000 95%, transparent);
}
.marquee-track {
    animation: marquee var(--marquee-duration, 40s) linear infinite;
}
.marquee:hover .marquee-track,
.marquee:focus-within .marquee-track {
    animation-play-state: paused;
}
@keyframes marquee {
    from {
        transform: translateX(0);
    }
    to {
        /* half the track plus half a gap, so the second copy lands exactly where the first began */
        transform: translateX(calc(-50% - 0.75rem));
    }
}
@media (prefers-reduced-motion: reduce) {
    .marquee {
        overflow-x: auto;
        mask-image: none;
        -webkit-mask-image: none;
    }
    .marquee-track {
        animation: none;
    }
}
</style>

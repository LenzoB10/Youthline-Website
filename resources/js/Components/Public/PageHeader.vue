<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
    // When set, the header becomes a photo banner: centred title + breadcrumb.
    image: { type: String, default: '' },
    // Optional middle breadcrumb, e.g. { label: 'Our Programs', href: '/programs' }
    parent: { type: Object, default: null },
});
</script>

<template>
    <!-- Photo banner -->
    <section v-if="image" class="relative isolate overflow-hidden bg-yl-forest text-white">
        <img
            :src="image"
            alt=""
            width="1200"
            height="1600"
            fetchpriority="high"
            class="yl-slow-zoom absolute inset-0 -z-20 h-full w-full object-cover object-[center_35%]"
        />
        <div class="absolute inset-0 -z-10 bg-yl-green-deep/50"></div>

        <div class="yl-container flex flex-col items-center py-24 text-center sm:py-32">
            <h1 class="yl-enter yl-enter-1 max-w-4xl text-4xl font-bold sm:text-5xl">{{ title }}</h1>
            <nav aria-label="Breadcrumb" class="yl-enter yl-enter-2 mt-4">
                <ol class="flex flex-wrap items-center justify-center gap-1.5 text-sm text-white/85">
                    <li>
                        <Link :href="route('home')" class="transition hover:text-yl-gold">Youthline</Link>
                    </li>
                    <li aria-hidden="true"><ChevronRight class="h-4 w-4" /></li>
                    <template v-if="parent">
                        <li>
                            <Link :href="parent.href" class="transition hover:text-yl-gold">{{ parent.label }}</Link>
                        </li>
                        <li aria-hidden="true"><ChevronRight class="h-4 w-4" /></li>
                    </template>
                    <li aria-current="page" class="font-medium text-white">{{ title }}</li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Plain banner -->
    <section v-else class="bg-yl-green-deep text-white">
        <div class="yl-container py-16 sm:py-20">
            <p v-if="eyebrow" class="yl-enter yl-enter-1 yl-eyebrow text-yl-gold">{{ eyebrow }}</p>
            <h1 class="yl-enter yl-enter-2 mt-3 text-4xl font-bold sm:text-5xl">{{ title }}</h1>
            <p v-if="subtitle" class="yl-enter yl-enter-3 mt-4 max-w-2xl text-lg text-white/80">
                {{ subtitle }}
            </p>
        </div>
    </section>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BriefcaseBusiness,
    GraduationCap,
    HandHeart,
    HousePlus,
    Sprout,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    program: { type: Object, required: true },
});

const iconBySlug = {
    'educational-support-and-scholarships': GraduationCap,
    'basic-needs-and-essential-support': HandHeart,
    'mentorship-and-psychosocial-support': Users,
    'career-development-and-guidance': BriefcaseBusiness,
    'family-and-community-support': HousePlus,
    'youth-entrepreneurship-and-economic-empowerment': Sprout,
};

const icon = computed(() => iconBySlug[props.program.slug] ?? GraduationCap);
</script>

<template>
    <article class="group relative flex h-full flex-col overflow-hidden bg-yl-green/5 transition hover:shadow-lg">
        <div class="aspect-[16/10] overflow-hidden bg-yl-green/10">
            <img
                :src="program.image"
                alt=""
                loading="lazy"
                decoding="async"
                class="h-full w-full object-cover object-[center_30%] transition duration-500 group-hover:scale-105"
            />
        </div>

        <div class="relative flex flex-1 flex-col p-6 sm:p-7">
            <h3 class="text-xl font-bold leading-snug text-yl-green-deep">
                <!-- The whole card is clickable through this stretched link -->
                <Link :href="route('programs.show', program.slug)" class="after:absolute after:inset-0 focus:outline-none">
                    {{ program.name }}
                </Link>
            </h3>
            <p class="mt-3 flex-1 leading-relaxed text-yl-ink/70">{{ program.summary }}</p>
            <span
                class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-yl-green transition group-hover:gap-2.5"
                aria-hidden="true"
            >
                Read more <ArrowRight class="h-4 w-4" />
            </span>

            <!-- Faint program icon in the corner, like the book mark in the design -->
            <component
                :is="icon"
                class="pointer-events-none absolute bottom-5 right-6 h-9 w-9 text-yl-green/15"
                aria-hidden="true"
            />
        </div>

        <!-- Visible focus ring for keyboard users on the stretched link -->
        <span class="pointer-events-none absolute inset-0 ring-yl-green ring-offset-2 group-has-[:focus-visible]:ring-2"></span>
    </article>
</template>

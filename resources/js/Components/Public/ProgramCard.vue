<script setup>
import {
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
    detailed: { type: Boolean, default: false },
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
    <article class="flex h-full flex-col rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition hover:border-yl-green/40 hover:shadow-md">
        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-yl-green/10 text-yl-green">
            <component :is="icon" class="h-6 w-6" />
        </span>
        <h3 class="mt-5 text-lg font-bold text-yl-green-deep">{{ program.name }}</h3>
        <p class="mt-2 flex-1 text-sm text-yl-ink/70">
            {{ detailed ? program.description : program.summary }}
        </p>
        <p
            v-if="program.student_count"
            class="mt-4 text-sm font-semibold text-yl-green"
        >
            {{ program.student_count.toLocaleString() }}
            {{ program.student_count === 1 ? 'student supported' : 'students supported' }}
        </p>
    </article>
</template>

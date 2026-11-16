<script setup>
import PageHeader from '@/Components/Public/PageHeader.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Users } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    program: { type: Object, required: true },
    otherPrograms: { type: Array, default: () => [] },
});

const paragraphs = computed(() =>
    (props.program.description ?? '').split(/\n\s*\n/).filter((line) => line.trim().length > 0),
);
</script>

<template>
    <Head :title="program.name" />

    <PublicLayout>
        <PageHeader
            :title="program.name"
            :image="program.image"
            :parent="{ label: 'Our Programs', href: route('programs') }"
        />

        <section class="py-16 sm:py-20">
            <div class="yl-container grid gap-12 lg:grid-cols-[2fr_1fr]">
                <!-- Program details -->
                <article v-reveal>
                    <p v-if="program.summary" class="text-xl font-semibold leading-relaxed text-yl-green-deep sm:text-2xl">
                        {{ program.summary }}
                    </p>

                    <img
                        :src="program.image"
                        :alt="program.name"
                        class="mt-8 aspect-[16/9] w-full object-cover object-[center_30%] shadow-md"
                    />

                    <div class="mt-8 space-y-5 text-lg leading-relaxed text-yl-ink/75">
                        <p v-for="(para, i) in paragraphs" :key="i">{{ para }}</p>
                    </div>

                    <p
                        v-if="program.student_count"
                        class="mt-8 inline-flex items-center gap-2 bg-yl-green/10 px-5 py-2 text-sm font-semibold text-yl-green"
                    >
                        <Users class="h-4 w-4" aria-hidden="true" />
                        {{ program.student_count.toLocaleString() }}
                        {{ program.student_count === 1 ? 'young person supported' : 'young people supported' }}
                    </p>

                    <div class="mt-10">
                        <Link
                            :href="route('programs')"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-yl-green hover:text-yl-green-dark"
                        >
                            <ArrowLeft class="h-4 w-4" /> All programs
                        </Link>
                    </div>
                </article>

                <!-- Sidebar -->
                <aside v-reveal.stagger="150" class="space-y-8">
                    <div class="bg-yl-green-deep p-8 text-white">
                        <h2 class="text-xl font-bold">Support this program</h2>
                        <p class="mt-3 text-white/80">
                            Sponsor a student, volunteer, or donate to help more young people through this program.
                        </p>
                        <Link :href="route('get-involved')" class="yl-btn-accent mt-6">Get involved</Link>
                    </div>

                    <div v-if="otherPrograms.length" class="bg-yl-cream p-8">
                        <h2 class="text-lg font-bold text-yl-green-deep">Other programs</h2>
                        <ul class="mt-4 divide-y divide-gray-200">
                            <li v-for="other in otherPrograms" :key="other.id">
                                <Link
                                    :href="route('programs.show', other.slug)"
                                    class="group flex items-center gap-4 py-3"
                                >
                                    <img
                                        :src="other.image"
                                        alt=""
                                        loading="lazy"
                                        class="h-14 w-14 shrink-0 object-cover"
                                    />
                                    <span class="flex-1 text-sm font-semibold text-yl-green-deep group-hover:text-yl-green">
                                        {{ other.name }}
                                    </span>
                                    <ArrowRight class="h-4 w-4 shrink-0 text-yl-green opacity-0 transition group-hover:opacity-100" />
                                </Link>
                            </li>
                        </ul>
                    </div>
                </aside>
            </div>
        </section>
    </PublicLayout>
</template>

<script setup>
import PageHeader from '@/Components/Public/PageHeader.vue';
import ProgramCard from '@/Components/Public/ProgramCard.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    page: { type: Object, default: null },
    programs: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Our Programs" />

    <PublicLayout>
        <PageHeader title="Our Programs" image="/imgs/web/lg/IMG20261028134006.jpg" />

        <section class="py-16 sm:py-20">
            <div class="yl-container">
                <div v-reveal.stagger="120" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <ProgramCard v-for="program in programs" :key="program.id" :program="program" />
                </div>
            </div>
        </section>

        <section class="bg-yl-cream py-16">
            <div v-reveal:zoom class="yl-container flex flex-col items-center justify-between gap-6 text-center sm:flex-row sm:text-left">
                <div>
                    <h2 class="text-2xl font-bold text-yl-green-deep">Want to support a program?</h2>
                    <p class="mt-2 text-yl-ink/70">Sponsor a student, volunteer, or donate to the work you care about.</p>
                </div>
                <Link :href="route('get-involved')" class="yl-btn-primary shrink-0">Get involved</Link>
            </div>
        </section>
    </PublicLayout>
</template>

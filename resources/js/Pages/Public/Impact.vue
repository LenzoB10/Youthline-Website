<script setup>
import PageHeader from '@/Components/Public/PageHeader.vue';
import StatTile from '@/Components/Public/StatTile.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    metrics: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Our Impact" />

    <PublicLayout>
        <PageHeader title="Our Impact" image="/imgs/web/lg/IMG20261028134008.jpg" />

        <section class="py-16">
            <div class="yl-container">
                <div v-reveal.stagger="90" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <StatTile
                        v-for="metric in metrics"
                        :key="metric.key"
                        :value="metric.value"
                        :label="metric.label"
                    />
                </div>
                <p class="mt-10 text-center text-sm text-yl-ink/60">
                    Figures update automatically as the Youthline team records support. We never
                    publish names, addresses or any private information.
                </p>
            </div>
        </section>

        <section class="bg-yl-green-deep py-16 text-white">
            <div v-reveal:zoom class="yl-container flex flex-col items-center justify-between gap-6 text-center sm:flex-row sm:text-left">
                <div>
                    <h2 class="text-2xl font-bold sm:text-3xl">Be part of the next number</h2>
                    <p class="mt-2 text-white/80">Your support turns these figures into futures.</p>
                </div>
                <Link :href="route('get-involved')" class="yl-btn-accent shrink-0">Get involved</Link>
            </div>
        </section>
    </PublicLayout>
</template>

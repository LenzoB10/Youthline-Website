<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Home } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    status: { type: Number, required: true },
});

const content = computed(
    () =>
        ({
            403: {
                title: 'Access denied',
                message: "You don't have permission to view this page.",
            },
            404: {
                title: 'Page not found',
                message: "Sorry, we couldn't find the page you were looking for. It may have moved, or the link may be out of date.",
            },
            500: {
                title: 'Something went wrong',
                message: "Something went wrong on our side. Please try again in a moment. If the problem continues, let us know.",
            },
            503: {
                title: 'Down for maintenance',
                message: "We're making some improvements and will be back shortly. Thank you for your patience.",
            },
        })[props.status] ?? { title: 'Error', message: 'Something unexpected happened.' },
);

const helpfulLinks = [
    { label: 'About Youthline', route: 'about' },
    { label: 'Our Programs', route: 'programs' },
    { label: 'News and Stories', route: 'news' },
    { label: 'Contact Us', route: 'contact' },
];
</script>

<template>
    <Head :title="content.title" />

    <PublicLayout>
        <section class="bg-yl-cream">
            <div class="yl-container py-20 text-center sm:py-28">
                <p class="text-7xl font-extrabold tracking-tight text-yl-green sm:text-8xl">{{ status }}</p>
                <h1 class="mt-4 text-3xl font-bold text-yl-green-deep sm:text-4xl">{{ content.title }}</h1>
                <p class="mx-auto mt-4 max-w-xl text-lg text-yl-ink/70">{{ content.message }}</p>

                <div class="mt-8 flex flex-wrap justify-center gap-4">
                    <Link :href="route('home')" class="yl-btn-primary">
                        <Home class="h-4 w-4" /> Back to home
                    </Link>
                    <Link v-if="status >= 500" :href="route('contact')" class="yl-btn-outline">
                        Contact us
                    </Link>
                </div>

                <nav v-if="status === 404" class="mt-12" aria-label="Helpful links">
                    <p class="text-sm font-semibold text-yl-ink/60">You might be looking for:</p>
                    <ul class="mt-3 flex flex-wrap justify-center gap-x-6 gap-y-2">
                        <li v-for="link in helpfulLinks" :key="link.route">
                            <Link
                                :href="route(link.route)"
                                class="inline-flex items-center gap-1 text-sm font-medium text-yl-green hover:underline"
                            >
                                {{ link.label }} <ArrowRight class="h-3.5 w-3.5" />
                            </Link>
                        </li>
                    </ul>
                </nav>
            </div>
        </section>
    </PublicLayout>
</template>

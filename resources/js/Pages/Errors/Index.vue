<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ArrowLeft } from 'lucide-vue-next';

const props = defineProps({ status: Number });

const title = computed(() => {
    return {
        503: 'Service Unavailable',
        500: 'Server Error',
        404: 'Page Not Found',
        403: 'Forbidden',
    }[props.status];
});

const description = computed(() => {
    return {
        503: 'Sorry, we are doing some maintenance. Please check back soon.',
        500: 'Whoops, something went wrong on our servers.',
        404: 'Sorry, the page you are looking for could not be found.',
        403: 'Sorry, you are forbidden from accessing this page.',
    }[props.status];
});

let back = function () {
    window.history.back();
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="title" />

        <div class="card-container flex items-center justify-center">
            <div class="mt-12 w-full max-w-2xl px-6">
                <div class="text-center">
                    <!-- Error Number -->
                    <div class="mb-4">
                        <h1
                            class="text-9xl font-extrabold tracking-widest text-mustBlue dark:text-text-inverted"
                        >
                            {{ status }}
                        </h1>
                        <div
                            class="absolute rotate-12 rounded bg-[#FF6A3D] px-2 text-sm"
                        >
                            {{ title }}
                        </div>
                    </div>

                    <!-- Error Message -->
                    <div class="mb-8">
                        <h2
                            class="text-xl text-text-muted dark:text-text-dark-muted"
                        >
                            {{ description }}
                        </h2>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mb-12 mt-4 flex justify-center gap-4">
                        <button
                            type="button"
                            class="btn-primary"
                            @click="back()"
                        >
                            <ArrowLeft class="mr-2" />
                            Go back to safety
                        </button>
                        <Link :href="route('dashboard')" class="btn-outline">
                            Home
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

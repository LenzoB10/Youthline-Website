<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

/**
 * "← Back to …" link at the top-right corner of a page, in brand green.
 * Place it at the very top of the page content (before the heading or header card).
 */
defineProps({
    href: { type: String, required: true },
});
</script>

<template>
    <div class="mb-3 flex justify-end">
        <Link
            :href="href"
            class="inline-flex items-center gap-1 whitespace-nowrap text-sm font-medium text-mustGreen hover:underline focus:outline-none focus-visible:underline"
        >
            <ArrowLeft class="h-4 w-4" aria-hidden="true" />
            <slot />
        </Link>
    </div>
</template>

<template>
    <Link
        :href="route(routeName, routeParams)"
        class="flex items-center gap-2 text-mustGreen"
    >
    <ArrowLeft class="h-4 w-4" />
        Back
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

defineProps({
    routeName: {
        type: String,
        required: true,
    },
    routeParams: {
        type: Object,
        required: false,
    },
});
</script>

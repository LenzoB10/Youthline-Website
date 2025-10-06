<template>
    <div class="hidden md:flex items-center">
        <h2 class=" font-medium text-text-base dark:text-text-inverted">
            {{ greeting }}, {{ firstName }} 👋
        </h2>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const firstName = computed(() => {
    const userName = page.props.auth?.user?.name || 'User';
    return userName.split(' ')[0];
});

const greeting = computed(() => {
    const hour = new Date().getHours();
    
    if (hour >= 5 && hour < 12) {
        return 'Good morning';
    } else if (hour >= 12 && hour < 17) {
        return 'Good afternoon';
    } else if (hour >= 17 && hour < 22) {
        return 'Good evening';
    } else {
        return 'Good evening';
    }
});
</script>

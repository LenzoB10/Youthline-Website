<!-- DashboardCard.vue -->
<template>
    <div class="card-container transform transition-transform hover:scale-105">
        <div class="mb-2 flex items-center justify-between">
            <h3
                class="text-sm font-medium text-text-muted dark:text-text-dark-muted"
            >
                {{ title }}
            </h3>
            <div class="rounded-full bg-accent/10 p-2">
                <component :is="iconComponent" class="h-5 w-5 text-mustGreen" />
            </div>
        </div>
        <p class="text-2xl font-bold text-mustGreen">{{ value }}</p>
    </div>
</template>

<script setup>
import { defineProps, computed } from 'vue';
import * as icons from 'lucide-vue-next';

const props = defineProps({
    title: String,
    value: String,
    icon: String,
});

const iconComponent = computed(
    () => icons[props.icon.charAt(0).toUpperCase() + props.icon.slice(1)],
);
</script>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    label: { type: String, default: null },
});

const styles = {
    applicant: 'bg-yl-gold/15 text-amber-800 dark:text-yl-gold',
    active: 'bg-mustGreen/15 text-yl-green-deep dark:text-accent-light',
    graduated: 'bg-yl-gold/15 text-yl-green-deep ring-1 ring-inset ring-yl-gold/50 dark:text-yl-gold',
    dropped_out: 'bg-gray-700 text-white dark:bg-gray-300 dark:text-gray-900',
    transferred: 'border border-gray-300 bg-white text-gray-600 dark:border-gray-500 dark:bg-transparent dark:text-gray-300',
    paused: 'bg-gray-200 text-gray-700 dark:bg-gray-500/20 dark:text-gray-300',
};

const text = computed(() => props.label ?? props.status.replace('_', ' '));
</script>

<template>
    <span class="inline-flex whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="styles[status] ?? styles.paused">
        {{ text }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    label: { type: String, default: null },
});

const styles = {
    applicant: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    active: 'bg-green-100 text-green-800 dark:bg-green-500/15 dark:text-green-300',
    graduated: 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
    dropped_out: 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300',
    transferred: 'bg-violet-100 text-violet-800 dark:bg-violet-500/15 dark:text-violet-300',
    paused: 'bg-gray-200 text-gray-700 dark:bg-gray-500/20 dark:text-gray-300',
};

const text = computed(() => props.label ?? props.status.replace('_', ' '));
</script>

<template>
    <span class="inline-flex whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="styles[status] ?? styles.paused">
        {{ text }}
    </span>
</template>

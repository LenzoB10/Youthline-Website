<template>
    <div class="flex flex-col items-center justify-center py-8">
        <svg
            class="h-12 w-12 animate-ping text-text-muted dark:text-text-dark-muted"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
        </svg>
        <h3 class="mt-2 font-medium text-text-base dark:text-text-dark-muted">
            {{ message }}
        </h3>
        <p
            v-if="description"
            class="mt-1 text-sm text-text-muted dark:text-text-dark-muted"
        >
            {{ description }}
        </p>
        <slot></slot>
    </div>
</template>

<script setup>
defineProps({
    message: {
        type: String,
        default: 'No record found',
    },
    description: {
        required: false,
        type: String,
    },
});
</script>

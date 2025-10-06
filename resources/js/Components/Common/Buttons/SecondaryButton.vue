<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="border-primary-300 bg-primary-50 text-primary-700 hover:bg-primary-100 focus:ring-primary-500 dark:border-primary-500 dark:bg-primary-800 dark:text-primary-300 dark:hover:bg-primary-700 dark:focus:ring-offset-primary-800 inline-flex items-center rounded-md border px-4 py-2 text-xs font-semibold uppercase tracking-widest shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-25"
    >
        <slot />
    </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 },
    label: { type: String, required: true },
});

const formatted = computed(() => Number(props.value ?? 0).toLocaleString());
</script>

<template>
    <article class="rounded-md border border-gray-100 bg-white px-6 py-10 text-center shadow-sm">
        <p class="text-5xl font-bold text-yl-green">{{ formatted }}</p>
        <p class="mt-3 text-yl-ink/70">{{ label }}</p>
    </article>
</template>

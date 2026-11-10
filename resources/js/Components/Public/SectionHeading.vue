<script setup>
defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    align: { type: String, default: 'center' },
});
</script>

<template>
    <div :class="align === 'center' ? 'mx-auto max-w-2xl text-center' : 'max-w-2xl'">
        <p v-if="eyebrow" class="yl-eyebrow">{{ eyebrow }}</p>
        <h2 class="yl-h2 mt-3">{{ title }}</h2>
        <p v-if="subtitle" class="mt-4 text-yl-ink/70">{{ subtitle }}</p>
    </div>
</template>

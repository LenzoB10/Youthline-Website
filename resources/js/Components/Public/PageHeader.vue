<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
});
</script>

<template>
    <section class="bg-yl-green-deep text-white">
        <div class="yl-container py-16 sm:py-20">
            <p v-if="eyebrow" class="yl-eyebrow text-yl-gold">{{ eyebrow }}</p>
            <h1 class="mt-3 text-4xl font-bold sm:text-5xl">{{ title }}</h1>
            <p v-if="subtitle" class="mt-4 max-w-2xl text-lg text-white/80">
                {{ subtitle }}
            </p>
        </div>
    </section>
</template>

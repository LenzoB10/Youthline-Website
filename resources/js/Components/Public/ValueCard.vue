<script setup>
defineProps({
    value: { type: Object, required: true },
});
</script>

<template>
    <div class="rounded-2xl border border-gray-100 bg-yl-cream p-6">
        <h3 class="text-base font-bold text-yl-green-deep">{{ value.name }}</h3>
        <p class="mt-2 text-sm text-yl-ink/70">{{ value.description }}</p>
    </div>
</template>

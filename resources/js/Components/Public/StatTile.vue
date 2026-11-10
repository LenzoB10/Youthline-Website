<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 },
    label: { type: String, required: true },
    image: { type: String, default: null },
});

const formatted = computed(() => Number(props.value ?? 0).toLocaleString());
</script>

<template>
    <article class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <img
            v-if="image"
            :src="image"
            alt=""
            width="800"
            height="1067"
            loading="lazy"
            decoding="async"
            class="h-32 w-full object-cover"
        />
        <div class="p-5 text-center">
            <p class="text-4xl font-bold text-yl-green">{{ formatted }}</p>
            <p class="mt-1 text-sm text-yl-ink/70">{{ label }}</p>
        </div>
    </article>
</template>

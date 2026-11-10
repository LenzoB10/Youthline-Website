<script setup>
import { computed } from 'vue';

const props = defineProps({
    member: { type: Object, required: true },
});

const initials = computed(() =>
    (props.member.name ?? '')
        .split(' ')
        .map((part) => part.charAt(0))
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);
</script>

<template>
    <div class="text-center">
        <img
            v-if="member.photo"
            :src="member.photo"
            :alt="member.name"
            class="mx-auto h-28 w-28 rounded-full object-cover"
        />
        <span
            v-else
            class="mx-auto flex h-28 w-28 items-center justify-center rounded-full bg-yl-green/10 text-2xl font-bold text-yl-green"
        >
            {{ initials }}
        </span>
        <h3 class="mt-4 text-base font-bold text-yl-green-deep">{{ member.name }}</h3>
        <p class="text-sm font-medium text-yl-green">{{ member.position }}</p>
        <p v-if="member.bio" class="mt-2 text-sm text-yl-ink/70">{{ member.bio }}</p>
    </div>
</template>

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
    <article class="flex h-full flex-col rounded-md bg-white p-7 shadow-sm transition hover:shadow-md">
        <p v-if="member.bio" class="flex-1 leading-relaxed text-yl-ink/70">{{ member.bio }}</p>
        <div class="flex items-center gap-4" :class="{ 'mt-6 border-t border-gray-100 pt-6': member.bio }">
            <img
                v-if="member.photo"
                :src="member.photo"
                :alt="member.name"
                loading="lazy"
                class="h-14 w-14 shrink-0 rounded-md object-cover"
            />
            <span
                v-else
                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-md bg-yl-green/10 text-lg font-bold text-yl-green"
                aria-hidden="true"
            >
                {{ initials }}
            </span>
            <div>
                <h3 class="text-lg font-bold text-yl-green-deep">{{ member.name }}</h3>
                <p class="text-sm text-yl-green">{{ member.position }}</p>
            </div>
        </div>
    </article>
</template>

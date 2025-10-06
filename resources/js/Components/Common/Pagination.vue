<script setup>

const props = defineProps({
    links: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['goToPage']);

const goToPage = (url) => {
    if (!url) return;
    emit('goToPage', url);
};
</script>

<template>
    <div class="flex justify-center">
        <div class="flex space-x-1">
            <button
                v-for="(link, index) in links"
                :key="index"
                @click="goToPage(link.url)"
                class="rounded-md px-2 sm:px-3 py-1 sm:py-2 text-xs sm:text-sm transition-colors duration-200 min-w-[32px] sm:min-w-[36px] flex items-center justify-center"
                :class="{
                    'bg-mustGreen text-text-inverted': link.active,
                    'text-text-base hover:bg-mustGreen hover:text-text-inverted dark:text-text-dark-muted':
                        !link.active && link.url,
                    'cursor-not-allowed text-text-muted dark:text-text-dark-muted':
                        !link.url,
                }"
                v-html="link.label"
                :disabled="!link.url"
            ></button>
        </div>
    </div>
</template>
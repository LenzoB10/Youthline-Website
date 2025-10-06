<template>
    <button
        @click="toggleFullscreen"
        class="flex h-9 w-9 items-center justify-center rounded-full text-text-muted transition-colors hover:bg-background-soft dark:hover:bg-background-dark-soft"
        :title="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'"
    >
        <Maximize v-if="!isFullscreen" class="h-5 w-5" />
        <Minimize v-else class="h-5 w-5" />
    </button>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Maximize, Minimize } from 'lucide-vue-next';

const isFullscreen = ref(false);

const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
        isFullscreen.value = true;
    } else if (document.exitFullscreen) {
        document.exitFullscreen();
        isFullscreen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('fullscreenchange', () => {
        isFullscreen.value = !!document.fullscreenElement;
    });
});

onUnmounted(() => {
    document.removeEventListener('fullscreenchange', () => {});
});
</script>

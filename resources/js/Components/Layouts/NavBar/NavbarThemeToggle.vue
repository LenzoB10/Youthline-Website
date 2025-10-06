<template>
    <button
        @click="toggleTheme"
        class="flex h-9 w-9 items-center justify-center rounded-full text-text-muted transition-colors hover:bg-background-soft dark:hover:bg-background-dark-soft"
        :title="darkMode ? 'Switch to light mode' : 'Switch to dark mode'"
    >
        <component :is="darkMode ? Sun : Moon" class="h-5 w-5" />
    </button>
</template>

<script setup>
import { storeToRefs } from 'pinia';
import { Sun, Moon } from 'lucide-vue-next';
import { useThemeStore } from '@/Stores/theme.js';

const theme = useThemeStore();
const { darkMode } = storeToRefs(theme);

const toggleTheme = () => {
    theme.toggleDarkMode();
};
</script>

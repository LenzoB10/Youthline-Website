<script setup>
import { Moon, Sun } from 'lucide-vue-next';
import { useThemeStore } from '@/Stores/theme';

const themeStore = useThemeStore();
</script>

<template>
    <div
        class="relative flex min-h-screen items-center justify-center bg-background px-4 text-text-base dark:bg-background-dark dark:text-text-inverted"
        style="
            background-image: url('/imgs/login-bg-ulendo.png');
            background-size: cover;
            background-position: center;
        "
    >
        <div class="absolute right-4 top-4">
            <button
                @click="themeStore.toggleDarkMode"
                class="rounded-full bg-background-soft/75 p-2 text-text-muted transition hover:text-accent dark:bg-background-dark-card/75 dark:text-text-dark-muted"
            >
                <component
                    :is="themeStore.darkMode ? Sun : Moon"
                    class="h-5 w-5"
                />
            </button>
        </div>

        <!-- Auth Container -->
        <div class="w-full max-w-md">
            <!-- Slot for Forms (login, reset, etc.) -->
            <div
                class="rounded-lg bg-background-card/55 px-6 py-8 shadow-lg dark:bg-background-dark-card/75"
            >
                <div
                    class="mb-3 flex flex-col items-center justify-center text-2xl font-bold text-mustBlue dark:text-text-inverted md:text-3xl lg:text-4xl"
                >
                    <img
                        :src="
                            themeStore.darkMode
                                ? '/imgs/logo/must-logo-white.png'
                                : '/imgs/logo/must-logo-black.png'
                        "
                        alt=""
                        class="w-16"
                    />
                    <h1 class="mt-2">ReDMIS</h1>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>

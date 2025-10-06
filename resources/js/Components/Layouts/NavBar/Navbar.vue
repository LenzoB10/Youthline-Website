<template>
    <header
        class="fixed right-0 top-0 z-20 flex h-16 w-full items-center justify-between border-b bg-background px-6 shadow-sm transition-all duration-300 dark:border-gray-700 dark:bg-background-dark-soft"
        :class="{
            'md:left-64': sidebar.isOpen,
            'md:w-[calc(100%-16rem)]': sidebar.isOpen,
        }"
    >
        <div class="flex items-center">
            <button
                @click="sidebar.toggle"
                class="mr-4 flex h-9 w-9 items-center justify-center rounded-full text-text-muted transition-colors hover:bg-background-soft dark:hover:bg-background-dark-soft"
            >
                <Menu class="h-5 w-5" />
            </button>

            <NavbarGreeting />
        </div>

        <div class="flex items-center gap-1 md:gap-2 lg:gap-5">
            <NavbarThemeToggle />
            <NavbarFullscreenToggle />
            <NavbarNotifications :notifications="notifications" />
            <NavbarProfile>
                <slot name="profile-dropdown"></slot>
            </NavbarProfile>
        </div>
    </header>
</template>

<script setup>
import { Menu } from 'lucide-vue-next';
import { useSidebarStore } from '@/Stores/sidebar.js';
import NavbarGreeting from './NavbarGreeting.vue';
import NavbarThemeToggle from './NavbarThemeToggle.vue';
import NavbarFullscreenToggle from './NavbarFullscreenToggle.vue';
import NavbarNotifications from './NavbarNotifications.vue';
import NavbarProfile from './NavbarProfile.vue';
import { usePage } from '@inertiajs/vue3';

const sidebar = useSidebarStore();

const notifications = usePage().props.auth?.notifications || [];
</script>

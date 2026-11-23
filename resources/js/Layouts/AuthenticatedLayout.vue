<template>
    <div class="app-bg flex min-h-screen font-app text-text-base antialiased dark:text-text-inverted">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content -->
        <div class="flex min-w-0 flex-1 flex-col transition-all duration-300" :class="{ 'md:pl-64': sidebar.isOpen }">
            <Navbar />
            <main class="min-w-0 flex-1 overflow-y-auto px-4 pt-16 transition-all duration-300 sm:px-6">
                <div class="mx-auto max-w-7xl py-4">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import Sidebar from '@/Components/Layouts/Sidebar.vue';
import Navbar from '@/Components/Layouts/NavBar/Navbar.vue';
import { useSidebarStore } from '@/Stores/sidebar';
import { toast } from 'vue3-toastify';
import { useThemeStore } from '@/Stores/theme';
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const sidebar = useSidebarStore();
const theme = useThemeStore();
const page = usePage();

// Watch for flash messages and trigger toast
watch(
    () => page.props.flash,
    (flash) => {
        if (flash) {
            if (flash.success)
                toast.success(flash.success, {
                    icon: '🚀',
                    theme: theme.darkMode === true ? 'dark' : 'light',
                    transition: toast.TRANSITIONS.SLIDE,
                    position: toast.POSITION.BOTTOM_RIGHT,
                });
            if (flash.error)
                toast.error(flash.error, {
                    theme: theme.darkMode === true ? 'dark' : 'light',
                    transition: toast.TRANSITIONS.SLIDE,
                    position: toast.POSITION.BOTTOM_RIGHT,
                });
            if (flash.info)
                toast.info(flash.info, {
                    theme: theme.darkMode === true ? 'dark' : 'light',
                    transition: toast.TRANSITIONS.SLIDE,
                    position: toast.POSITION.BOTTOM_RIGHT,
                });
            if (flash.warning)
                toast.warning(flash.warning, {
                    theme: theme.darkMode === true ? 'dark' : 'light',
                    transition: toast.TRANSITIONS.SLIDE,
                    position: toast.POSITION.BOTTOM_RIGHT,
                });
        }
    },
    { immediate: true, deep: true },
);
</script>

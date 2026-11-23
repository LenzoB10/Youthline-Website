<template>
    <div class="relative">
        <button @click="toggleNotifications"
            class="relative flex h-9 w-9 items-center justify-center rounded-full text-text-muted transition-colors hover:bg-background-soft dark:hover:bg-background-dark-soft"
            :class="{
                'bg-background-soft dark:bg-background-dark-soft':
                    showNotifications,
            }">
            <Bell class="h-5 w-5" />
            <span v-if="hasUnreadNotifications" class="absolute right-1 top-1 h-2 w-2 rounded-full bg-accent animate-ping"></span>
        </button>
        <Transition enter-active-class="transition duration-200 ease-out"
            enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-150 ease-in" leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0">
            <div v-if="showNotifications"
                class="absolute right-0 z-30 mt-2 w-80 origin-top-right overflow-hidden surface-raised rounded-2xl"
                v-click-outside="closeNotifications">
                <div class="border-b border-gray-200 px-4 py-3">
                    <div class="flex justify-between items-center">
                        <h3 class="text-sm font-medium text-text-base dark:text-text-inverted">
                            Notifications
                        </h3>
                        <Link
                            v-if="notifications.length != 0"
                            :href="route('notifications.index')"
                            class="text-xs font-medium text-text-muted transition-colors hover:text-accent"
                        >
                            View All
                        </Link>
                    </div>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    <div v-if="notifications.length === 0"
                        class="px-4 py-8 text-center text-sm text-text-muted dark:text-text-dark-muted">
                        No new notifications
                    </div>
                    <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="notification in notifications" :key="notification.id"
                            class="px-4 py-3 transition-colors hover:bg-background-soft dark:hover:bg-background-dark-soft">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <component :is="notification.icon" class="h-5 w-5 text-gray-400" />
                                </div>
                                <div class="ml-3 flex-1">
                                    <p class="text-sm text-text-base dark:text-text-inverted">
                                        {{ notification.title }}
                                    </p>
                                    <p 
                                        class="mt-1 text-xs text-text-muted dark:text-text-dark-muted"
                                        v-html="notification.message"
                                    ></p>
                                    <p class="mt-1 text-xs text-text-muted dark:text-text-dark-muted">
                                        {{ notification.time }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Bell } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    notifications: {
        type: Array,
        default: () => [],
    },
});

const showNotifications = ref(false);

const hasUnreadNotifications = computed(() => {
    return props.notifications.some((notification) => !notification.read);
});

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value;
};

const closeNotifications = () => {
    showNotifications.value = false;
};
</script>

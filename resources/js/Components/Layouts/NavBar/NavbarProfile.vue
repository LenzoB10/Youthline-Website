<template>
    <div class="relative">
        <button
            @click="toggleProfile"
            class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-gray-200 bg-gray-100 text-sm font-medium text-gray-700 hover:ring-mustGreen focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 dark:border-gray-600 dark:bg-background-dark-card dark:text-gray-200 dark:focus:ring-offset-gray-900"
            :class="{ 'ring-1 ring-mustGreen': showProfile }"
        >
            {{ userInitials }}
        </button>
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="showProfile"
                class="absolute right-0 z-30 mt-2 w-56 origin-top-right rounded-xl border border-gray-200 bg-background-card py-1 shadow-lg dark:border-gray-700 dark:bg-background-dark-card"
                v-click-outside="closeProfile"
            >
                <div
                    class="border-b border-gray-200 px-4 py-3 dark:border-gray-700"
                >
                    <p
                        class="text-sm font-medium text-text-base dark:text-text-inverted"
                    >
                        {{ $page.props.auth?.user?.name }}
                    </p>
                    <p
                        class="truncate text-sm text-text-muted dark:text-text-dark-muted"
                    >
                        {{ $page.props.auth?.user?.email }}
                    </p>
                </div>
                <div class="py-1">
                    <div class="py-1">
                        <Link
                            :href="route('profile.edit')"
                            class="block px-4 py-2 text-sm text-text-base transition-colors hover:bg-background-soft dark:text-text-inverted dark:hover:bg-background-dark-soft"
                        >
                            <div class="flex items-center">
                                <User class="mr-2 h-4 w-4" />
                                <span>Profile</span>
                            </div>
                        </Link>
                        <a
                            href="#"
                            class="block px-4 py-2 text-sm text-text-base transition-colors hover:bg-background-soft dark:text-text-inverted dark:hover:bg-background-dark-soft"
                        >
                            <div class="flex items-center">
                                <Settings class="mr-2 h-4 w-4" />
                                <span>Settings</span>
                            </div>
                        </a>
                        <a
                            href="#"
                            class="block px-4 py-2 text-sm text-text-base transition-colors hover:bg-background-soft dark:text-text-inverted dark:hover:bg-background-dark-soft"
                        >
                            <div class="flex items-center">
                                <HelpCircle class="mr-2 h-4 w-4" />
                                <span>Help</span>
                            </div>
                        </a>
                    </div>
                    <div
                        class="border-t border-gray-200 py-1 dark:border-gray-700"
                    >
                        <form
                            method="post"
                            action="{{ route('logout') }}"
                            class="flex items-center justify-between px-4 py-2 transition duration-200 hover:bg-gray-100 hover:dark:bg-black hover:dark:bg-opacity-30"
                        >
                            <div
                                class="flex items-center text-sm text-red-500 transition-colors hover:bg-background-soft dark:text-text-inverted dark:hover:bg-background-dark-soft"
                            >
                                <LogOut class="mr-2 h-4 w-4" />
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="text-sm capitalize"
                                >
                                    Log out
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { User, Settings, HelpCircle, LogOut } from 'lucide-vue-next';
import { Link, usePage } from '@inertiajs/vue3';

const showProfile = ref(false);

const toggleProfile = () => {
    showProfile.value = !showProfile.value;
};

const closeProfile = () => {
    showProfile.value = false;
};

const userInitials = computed(() => {
    const name = usePage().props.auth?.user?.name || '';
    return name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
});
</script>

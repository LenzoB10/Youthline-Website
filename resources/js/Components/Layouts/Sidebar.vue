<template>
    <div>
        <!-- Mobile overlay -->
        <div v-if="sidebar.isMobile && sidebar.isMobileSidebarOpen" class="fixed inset-0 z-20 bg-black/50 md:hidden"
            @click="sidebar.closeMobile"></div>

        <aside
            class="fixed z-30 flex h-full w-64 flex-col bg-mustBlue shadow-lg transition-all duration-300 dark:bg-background-dark-soft"
            :class="[
                sidebar.isMobile
                    ? sidebar.isMobileSidebarOpen
                        ? 'translate-x-0'
                        : '-translate-x-full'
                    : sidebar.isOpen
                        ? 'translate-x-0'
                        : '-translate-x-full',
                'md:transition-transform md:duration-300',
            ]">
            <a href="/" class="flex flex-shrink-0 items-center gap-2 px-6 py-4 text-2xl font-bold text-text-inverted">
                <img src="/imgs/logo/must-logo-white.png" alt="Logo" class="w-9" />
                <span>ReDMIS</span>
            </a>
            <nav class="flex-grow overflow-y-auto px-3 pb-4">
                <ul class="space-y-2">
                    <li v-for="item in filteredMenuItems" :key="item.name">
                        <div v-if="item.children && item.children.length">
                            <button @click="toggleSubMenu(item)"
                                class="flex w-full items-center justify-between rounded-lg p-3 text-text-inverted transition hover:bg-background-soft hover:text-text-muted dark:text-text-dark-muted dark:hover:bg-accent/10"
                                :class="{
                                    'bg-mustGreen text-text-inverted dark:bg-accent/10 dark:text-text-inverted':
                                        item.isCurrent,
                                }">
                                <div class="flex items-center">
                                    <component :is="item.icon" class="mr-3 h-5 w-5" />
                                    {{ item.name }}
                                </div>
                                <ChevronDown class="h-4 w-4 transform transition-transform duration-200" :class="{
                                    'rotate-180': item.isExpanded,
                                }" />
                            </button>
                            <transition enter-active-class="transition-all duration-200 ease-out"
                                enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-96"
                                leave-active-class="transition-all duration-150 ease-in"
                                leave-from-class="opacity-100 max-h-96" leave-to-class="opacity-0 max-h-0">
                                <ul v-if="item.isExpanded" class="ml-6 space-y-1 overflow-hidden">
                                    <li v-for="child in item.children" :key="child.name">
                                        <Link :href="child.routeLink
                                            ? route(child.routeLink)
                                            : '#'
                                            "
                                            class="flex items-center rounded-lg px-3 py-2 text-sm text-text-inverted transition hover:bg-background-soft hover:text-text-muted dark:text-text-dark-muted dark:hover:bg-accent/10"
                                            :class="{
                                                'bg-mustGreen text-text-inverted dark:bg-accent/10 dark:text-text-inverted':
                                                    child.isCurrent,
                                            }">
                                        <component :is="child.icon" class="mr-3 h-4 w-4" />
                                        {{ child.name }}
                                        </Link>
                                    </li>
                                </ul>
                            </transition>
                        </div>
                        <Link v-else :href="item.routeLink ? route(item.routeLink) : '#'"
                            class="flex items-center rounded-lg p-3 text-text-inverted transition hover:bg-background-soft hover:text-text-muted dark:text-text-dark-muted dark:hover:bg-accent/10"
                            :class="{
                                'bg-mustGreen text-text-inverted dark:bg-accent/10 dark:text-text-inverted':
                                    item.isCurrent,
                            }">
                        <component :is="item.icon" class="mr-3 h-5 w-5" />
                        {{ item.name }}
                        </Link>
                    </li>
                </ul>
            </nav>
        </aside>
    </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, computed } from "vue";
import {
    Home,
    Settings,
    BarChart,
    ChevronDown,
    User,
    Shield,
    Activity,
    Bell,
    Building,
    Truck,
    FileText,
    ClipboardList,
    Map,
    CheckCircle,
    ThumbsUp,
    Car,
    CarFront,
    Wrench,
    Users,
    Store,
    Droplet,
    Fuel,
    BellIcon,
} from "lucide-vue-next";

import { useSidebarStore } from "@/Stores/sidebar.js";
import { Link } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";

const { url: currentUrl } = usePage();
const page = usePage();

const sidebar = useSidebarStore();

const checkScreenSize = () => {
    const isMobile = window.innerWidth < 768;
    sidebar.setMobile(isMobile);
};

onMounted(() => {
    checkScreenSize();
    window.addEventListener("resize", checkScreenSize);
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", checkScreenSize);
    document.body.style.overflow = "";
});

// Safe permission check function
const hasPermission = (permission) => {
    if (permission === null || permission === undefined) {
        return true;
    }

    try {
        const page = usePage();
        const permissions = page?.props?.auth?.permissions || [];
        return permissions.includes(permission);
    } catch (error) {
        console.warn("Error checking permission:", error);
        return false;
    }
};

const menuItems = ref([
    {
        name: "Dashboard",
        icon: Home,
        isCurrent: currentUrl === "/" || currentUrl.startsWith("/dashboard"),
        routeLink: 'dashboard',
        isExpanded: false,
        permission: null,
    },
    {
        name: "Notifications",
        icon: BellIcon,
        routeLink: 'notifications.index',
        isCurrent: currentUrl.startsWith("/notifications"),
        permission: null,
    },
    {
        name: "System",
        icon: Settings,
        permission: "list users",
        isCurrent:
            currentUrl.startsWith("/users") ||
            currentUrl.startsWith("/roles") ||
            currentUrl.startsWith("/activity-logs") ||
            currentUrl.startsWith("/system-settings"),
        isExpanded: false,
        children: [
            {
                name: "Users",
                icon: User,
                routeLink: 'admin.users.index',
                isCurrent: currentUrl.startsWith("/users"),
                permission: "list users",
            },
            {
                name: "Roles & Permissions",
                icon: Shield,
                routeLink: 'admin.roles.index',
                isCurrent: currentUrl.startsWith("/roles"),
                permission: "list roles",
            },
            {
                name: "Activity Logs",
                icon: Activity,
                routeLink: 'admin.activity-logs.index',
                isCurrent: currentUrl.startsWith("/activity-logs"),
                permission: "list audit logs",
            },
        ],
    },
]);


const toggleSubMenu = (item) => {
    // Close all other submenus
    menuItems.value.forEach((menuItem) => {
        if (menuItem !== item) {
            menuItem.isExpanded = false;
        }
    });
    // Toggle the clicked submenu
    expandedStates.value[item.name] = !expandedStates.value[item.name];
    item.isExpanded = expandedStates.value[item.name];
};

// Track expanded state for each menu item
const expandedStates = ref({});

const filteredMenuItems = computed(() => {
    return menuItems.value
        .filter((item) => {
            if (!hasPermission(item.permission)) {
                return false;
            }

            if (item.children) {
                const filteredChildren = item.children.filter((child) =>
                    hasPermission(child.permission),
                );

                return filteredChildren.length > 0;
            }

            return true;
        })
        .map((item) => {
            const newItem = { ...item };

            if (!(item.name in expandedStates.value)) {
                expandedStates.value[item.name] = item.isExpanded || false;
            }

            newItem.isExpanded = expandedStates.value[item.name];

            if (item.children) {
                newItem.children = item.children.filter((child) =>
                    hasPermission(child.permission),
                );
            }

            return newItem;
        });
});
</script>
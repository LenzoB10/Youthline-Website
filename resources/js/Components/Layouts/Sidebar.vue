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
            <a href="/" class="flex flex-shrink-0 items-center gap-2.5 px-6 py-4 text-2xl font-bold text-text-inverted">
                <img src="/imgs/youthline-logo-sm.png" alt="" width="240" height="160" class="h-10 w-auto object-contain" />
                <span>Youthline</span>
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
    ChevronDown,
    User,
    Shield,
    Activity,
    BellIcon,
    DatabaseBackup,
    Globe,
    FileText,
    Newspaper,
    Contact,
    LayoutGrid,
    Heart,
    Users,
    MessageSquare,
    Handshake,
    Mail,
    SlidersHorizontal,
    CalendarDays,
    School,
    Package,
    DoorOpen,
    ShieldCheck,
    GraduationCap,
    UserPlus,
    ArrowUpCircle,
    Wallet,
    HandCoins,
    ClipboardCheck,
    LifeBuoy,
    MessagesSquare,
    Hammer,
    Store,
    HeartHandshake,
    FileBarChart,
} from "lucide-vue-next";

import { useSidebarStore } from "@/Stores/sidebar.js";
import { Link } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";

const { url: currentUrl } = usePage();

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
        // An array means "any of these".
        return Array.isArray(permission)
            ? permission.some((p) => permissions.includes(p))
            : permissions.includes(permission);
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
        name: "Website",
        icon: Globe,
        permission: "manage website content",
        isCurrent: currentUrl.startsWith("/website"),
        isExpanded: false,
        children: [
            {
                name: "Overview",
                icon: Globe,
                routeLink: 'admin.website.index',
                isCurrent: currentUrl === "/website",
                permission: "manage website content",
            },
            {
                name: "Site Settings",
                icon: Settings,
                routeLink: 'admin.website.settings.edit',
                isCurrent: currentUrl.startsWith("/website/settings"),
                permission: "manage website content",
            },
            {
                name: "Pages",
                icon: FileText,
                routeLink: 'admin.website.pages.index',
                isCurrent: currentUrl.startsWith("/website/pages"),
                permission: "manage website content",
            },
            {
                name: "Programs",
                icon: LayoutGrid,
                routeLink: 'admin.website.programs.index',
                isCurrent: currentUrl.startsWith("/website/programs"),
                permission: "manage website content",
            },
            {
                name: "Core Values",
                icon: Heart,
                routeLink: 'admin.website.values.index',
                isCurrent: currentUrl.startsWith("/website/values"),
                permission: "manage website content",
            },
            {
                name: "Team",
                icon: Users,
                routeLink: 'admin.website.team.index',
                isCurrent: currentUrl.startsWith("/website/team"),
                permission: "manage website content",
            },
            {
                name: "Contact Details",
                icon: Contact,
                routeLink: 'admin.website.contact.edit',
                isCurrent: currentUrl.startsWith("/website/contact"),
                permission: "manage website content",
            },
            {
                name: "News & Stories",
                icon: Newspaper,
                routeLink: 'admin.website.news.index',
                isCurrent: currentUrl.startsWith("/website/news"),
                permission: "manage website content",
            },
            {
                name: "Messages",
                icon: MessageSquare,
                routeLink: 'admin.website.messages.index',
                isCurrent: currentUrl.startsWith("/website/messages"),
                permission: "manage website content",
            },
            {
                name: "Get Involved",
                icon: Handshake,
                routeLink: 'admin.website.involvement.index',
                isCurrent: currentUrl.startsWith("/website/involvement"),
                permission: "manage website content",
            },
            {
                name: "Subscribers",
                icon: Mail,
                routeLink: 'admin.website.subscribers.index',
                isCurrent: currentUrl.startsWith("/website/subscribers"),
                permission: "manage website content",
            },
        ],
    },
    {
        name: "Students",
        icon: GraduationCap,
        permission: "list students",
        isCurrent: currentUrl.startsWith("/students"),
        isExpanded: false,
        children: [
            {
                name: "All students",
                icon: Users,
                routeLink: 'students.index',
                isCurrent: currentUrl === "/students" || /^\/students\/\d+/.test(currentUrl) || currentUrl.startsWith("/students?"),
                permission: "list students",
            },
            {
                name: "Add student",
                icon: UserPlus,
                routeLink: 'students.create',
                isCurrent: currentUrl.startsWith("/students/create"),
                permission: "add student",
            },
            {
                name: "Promote students",
                icon: ArrowUpCircle,
                routeLink: 'students.promote.index',
                isCurrent: currentUrl.startsWith("/students/promote"),
                permission: "change student status",
            },
        ],
    },
    {
        name: "Support",
        icon: LifeBuoy,
        permission: ["manage basic needs", "manage mentorship", "manage home visits", "manage businesses"],
        isCurrent: currentUrl.startsWith("/support"),
        isExpanded: false,
        children: [
            {
                name: "Basic needs",
                icon: Package,
                routeLink: 'support.basic-needs.index',
                isCurrent: currentUrl.startsWith("/support/basic-needs"),
                permission: "manage basic needs",
            },
            {
                name: "Mentors",
                icon: MessagesSquare,
                routeLink: 'support.mentors.index',
                isCurrent: currentUrl.startsWith("/support/mentors"),
                permission: "manage mentorship",
            },
            {
                name: "Families & repairs",
                icon: Hammer,
                routeLink: 'support.families',
                isCurrent: currentUrl.startsWith("/support/families"),
                permission: "manage home visits",
            },
            {
                name: "Businesses",
                icon: Store,
                routeLink: 'support.businesses',
                isCurrent: currentUrl.startsWith("/support/businesses"),
                permission: "manage businesses",
            },
        ],
    },
    {
        name: "Sponsors",
        icon: HeartHandshake,
        routeLink: 'sponsors.index',
        isCurrent: currentUrl.startsWith("/sponsors"),
        permission: "manage sponsors",
    },
    {
        name: "Reports",
        icon: FileBarChart,
        routeLink: 'reports.index',
        isCurrent: currentUrl.startsWith("/reports"),
        permission: "export reports",
    },
    {
        name: "Finance",
        icon: Wallet,
        permission: ["manage fees", "approve payments", "approve repairs"],
        isCurrent: currentUrl.startsWith("/finance"),
        isExpanded: false,
        children: [
            {
                name: "Unpaid fees",
                icon: HandCoins,
                routeLink: 'finance.unpaid.index',
                isCurrent: currentUrl.startsWith("/finance/unpaid-fees"),
                permission: "manage fees",
            },
            {
                name: "Approvals",
                icon: ClipboardCheck,
                routeLink: 'finance.approvals.index',
                isCurrent: currentUrl.startsWith("/finance/approvals"),
                permission: "approve payments",
            },
        ],
    },
    {
        name: "Settings",
        icon: SlidersHorizontal,
        permission: "update settings",
        isCurrent: currentUrl.startsWith("/settings"),
        isExpanded: false,
        children: [
            {
                name: "Overview",
                icon: SlidersHorizontal,
                routeLink: 'admin.settings.index',
                isCurrent: currentUrl === "/settings",
                permission: "update settings",
            },
            {
                name: "Academic Calendar",
                icon: CalendarDays,
                routeLink: 'admin.settings.years.index',
                isCurrent: currentUrl.startsWith("/settings/academic-years"),
                permission: "update settings",
            },
            {
                name: "Institutions",
                icon: School,
                routeLink: 'admin.settings.institutions.index',
                isCurrent: currentUrl.startsWith("/settings/institutions"),
                permission: "update settings",
            },
            {
                name: "Item Categories",
                icon: Package,
                routeLink: 'admin.settings.item-categories.index',
                isCurrent: currentUrl.startsWith("/settings/item-categories"),
                permission: "update settings",
            },
            {
                name: "Exit Reasons",
                icon: DoorOpen,
                routeLink: 'admin.settings.exit-reasons.index',
                isCurrent: currentUrl.startsWith("/settings/exit-reasons"),
                permission: "update settings",
            },
            {
                name: "Approvals",
                icon: ShieldCheck,
                routeLink: 'admin.settings.approvals.edit',
                isCurrent: currentUrl.startsWith("/settings/approvals"),
                permission: "update settings",
            },
        ],
    },
    {
        name: "System",
        icon: Settings,
        permission: "list users",
        isCurrent:
            currentUrl.startsWith("/users") ||
            currentUrl.startsWith("/roles") ||
            currentUrl.startsWith("/activity-logs") ||
            currentUrl.startsWith("/backups") ||
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
            {
                name: "Backups",
                icon: DatabaseBackup,
                routeLink: 'admin.backups.index',
                isCurrent: currentUrl.startsWith("/backups"),
                permission: "list backups",
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
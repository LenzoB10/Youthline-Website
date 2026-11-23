<template>
    <div>
        <!-- Mobile overlay -->
        <div v-if="sidebar.isMobile && sidebar.isMobileSidebarOpen" class="fixed inset-0 z-20 bg-gray-900/40 md:hidden"
            @click="sidebar.closeMobile"></div>

        <aside
            class="fixed left-0 top-0 z-30 flex h-full w-64 flex-col border-r border-[#e6e8ec] bg-white transition-all duration-300 dark:border-white/10 dark:bg-[#16191d]"
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
            <!-- Brand -->
            <a href="/" class="mx-3 mt-3 flex flex-shrink-0 items-center gap-3 rounded-[10px] px-3 py-3 transition hover:bg-gray-50 dark:hover:bg-white/5">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-gray-200 dark:bg-white/10 dark:ring-white/10">
                    <img src="/imgs/youthline-logo-sm.png" alt="" width="240" height="160" class="h-7 w-auto object-contain" />
                </span>
                <span class="min-w-0 leading-tight">
                    <span class="block text-[17px] font-bold tracking-tight text-mustBlue dark:text-text-inverted">Youthline</span>
                    <span class="block text-[11px] font-medium text-text-muted dark:text-text-dark-muted">Management system</span>
                </span>
            </a>

            <div class="mx-6 my-2 h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent dark:via-white/10"></div>

            <nav class="no-scrollbar flex-grow overflow-y-auto px-3 pb-3" aria-label="Main">
                <template v-for="(item, index) in filteredMenuItems" :key="item.name">
                    <p v-if="index === 0 || filteredMenuItems[index - 1].section !== item.section"
                        class="mb-1.5 px-3 text-[10.5px] font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500"
                        :class="index === 0 ? 'mt-1' : 'mt-5'">
                        {{ item.section }}
                    </p>

                    <div v-if="item.children && item.children.length" class="mb-0.5">
                        <button type="button" @click="toggleSubMenu(item)" :aria-expanded="item.isExpanded"
                            class="group flex w-full items-center justify-between rounded-[10px] px-3 py-2.5 text-[14px] font-medium transition"
                            :class="item.isCurrent
                                ? 'text-mustGreen dark:text-accent-light'
                                : 'text-gray-600 hover:bg-gray-100 hover:text-mustBlue dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white'">
                            <span class="flex items-center">
                                <component :is="item.icon" class="mr-3 h-[18px] w-[18px]"
                                    :class="item.isCurrent ? 'text-mustGreen dark:text-accent-light' : 'text-gray-400 group-hover:text-mustGreen'" />
                                {{ item.name }}
                            </span>
                            <ChevronDown class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': item.isExpanded }" />
                        </button>
                        <transition enter-active-class="transition-all duration-200 ease-out"
                            enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-[40rem]"
                            leave-active-class="transition-all duration-150 ease-in"
                            leave-from-class="opacity-100 max-h-[40rem]" leave-to-class="opacity-0 max-h-0">
                            <ul v-if="item.isExpanded" class="my-1 ml-[21px] space-y-0.5 overflow-hidden border-l border-gray-300/70 pl-3 dark:border-white/10">
                                <li v-for="child in item.children" :key="child.name">
                                    <Link :href="child.routeLink ? route(child.routeLink) : '#'"
                                        class="relative flex items-center rounded-md px-3 py-2 text-[13px] transition"
                                        :class="child.isCurrent
                                            ? 'bg-mustGreen/10 font-semibold text-mustGreen dark:bg-white/10 dark:text-accent-light'
                                            : 'text-gray-500 hover:bg-gray-100 hover:text-mustBlue dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white'">
                                        <span v-if="child.isCurrent" class="absolute -left-[13px] top-1/2 h-5 w-[2px] -translate-y-1/2 rounded-full bg-mustGreen"></span>
                                        {{ child.name }}
                                    </Link>
                                </li>
                            </ul>
                        </transition>
                    </div>

                    <Link v-else :href="item.routeLink ? route(item.routeLink) : '#'"
                        class="group mb-0.5 flex items-center rounded-[10px] px-3 py-2.5 text-[14px] font-medium transition"
                        :class="item.isCurrent
                            ? 'bg-mustGreen text-white shadow-[0_8px_20px_-8px_rgba(28,125,60,0.65)]'
                            : 'text-gray-600 hover:bg-gray-100 hover:text-mustBlue dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white'">
                        <component :is="item.icon" class="mr-3 h-[18px] w-[18px]"
                            :class="item.isCurrent ? 'text-white' : 'text-gray-400 group-hover:text-mustGreen'" />
                        {{ item.name }}
                    </Link>
                </template>
            </nav>

            <!-- Signed-in user: opens the same menu as the top-bar avatar -->
            <div v-if="user" class="relative m-3 mt-0" v-click-outside="() => (userMenuOpen = false)">
                <Transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="translate-y-1 opacity-0"
                    leave-active-class="transition duration-100 ease-in"
                    leave-to-class="translate-y-1 opacity-0"
                >
                    <div v-if="userMenuOpen" class="absolute inset-x-0 bottom-full z-40 mb-2">
                        <ProfileMenu />
                    </div>
                </Transition>
                <button type="button" :aria-expanded="userMenuOpen" aria-haspopup="menu"
                    class="flex w-full items-center gap-3 rounded-[10px] bg-gray-50 p-2.5 text-left ring-1 ring-gray-200 transition hover:bg-gray-100 dark:bg-white/5 dark:ring-white/10 dark:hover:bg-white/10"
                    :class="{ '!ring-mustGreen': userMenuOpen }"
                    @click="userMenuOpen = !userMenuOpen">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-mustGreen to-yl-green-deep text-xs font-bold text-white">
                        {{ initials }}
                    </span>
                    <span class="min-w-0 flex-1 leading-tight">
                        <span class="block truncate text-sm font-semibold text-mustBlue dark:text-text-inverted">{{ user.name }}</span>
                        <span class="block truncate text-[11px] text-text-muted dark:text-text-dark-muted">{{ user.email }}</span>
                    </span>
                    <ChevronsUpDown class="h-4 w-4 shrink-0 text-gray-400" />
                </button>
            </div>
        </aside>
    </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, computed } from "vue";
import {
    Home,
    Settings,
    ChevronDown,
    ChevronsUpDown,
    User,
    Shield,
    Activity,
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
    Wallet,
    HandCoins,
    ClipboardCheck,
    LifeBuoy,
    MessagesSquare,
    HandHeart,
    Store,
    HeartHandshake,
    FileBarChart,
    Quote,
    Building2,
} from "lucide-vue-next";

import { useSidebarStore } from "@/Stores/sidebar.js";
import { Link } from "@inertiajs/vue3";
import ProfileMenu from "@/Components/Layouts/ProfileMenu.vue";
import { usePage } from "@inertiajs/vue3";

const { url: currentUrl } = usePage();

const user = computed(() => usePage().props.auth?.user ?? null);
const userMenuOpen = ref(false);
const initials = computed(() => (user.value?.name ?? '?').split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase());

// Menu sections, in the order they appear.
const SECTION_ORDER = ["Overview", "Programs", "Finance & reports", "Administration"];

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
        section: "Overview",
        icon: Home,
        isCurrent: currentUrl === "/" || currentUrl.startsWith("/dashboard"),
        routeLink: 'dashboard',
        isExpanded: false,
        permission: null,
    },
    {
        name: "Website",
        section: "Administration",
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
                name: "Testimonials",
                icon: Quote,
                routeLink: 'admin.website.testimonials.index',
                isCurrent: currentUrl.startsWith("/website/testimonials"),
                permission: "manage website content",
            },
            {
                name: "Partners",
                icon: Building2,
                routeLink: 'admin.website.partners.index',
                isCurrent: currentUrl.startsWith("/website/partners"),
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
        section: "Programs",
        icon: GraduationCap,
        routeLink: 'students.index',
        // Highlighted on every student page: the list, profiles, add/edit and promotion.
        isCurrent: currentUrl.startsWith("/students"),
        permission: "list students",
    },
    {
        name: "Support",
        section: "Programs",
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
                name: "Families",
                icon: HandHeart,
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
        section: "Programs",
        icon: HeartHandshake,
        routeLink: 'sponsors.index',
        isCurrent: currentUrl.startsWith("/sponsors"),
        permission: "manage sponsors",
    },
    {
        name: "Reports",
        section: "Finance & reports",
        icon: FileBarChart,
        routeLink: 'reports.index',
        isCurrent: currentUrl.startsWith("/reports"),
        permission: "export reports",
    },
    {
        name: "Finance",
        section: "Finance & reports",
        icon: Wallet,
        permission: ["manage fees", "approve payments"],
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
        section: "Administration",
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
        section: "Administration",
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
    return [...menuItems.value]
        .sort((a, b) => SECTION_ORDER.indexOf(a.section) - SECTION_ORDER.indexOf(b.section))
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
                expandedStates.value[item.name] = item.isExpanded || Boolean(item.children && item.isCurrent);
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
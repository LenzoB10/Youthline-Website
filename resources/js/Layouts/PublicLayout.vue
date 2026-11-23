<script setup>
import XLogo from '@/Components/Public/XLogo.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import {
    Facebook,
    Instagram,
    Mail,
    MapPin,
    Menu,
    Phone,
    X,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { toast } from 'vue3-toastify';

const props = defineProps({
    // Pages with a full-bleed photo hero (Home) start with a see-through
    // navbar over the photo, which turns solid white once the visitor scrolls.
    transparentHeader: { type: Boolean, default: false },
});

const page = usePage();
const site = computed(() => page.props.site ?? {});
const social = computed(() => site.value.social_links ?? {});
const contact = computed(() => site.value.contact ?? {});
const year = new Date().getFullYear();

// Flash messages from public form submissions
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
    },
    { deep: true, immediate: true },
);

// Newsletter signup (footer)
const newsletter = useForm({ email: '', company: '' });
const subscribe = () => {
    newsletter.post(route('newsletter.store'), {
        preserveScroll: true,
        onSuccess: () => newsletter.reset(),
    });
};

const navLinks = [
    { label: 'Home', route: 'home' },
    { label: 'About', route: 'about' },
    { label: 'Our Programs', route: 'programs' },
    { label: 'Our Impact', route: 'impact' },
    { label: 'News', route: 'news' },
    { label: 'Contact', route: 'contact' },
];

const mobileOpen = ref(false);

const scrolled = ref(false);
const onScroll = () => {
    scrolled.value = window.scrollY > 24;
};
onMounted(() => {
    if (props.transparentHeader) {
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }
});
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));

const solidHeader = computed(() => !props.transparentHeader || scrolled.value || mobileOpen.value);

const isCurrent = (name) => {
    try {
        return route().current(name);
    } catch (e) {
        return false;
    }
};
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white text-yl-ink">
        <!-- Keyboard / screen-reader users can jump straight past the navigation -->
        <a
            href="#main-content"
            class="sr-only z-50 bg-yl-gold px-5 py-2 text-sm font-semibold text-yl-forest focus:not-sr-only focus:fixed focus:left-4 focus:top-4"
        >
            Skip to content
        </a>

        <!-- Header -->
        <header
            class="top-0 z-40 border-b transition-colors duration-300"
            :class="[
                transparentHeader ? 'fixed inset-x-0' : 'sticky',
                solidHeader ? 'border-gray-100 bg-white/95 shadow-sm backdrop-blur' : 'border-transparent bg-transparent',
            ]"
        >
            <div class="yl-container flex h-16 items-center justify-between gap-4">
                <!-- Brand -->
                <Link :href="route('home')" class="flex items-center gap-2.5" aria-label="Youthline home">
                    <img
                        src="/imgs/youthline-logo-sm.png"
                        alt=""
                        width="240"
                        height="160"
                        class="h-11 w-auto object-contain"
                    />
                </Link>

                <!-- Desktop nav -->
                <nav class="hidden items-center gap-1 lg:flex" aria-label="Main">
                    <Link
                        v-for="link in navLinks"
                        :key="link.route"
                        :href="route(link.route)"
                        :class="
                            solidHeader
                                ? ['yl-nav-link', { 'yl-nav-link-active': isCurrent(link.route) }]
                                : ['yl-nav-link-light', { 'yl-nav-link-light-active': isCurrent(link.route) }]
                        "
                        :aria-current="isCurrent(link.route) ? 'page' : undefined"
                    >
                        {{ link.label }}
                    </Link>
                </nav>

                <div class="hidden items-center gap-3 lg:flex">
                    <Link :href="route('get-involved')" class="yl-btn-accent !px-5 !py-2">
                        Donate
                    </Link>
                </div>

                <!-- Mobile toggle -->
                <button
                    type="button"
                    class="inline-flex items-center justify-center p-2 transition-colors lg:hidden"
                    :class="solidHeader ? 'text-yl-green-deep' : 'text-white'"
                    :aria-label="mobileOpen ? 'Close menu' : 'Open menu'"
                    :aria-expanded="mobileOpen ? 'true' : 'false'"
                    aria-controls="mobile-menu"
                    @click="mobileOpen = !mobileOpen"
                >
                    <Menu v-if="!mobileOpen" class="h-6 w-6" />
                    <X v-else class="h-6 w-6" />
                </button>
            </div>

            <!-- Mobile nav -->
            <div v-if="mobileOpen" id="mobile-menu" class="border-t border-gray-100 bg-white lg:hidden">
                <nav class="yl-container flex flex-col gap-1 py-3" aria-label="Mobile">
                    <Link
                        v-for="link in navLinks"
                        :key="link.route"
                        :href="route(link.route)"
                        class="yl-nav-link"
                        :class="{ 'yl-nav-link-active': isCurrent(link.route) }"
                        :aria-current="isCurrent(link.route) ? 'page' : undefined"
                        @click="mobileOpen = false"
                    >
                        {{ link.label }}
                    </Link>
                    <Link
                        :href="route('get-involved')"
                        class="yl-btn-accent mt-2 w-full"
                        @click="mobileOpen = false"
                    >
                        Donate
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Page content -->
        <main id="main-content" tabindex="-1" class="yl-page-fade flex-1 focus:outline-none">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-yl-green-deep text-white">
            <div class="yl-container grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-4">
                <div class="sm:col-span-2 lg:col-span-1">
                    <div class="flex items-center gap-3">
                        <!-- White circle so the green/gold logo stands out on the dark footer -->
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-white p-2 shadow-sm">
                            <img
                                src="/imgs/youthline-logo-sm.png"
                                alt=""
                                width="240"
                                height="160"
                                loading="lazy"
                                class="h-auto w-full object-contain"
                            />
                        </span>
                        <span class="text-lg font-bold">{{ site.org_name || 'Youthline' }}</span>
                    </div>
                    <p class="mt-4 text-sm text-white/70">
                        {{ site.tagline }}
                    </p>
                    <div class="mt-5 flex gap-3">
                        <a
                            v-if="social.facebook"
                            :href="social.facebook"
                            target="_blank"
                            rel="noopener"
                            class="yl-social yl-social-facebook bg-white/10 p-2"
                            aria-label="Facebook"
                        >
                            <Facebook class="h-4 w-4" />
                        </a>
                        <a
                            v-if="social.instagram"
                            :href="social.instagram"
                            target="_blank"
                            rel="noopener"
                            class="yl-social yl-social-instagram bg-white/10 p-2"
                            aria-label="Instagram"
                        >
                            <Instagram class="h-4 w-4" />
                        </a>
                        <a
                            v-if="social.x"
                            :href="social.x"
                            target="_blank"
                            rel="noopener"
                            class="yl-social yl-social-x bg-white/10 p-2"
                            aria-label="X"
                        >
                            <XLogo class="h-4 w-4" />
                        </a>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-white/60">
                        Explore
                    </h3>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li v-for="link in navLinks" :key="link.route">
                            <Link :href="route(link.route)" class="text-white/80 hover:text-white">
                                {{ link.label }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-white/60">
                        Newsletter
                    </h3>
                    <p class="mt-4 text-sm text-white/70">
                        Get occasional updates on our work and the young people we serve.
                    </p>
                    <form class="mt-4 flex flex-col gap-2" @submit.prevent="subscribe">
                        <input
                            v-model="newsletter.company"
                            type="text"
                            tabindex="-1"
                            autocomplete="off"
                            class="hidden"
                            aria-hidden="true"
                        />
                        <label for="newsletter-email" class="sr-only">Email address</label>
                        <input
                            id="newsletter-email"
                            v-model="newsletter.email"
                            type="email"
                            autocomplete="email"
                            required
                            placeholder="you@example.com"
                            class="rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm text-white placeholder-white/50 focus:border-yl-gold focus:outline-none"
                        />
                        <button type="submit" class="yl-btn-accent !py-2" :disabled="newsletter.processing">
                            Subscribe
                        </button>
                        <p v-if="newsletter.errors.email" class="text-xs text-yl-gold-soft">
                            {{ newsletter.errors.email }}
                        </p>
                    </form>
                </div>

                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-white/60">
                        Contact
                    </h3>
                    <ul class="mt-4 space-y-3 text-sm text-white/80">
                        <li v-if="contact.phone" class="flex items-center gap-2">
                            <Phone class="h-4 w-4 shrink-0" /> {{ contact.phone }}
                        </li>
                        <li v-if="contact.email" class="flex items-center gap-2">
                            <Mail class="h-4 w-4 shrink-0" /> {{ contact.email }}
                        </li>
                        <li v-if="contact.address" class="flex items-start gap-2">
                            <MapPin class="h-4 w-4 shrink-0" /> {{ contact.address }}
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-white/10">
                <div class="yl-container py-5 text-center text-xs text-white/50">
                    {{ site.footer_text || `© ${year} ${site.org_name || 'Youthline'}. All rights reserved.` }}
                </div>
            </div>
        </footer>
    </div>
</template>

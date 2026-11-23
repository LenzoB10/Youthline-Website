<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Building2, Contact, FileText, Handshake, Heart, LayoutGrid, Mail, MessageSquare, Newspaper, Quote, Settings, Users } from 'lucide-vue-next';

defineProps({
    stats: { type: Object, default: () => ({}) },
});

const cards = [
    { name: 'Site Settings', description: 'Name, tagline, logo, footer and social links.', icon: Settings, route: 'admin.website.settings.edit' },
    { name: 'Pages', description: 'Edit the text shown on each public page.', icon: FileText, route: 'admin.website.pages.index' },
    { name: 'Programs', description: 'The six programs shown on the site.', icon: LayoutGrid, route: 'admin.website.programs.index' },
    { name: 'Core Values', description: 'The values on the About page.', icon: Heart, route: 'admin.website.values.index' },
    { name: 'Team', description: 'Leadership team members and photos.', icon: Users, route: 'admin.website.team.index' },
    { name: 'Testimonials', description: 'Quotes from students, families and sponsors on the home page.', icon: Quote, route: 'admin.website.testimonials.index' },
    { name: 'Partners', description: 'Logos of organisations that support Youthline.', icon: Building2, route: 'admin.website.partners.index' },
    { name: 'Contact Details', description: 'Phone, email, address and office hours.', icon: Contact, route: 'admin.website.contact.edit' },
    { name: 'News & Stories', description: 'Write, publish and manage posts.', icon: Newspaper, route: 'admin.website.news.index' },
    { name: 'Contact Messages', description: 'Messages from the Contact Us form.', icon: MessageSquare, route: 'admin.website.messages.index' },
    { name: 'Get Involved Requests', description: 'Volunteers, mentors, partners and sponsors.', icon: Handshake, route: 'admin.website.involvement.index' },
    { name: 'Newsletter Subscribers', description: 'View, remove and export subscribers.', icon: Mail, route: 'admin.website.subscribers.index' },
];
</script>

<template>
    <Head title="Website" />

    <AuthenticatedLayout>
        <h1 class="page-heading">Website Content</h1>
        <p class="mb-6 text-sm text-text-muted dark:text-text-dark-muted">
            Manage what visitors see on the public Youthline website.
        </p>

        <div class="mb-8 grid gap-4 sm:grid-cols-3">
            <div class="card-container">
                <p class="text-3xl font-bold text-mustBlue dark:text-text-inverted">{{ stats.pages ?? 0 }}</p>
                <p class="text-sm text-text-muted">Editable pages</p>
            </div>
            <div class="card-container">
                <p class="text-3xl font-bold text-mustGreen">{{ stats.news_published ?? 0 }}</p>
                <p class="text-sm text-text-muted">Published posts</p>
            </div>
            <div class="card-container">
                <p class="text-3xl font-bold text-text-muted">{{ stats.news_drafts ?? 0 }}</p>
                <p class="text-sm text-text-muted">Draft posts</p>
            </div>
            <Link :href="route('admin.website.messages.index')" class="card-container transition hover:shadow-lg">
                <p class="text-3xl font-bold text-mustBlue dark:text-text-inverted">{{ stats.new_messages ?? 0 }}</p>
                <p class="text-sm text-text-muted">New contact messages</p>
            </Link>
            <Link :href="route('admin.website.involvement.index')" class="card-container transition hover:shadow-lg">
                <p class="text-3xl font-bold text-mustGreen">{{ stats.new_requests ?? 0 }}</p>
                <p class="text-sm text-text-muted">New get-involved requests</p>
            </Link>
            <Link :href="route('admin.website.subscribers.index')" class="card-container transition hover:shadow-lg">
                <p class="text-3xl font-bold text-text-muted">{{ stats.subscribers ?? 0 }}</p>
                <p class="text-sm text-text-muted">Newsletter subscribers</p>
            </Link>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <Link
                v-for="card in cards"
                :key="card.route"
                :href="route(card.route)"
                class="card-container flex items-start gap-4 transition hover:shadow-lg"
            >
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-mustGreen/10 text-mustGreen">
                    <component :is="card.icon" class="h-5 w-5" />
                </span>
                <div>
                    <h2 class="font-semibold text-mustBlue dark:text-text-inverted">{{ card.name }}</h2>
                    <p class="text-sm text-text-muted">{{ card.description }}</p>
                </div>
            </Link>
        </div>
    </AuthenticatedLayout>
</template>

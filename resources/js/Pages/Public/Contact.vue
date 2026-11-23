<script setup>
import InputError from '@/Components/FormElements/InputError.vue';
import PageHeader from '@/Components/Public/PageHeader.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import XLogo from '@/Components/Public/XLogo.vue';
import { Facebook, Instagram, Mail, MapPin, Phone } from 'lucide-vue-next';
import { computed } from 'vue';

defineProps({
    page: { type: Object, default: null },
});

const pageData = usePage();
const contact = computed(() => pageData.props.site?.contact ?? {});
const social = computed(() => pageData.props.site?.social_links ?? {});

const details = computed(() =>
    [
        {
            icon: Phone,
            label: 'Phone',
            value: contact.value.phone,
            href: contact.value.phone ? `tel:${contact.value.phone.replace(/[^\d+]/g, '')}` : null,
        },
        {
            icon: Mail,
            label: 'Email',
            value: contact.value.email,
            href: contact.value.email ? `mailto:${contact.value.email}` : null,
        },
        { icon: MapPin, label: 'Address', value: contact.value.address, href: null },
    ].filter((detail) => detail.value),
);

const socialLinks = computed(() =>
    [
        { icon: Facebook, label: 'Facebook', href: social.value.facebook, network: 'facebook' },
        { icon: Instagram, label: 'Instagram', href: social.value.instagram, network: 'instagram' },
        { icon: XLogo, label: 'X (Twitter)', href: social.value.x, network: 'x' },
    ].filter((link) => link.href),
);

const form = useForm({ name: '', email: '', phone: '', message: '', company: '' });

const submit = () => {
    form.post(route('contact.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Contact Us" />

    <PublicLayout>
        <PageHeader title="Contact Us" image="/imgs/web/lg/IMG20261028134004.jpg" />

        <section class="py-16 sm:py-24">
            <div class="yl-container grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                <!-- Details -->
                <div v-reveal:left>
                    <h2 class="text-4xl font-bold text-yl-green-deep sm:text-5xl">Get In Touch</h2>
                    <p class="mt-4 max-w-lg text-lg leading-relaxed text-yl-ink/70">
                        Questions, partnerships, or want to help? Send us a message and we will
                        respond as soon as we can.
                    </p>

                    <div v-reveal.stagger class="mt-10 grid gap-8 sm:grid-cols-2">
                        <div v-for="detail in details" :key="detail.label" class="flex items-start gap-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-yl-green-deep text-yl-gold">
                                <component :is="detail.icon" class="h-5 w-5" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-lg font-bold text-yl-green-deep">{{ detail.label }}</p>
                                <a
                                    v-if="detail.href"
                                    :href="detail.href"
                                    class="break-words text-yl-ink/70 transition hover:text-yl-green"
                                >
                                    {{ detail.value }}
                                </a>
                                <p v-else class="text-yl-ink/70">{{ detail.value }}</p>
                            </div>
                        </div>
                    </div>

                    <template v-if="socialLinks.length">
                        <hr class="mt-10 max-w-sm border-gray-200" />
                        <div class="mt-8 flex items-center gap-6">
                            <p class="font-bold text-yl-green-deep">Social Media</p>
                            <div class="flex items-center gap-3">
                                <a
                                    v-for="link in socialLinks"
                                    :key="link.label"
                                    :href="link.href"
                                    target="_blank"
                                    rel="noopener"
                                    :aria-label="link.label"
                                    class="yl-social bg-yl-green/10 p-2.5 text-yl-green-deep"
                                    :class="`yl-social-${link.network}`"
                                >
                                    <component :is="link.icon" class="h-5 w-5" />
                                </a>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Form -->
                <form v-reveal:right class="yl-form-card" @submit.prevent="submit">
                    <!-- Spam trap: hidden from people, filled in by bots -->
                    <input v-model="form.company" type="text" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

                    <div class="grid gap-6">
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="name" class="yl-label">Name</label>
                                <input id="name" v-model="form.name" type="text" class="yl-field" placeholder="Your name" autocomplete="name" />
                                <InputError :message="form.errors.name" class="mt-1" />
                            </div>
                            <div>
                                <label for="email" class="yl-label">Email</label>
                                <input id="email" v-model="form.email" type="email" class="yl-field" placeholder="you@example.com" autocomplete="email" />
                                <InputError :message="form.errors.email" class="mt-1" />
                            </div>
                        </div>
                        <div>
                            <label for="phone" class="yl-label">Phone <span class="font-normal text-yl-ink/50">(optional)</span></label>
                            <input id="phone" v-model="form.phone" type="tel" class="yl-field" placeholder="+265 ..." autocomplete="tel" />
                            <InputError :message="form.errors.phone" class="mt-1" />
                        </div>
                        <div>
                            <label for="message" class="yl-label">Message</label>
                            <textarea id="message" v-model="form.message" rows="6" class="yl-field" placeholder="How can we help?"></textarea>
                            <InputError :message="form.errors.message" class="mt-1" />
                        </div>
                        <div>
                            <button
                                type="submit"
                                class="yl-btn-submit"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Sending…' : 'Send message' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </PublicLayout>
</template>

<script setup>
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import PageHeader from '@/Components/Public/PageHeader.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Clock, Mail, MapPin, Phone } from 'lucide-vue-next';
import { computed } from 'vue';

defineProps({
    page: { type: Object, default: null },
});

const pageData = usePage();
const contact = computed(() => pageData.props.site?.contact ?? {});

const details = computed(() => [
    { icon: Phone, label: 'Phone', value: contact.value.phone },
    { icon: Mail, label: 'Email', value: contact.value.email },
    { icon: MapPin, label: 'Address', value: contact.value.address },
    { icon: Clock, label: 'Office hours', value: contact.value.office_hours },
]);

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
        <PageHeader
            eyebrow="Reach out"
            :title="page?.title || 'Contact Us'"
            :subtitle="page?.summary || ''"
        />

        <section class="py-16">
            <div class="yl-container grid gap-12 lg:grid-cols-2">
                <!-- Details -->
                <div>
                    <h2 class="text-2xl font-bold text-yl-green-deep">Get in touch</h2>
                    <p class="mt-2 text-yl-ink/70">
                        Questions, partnerships, or want to help? Send us a message and we will
                        respond as soon as we can.
                    </p>
                    <div class="mt-8 grid gap-6 sm:grid-cols-2">
                        <div
                            v-for="detail in details.filter((d) => d.value)"
                            :key="detail.label"
                            class="flex items-start gap-3"
                        >
                            <component :is="detail.icon" class="mt-1 h-5 w-5 text-yl-green" />
                            <div>
                                <p class="font-semibold text-yl-green-deep">{{ detail.label }}</p>
                                <p class="text-yl-ink/70">{{ detail.value }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <form class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8" @submit.prevent="submit">
                    <input v-model="form.company" type="text" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

                    <div class="grid gap-5">
                        <div>
                            <InputLabel for="name" value="Your name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1" autocomplete="name" />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <InputLabel for="email" value="Email" />
                                <TextInput id="email" v-model="form.email" type="email" class="mt-1" autocomplete="email" />
                                <InputError :message="form.errors.email" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="phone" value="Phone (optional)" />
                                <TextInput id="phone" v-model="form.phone" type="text" class="mt-1" autocomplete="tel" />
                                <InputError :message="form.errors.phone" class="mt-1" />
                            </div>
                        </div>
                        <div>
                            <InputLabel for="message" value="Message" />
                            <TextArea id="message" v-model="form.message" rows="5" class="mt-1" />
                            <InputError :message="form.errors.message" class="mt-1" />
                        </div>
                        <button type="submit" class="yl-btn-primary w-full" :disabled="form.processing">
                            Send message
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </PublicLayout>
</template>

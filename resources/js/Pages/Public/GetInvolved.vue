<script setup>
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import PageHeader from '@/Components/Public/PageHeader.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Heart, Users } from 'lucide-vue-next';

const typeOptions = [
    { value: 'volunteer', label: 'Volunteer' },
    { value: 'mentor', label: 'Mentor' },
    { value: 'partner', label: 'Partner organisation' },
    { value: 'sponsor', label: 'Sponsor a student' },
    { value: 'donor', label: 'Donor' },
];

const form = useForm({ name: '', email: '', phone: '', type: '', message: '', company: '' });

const submit = () => {
    form.post(route('involvement.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Get Involved" />

    <PublicLayout>
        <PageHeader
            eyebrow="Join us"
            title="Get Involved"
            subtitle="Volunteer, mentor, partner, sponsor a student, or donate — there are many ways to help a young person thrive."
        />

        <section class="py-16">
            <div class="yl-container grid gap-12 lg:grid-cols-2">
                <!-- Ways to help -->
                <div>
                    <div class="flex items-start gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-yl-green/10 text-yl-green">
                            <Users class="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-lg font-bold text-yl-green-deep">Give your time or skills</h3>
                            <p class="mt-1 text-yl-ink/70">
                                Volunteer, mentor a young person, or partner with us as an organisation.
                                Tell us how you would like to help and we will be in touch.
                            </p>
                        </div>
                    </div>
                    <div class="mt-8 flex items-start gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-yl-gold/15 text-yl-gold">
                            <Heart class="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-lg font-bold text-yl-green-deep">Sponsor or donate</h3>
                            <p class="mt-1 text-yl-ink/70">
                                Sponsor a student’s journey or make a donation to the work you care about.
                                Online giving is coming soon — register your interest below and we will
                                help you get started.
                            </p>
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
                            <InputLabel for="type" value="How would you like to help?" />
                            <SelectInput
                                id="type"
                                v-model="form.type"
                                :options="typeOptions"
                                placeholder="Choose an option"
                                class="mt-1"
                            />
                            <InputError :message="form.errors.type" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="message" value="Message (optional)" />
                            <TextArea id="message" v-model="form.message" rows="4" class="mt-1" />
                            <InputError :message="form.errors.message" class="mt-1" />
                        </div>
                        <button type="submit" class="yl-btn-primary w-full" :disabled="form.processing">
                            Send
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </PublicLayout>
</template>

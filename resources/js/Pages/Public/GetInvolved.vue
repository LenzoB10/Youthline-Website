<script setup>
import InputError from '@/Components/FormElements/InputError.vue';
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
        <PageHeader title="Get Involved" image="/imgs/web/lg/IMG20261028134003.jpg" />

        <section class="py-16 sm:py-24">
            <div class="yl-container grid items-start gap-12 lg:grid-cols-2 lg:gap-16">
                <!-- Ways to help -->
                <div v-reveal:left>
                    <div class="flex items-start gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center bg-yl-green/10 text-yl-green">
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
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center bg-yl-gold/15 text-yl-gold">
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
                            <label for="type" class="yl-label">How would you like to help?</label>
                            <select id="type" v-model="form.type" class="yl-field" :class="{ 'text-yl-ink/35': !form.type }">
                                <option value="" disabled>Choose an option</option>
                                <option v-for="option in typeOptions" :key="option.value" :value="option.value" class="text-yl-ink">
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.type" class="mt-1" />
                        </div>
                        <div>
                            <label for="message" class="yl-label">Message <span class="font-normal text-yl-ink/50">(optional)</span></label>
                            <textarea id="message" v-model="form.message" rows="5" class="yl-field" placeholder="Tell us a little about yourself"></textarea>
                            <InputError :message="form.errors.message" class="mt-1" />
                        </div>
                        <div>
                            <button type="submit" class="yl-btn-submit" :disabled="form.processing">
                                {{ form.processing ? 'Sending…' : 'Send' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </PublicLayout>
</template>

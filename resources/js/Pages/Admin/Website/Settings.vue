<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: { type: Object, required: true },
});

const form = useForm({
    org_name: props.settings.org_name ?? '',
    tagline: props.settings.tagline ?? '',
    logo: props.settings.logo ?? '',
    footer_text: props.settings.footer_text ?? '',
    social_links: {
        facebook: props.settings.social_links?.facebook ?? '',
        instagram: props.settings.social_links?.instagram ?? '',
        x: props.settings.social_links?.x ?? '',
    },
});

const submit = () => form.put(route('admin.website.settings.update'), { preserveScroll: true });
</script>

<template>
    <Head title="Site Settings" />

    <AuthenticatedLayout>
        <BackLink :href="route('admin.website.index')">Website content</BackLink>
        <h1 class="page-heading">Site Settings</h1>

        <form class="card-container max-w-2xl" @submit.prevent="submit">
            <div class="grid gap-5">
                <div>
                    <InputLabel for="org_name" value="Organisation name" />
                    <TextInput id="org_name" v-model="form.org_name" type="text" class="mt-1" />
                    <InputError :message="form.errors.org_name" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="tagline" value="Tagline" />
                    <TextInput id="tagline" v-model="form.tagline" type="text" class="mt-1" />
                    <InputError :message="form.errors.tagline" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="logo" value="Logo URL / path" />
                    <TextInput id="logo" v-model="form.logo" type="text" class="mt-1" placeholder="/imgs/youthline-logo.png" />
                    <InputError :message="form.errors.logo" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="footer_text" value="Footer text" />
                    <TextArea id="footer_text" v-model="form.footer_text" rows="2" class="mt-1" />
                    <InputError :message="form.errors.footer_text" class="mt-1" />
                </div>
                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <InputLabel value="Facebook" />
                        <TextInput v-model="form.social_links.facebook" type="url" class="mt-1" />
                        <InputError :message="form.errors['social_links.facebook']" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Instagram" />
                        <TextInput v-model="form.social_links.instagram" type="url" class="mt-1" />
                        <InputError :message="form.errors['social_links.instagram']" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="X (Twitter)" />
                        <TextInput v-model="form.social_links.x" type="url" class="mt-1" />
                        <InputError :message="form.errors['social_links.x']" class="mt-1" />
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn-primary" :disabled="form.processing">Save settings</button>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

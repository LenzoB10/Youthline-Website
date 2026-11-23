<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    page: { type: Object, required: true },
});

const form = useForm({
    title: props.page.title ?? '',
    summary: props.page.summary ?? '',
    body: props.page.body ?? '',
});

const submit = () => form.put(route('admin.website.pages.update', props.page.id));
</script>

<template>
    <Head :title="`Edit: ${page.title}`" />

    <AuthenticatedLayout>
        <BackLink :href="route('admin.website.pages.index')">All pages</BackLink>
        <h1 class="page-heading">Edit page</h1>
        <p class="mb-6 font-mono text-xs text-mustGreen">{{ page.key }}</p>

        <form class="card-container max-w-3xl" @submit.prevent="submit">
            <div class="grid gap-5">
                <div>
                    <InputLabel for="title" value="Title" />
                    <TextInput id="title" v-model="form.title" type="text" class="mt-1" />
                    <InputError :message="form.errors.title" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="summary" value="Summary (used in previews)" />
                    <TextArea id="summary" v-model="form.summary" rows="2" class="mt-1" />
                    <InputError :message="form.errors.summary" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="body" value="Body" />
                    <TextArea id="body" v-model="form.body" rows="10" class="mt-1" />
                    <p class="mt-1 text-xs text-text-muted">Separate paragraphs with a blank line.</p>
                    <InputError :message="form.errors.body" class="mt-1" />
                </div>
                <div>
                    <button type="submit" class="btn-primary" :disabled="form.processing">Save page</button>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

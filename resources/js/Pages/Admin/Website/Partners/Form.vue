<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    partner: { type: Object, default: null },
});

const isEdit = computed(() => props.partner !== null);

const form = useForm({
    name: props.partner?.name ?? '',
    website_url: props.partner?.website_url ?? '',
    is_published: props.partner?.is_published ?? true,
    display_order: props.partner?.display_order ?? 0,
    logo: null,
});

const submit = () => {
    form.transform((data) => ({ ...data, is_published: data.is_published ? 1 : 0, ...(isEdit.value ? { _method: 'put' } : {}) }));
    form.post(isEdit.value ? route('admin.website.partners.update', props.partner.id) : route('admin.website.partners.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="isEdit ? 'Edit partner' : 'New partner'" />

    <AuthenticatedLayout>
        <BackLink :href="route('admin.website.partners.index')">All partners</BackLink>
        <h1 class="page-heading">{{ isEdit ? 'Edit partner' : 'New partner' }}</h1>

        <form class="card-container max-w-2xl" @submit.prevent="submit">
            <div class="grid gap-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="name" value="Organisation name" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1" required />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="website_url" value="Website (optional)" />
                        <TextInput id="website_url" v-model="form.website_url" type="url" class="mt-1" placeholder="https://" />
                        <InputError :message="form.errors.website_url" class="mt-1" />
                    </div>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="logo" value="Logo" />
                        <span v-if="partner?.logo" class="mb-2 mt-1 flex h-14 w-28 items-center justify-center rounded-md border border-mustBorder-light bg-white p-1.5">
                            <img :src="partner.logo" alt="" class="max-h-full max-w-full object-contain" />
                        </span>
                        <input id="logo" type="file" accept="image/*" class="mt-1 block w-full text-sm" @input="form.logo = $event.target.files[0]" />
                        <p class="mt-1 text-xs text-text-muted">A PNG with a transparent background looks best. Without a logo, the name is shown.</p>
                        <InputError :message="form.errors.logo" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="display_order" value="Display order" />
                        <TextInput id="display_order" v-model="form.display_order" type="number" min="0" class="mt-1" />
                        <InputError :message="form.errors.display_order" class="mt-1" />
                    </div>
                </div>
                <label class="flex items-start gap-2 text-sm">
                    <Checkbox v-model="form.is_published" class="mt-0.5" />
                    <span>
                        Show on the website
                        <span class="block text-text-muted">Untick to keep it saved but hidden.</span>
                    </span>
                </label>
                <div>
                    <button type="submit" class="btn-primary" :disabled="form.processing">
                        {{ isEdit ? 'Save partner' : 'Add partner' }}
                    </button>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

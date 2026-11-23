<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    contact: { type: Object, required: true },
});

const form = useForm({
    phone: props.contact.phone ?? '',
    email: props.contact.email ?? '',
    address: props.contact.address ?? '',
    office_hours: props.contact.office_hours ?? '',
    map_location: props.contact.map_location ?? '',
});

const submit = () => form.put(route('admin.website.contact.update'), { preserveScroll: true });
</script>

<template>
    <Head title="Contact Details" />

    <AuthenticatedLayout>
        <BackLink :href="route('admin.website.index')">Website content</BackLink>
        <h1 class="page-heading">Contact Details</h1>

        <form class="card-container max-w-2xl" @submit.prevent="submit">
            <div class="grid gap-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="phone" value="Phone" />
                        <TextInput id="phone" v-model="form.phone" type="text" class="mt-1" />
                        <InputError :message="form.errors.phone" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput id="email" v-model="form.email" type="email" class="mt-1" />
                        <InputError :message="form.errors.email" class="mt-1" />
                    </div>
                </div>
                <div>
                    <InputLabel for="address" value="Address" />
                    <TextArea id="address" v-model="form.address" rows="2" class="mt-1" />
                    <InputError :message="form.errors.address" class="mt-1" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="office_hours" value="Office hours" />
                        <TextInput id="office_hours" v-model="form.office_hours" type="text" class="mt-1" />
                        <InputError :message="form.errors.office_hours" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="map_location" value="Map location (lat,lng)" />
                        <TextInput id="map_location" v-model="form.map_location" type="text" class="mt-1" />
                        <InputError :message="form.errors.map_location" class="mt-1" />
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn-primary" :disabled="form.processing">Save contact details</button>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

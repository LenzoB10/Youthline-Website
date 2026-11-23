<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    member: { type: Object, default: null },
});

const isEdit = computed(() => props.member !== null);

const form = useForm({
    name: props.member?.name ?? '',
    position: props.member?.position ?? '',
    bio: props.member?.bio ?? '',
    display_order: props.member?.display_order ?? 0,
    photo: null,
});

const submit = () => {
    if (isEdit.value) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.website.team.update', props.member.id));
    } else {
        form.post(route('admin.website.team.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit member' : 'New member'" />

    <AuthenticatedLayout>
        <BackLink :href="route('admin.website.team.index')">All team members</BackLink>
        <h1 class="page-heading">{{ isEdit ? 'Edit member' : 'New member' }}</h1>

        <form class="card-container max-w-2xl" @submit.prevent="submit">
            <div class="grid gap-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1" />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="position" value="Position" />
                        <TextInput id="position" v-model="form.position" type="text" class="mt-1" />
                        <InputError :message="form.errors.position" class="mt-1" />
                    </div>
                </div>
                <div>
                    <InputLabel for="bio" value="Short bio" />
                    <TextArea id="bio" v-model="form.bio" rows="4" class="mt-1" />
                    <InputError :message="form.errors.bio" class="mt-1" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="display_order" value="Display order" />
                        <TextInput id="display_order" v-model="form.display_order" type="number" min="0" class="mt-1" />
                        <InputError :message="form.errors.display_order" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="photo" value="Photo" />
                        <img v-if="member?.photo" :src="member.photo" alt="" class="mt-1 mb-2 h-16 w-16 rounded-full object-cover" />
                        <input id="photo" type="file" accept="image/*" class="mt-1 block w-full text-sm" @input="form.photo = $event.target.files[0]" />
                        <InputError :message="form.errors.photo" class="mt-1" />
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn-primary" :disabled="form.processing">
                        {{ isEdit ? 'Save member' : 'Add member' }}
                    </button>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

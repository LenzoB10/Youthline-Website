<script setup>
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    program: { type: Object, default: null },
});

const isEdit = computed(() => props.program !== null);

const form = useForm({
    name: props.program?.name ?? '',
    summary: props.program?.summary ?? '',
    description: props.program?.description ?? '',
    display_order: props.program?.display_order ?? 0,
    image: null,
});

const submit = () => {
    if (isEdit.value) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.website.programs.update', props.program.id));
    } else {
        form.post(route('admin.website.programs.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit program' : 'New program'" />

    <AuthenticatedLayout>
        <Link :href="route('admin.website.programs.index')" class="mb-4 inline-flex items-center gap-1 text-sm text-mustBlue dark:text-text-dark-muted">
            <ArrowLeft class="h-4 w-4" /> All programs
        </Link>
        <h1 class="page-heading">{{ isEdit ? 'Edit program' : 'New program' }}</h1>

        <form class="card-container max-w-3xl" @submit.prevent="submit">
            <div class="grid gap-5">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1" />
                    <InputError :message="form.errors.name" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="summary" value="Summary (one line)" />
                    <TextArea id="summary" v-model="form.summary" rows="2" class="mt-1" />
                    <InputError :message="form.errors.summary" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="description" value="Description" />
                    <TextArea id="description" v-model="form.description" rows="6" class="mt-1" />
                    <InputError :message="form.errors.description" class="mt-1" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="display_order" value="Display order" />
                        <TextInput id="display_order" v-model="form.display_order" type="number" min="0" class="mt-1" />
                        <InputError :message="form.errors.display_order" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="image" value="Image" />
                        <img v-if="program?.image" :src="program.image" alt="" class="mt-1 mb-2 h-16 w-16 rounded object-cover" />
                        <input id="image" type="file" accept="image/*" class="mt-1 block w-full text-sm" @input="form.image = $event.target.files[0]" />
                        <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="mt-1 w-full">{{ form.progress.percentage }}%</progress>
                        <InputError :message="form.errors.image" class="mt-1" />
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn-primary" :disabled="form.processing">
                        {{ isEdit ? 'Save program' : 'Create program' }}
                    </button>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

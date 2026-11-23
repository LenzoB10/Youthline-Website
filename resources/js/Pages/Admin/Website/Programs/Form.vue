<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ExternalLink } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';

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

// Show the chosen photo straight away, cropped the way the website card crops it.
const preview = ref(props.program?.image ?? null);
let objectUrl = null;
const pickImage = (event) => {
    const file = event.target.files[0] ?? null;
    form.image = file;
    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
    }
    if (file) {
        objectUrl = URL.createObjectURL(file);
        preview.value = objectUrl;
    } else {
        preview.value = props.program?.image ?? null;
    }
};
onBeforeUnmount(() => objectUrl && URL.revokeObjectURL(objectUrl));

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
        <BackLink :href="route('admin.website.programs.index')">All programs</BackLink>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="page-heading">{{ isEdit ? 'Edit program' : 'New program' }}</h1>
            <a
                v-if="isEdit && program.slug"
                :href="route('programs.show', program.slug)"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-mustBlue hover:underline dark:text-text-dark-muted"
            >
                View on website <ExternalLink class="h-4 w-4" />
            </a>
        </div>

        <form class="card-container max-w-3xl" @submit.prevent="submit">
            <div class="grid gap-5">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1" />
                    <InputError :message="form.errors.name" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="summary" value="Summary (shown on the program card)" />
                    <TextArea id="summary" v-model="form.summary" rows="2" class="mt-1" />
                    <InputError :message="form.errors.summary" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="description" value="Description (shown on the program's own page; leave a blank line between paragraphs)" />
                    <TextArea id="description" v-model="form.description" rows="6" class="mt-1" />
                    <InputError :message="form.errors.description" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="image" value="Photo" />
                    <p class="mt-1 text-xs text-text-muted">
                        Shown at the top of the program card and as the banner on the program's page.
                        Landscape photos work best (JPG, PNG or WebP, up to 4 MB).
                    </p>
                    <div class="mt-3 grid gap-4 sm:grid-cols-[16rem_1fr] sm:items-start">
                        <div class="aspect-[16/10] overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-700">
                            <img v-if="preview" :src="preview" alt="" class="h-full w-full object-cover object-[center_30%]" />
                            <div v-else class="flex h-full items-center justify-center px-4 text-center text-xs text-text-muted">
                                No photo yet: the website shows one of the general site photos instead.
                            </div>
                        </div>
                        <div>
                            <input id="image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm" @input="pickImage" />
                            <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="mt-2 w-full">{{ form.progress.percentage }}%</progress>
                            <InputError :message="form.errors.image" class="mt-1" />
                        </div>
                    </div>
                </div>
                <div class="max-w-xs">
                    <InputLabel for="display_order" value="Display order" />
                    <TextInput id="display_order" v-model="form.display_order" type="number" min="0" class="mt-1" />
                    <InputError :message="form.errors.display_order" class="mt-1" />
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

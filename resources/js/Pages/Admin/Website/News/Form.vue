<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    post: { type: Object, default: null },
    students: { type: Array, default: () => [] },
});

const isEdit = computed(() => props.post !== null);

const categoryOptions = [
    { value: 'news', label: 'News' },
    { value: 'success_story', label: 'Success story' },
    { value: 'activity', label: 'Activity' },
];

const studentOptions = computed(() =>
    props.students.map((student) => ({ value: student.id, label: student.name })),
);

const form = useForm({
    title: props.post?.title ?? '',
    category: props.post?.category ?? 'news',
    body: props.post?.body ?? '',
    image: null,
    student_id: props.post?.student_id ?? '',
    is_published: props.post?.is_published ?? false,
});

const submit = () => {
    if (isEdit.value) {
        form
            .transform((data) => ({ ...data, student_id: data.student_id || null, _method: 'put' }))
            .post(route('admin.website.news.update', props.post.id));
    } else {
        form
            .transform((data) => ({ ...data, student_id: data.student_id || null }))
            .post(route('admin.website.news.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit post' : 'New post'" />

    <AuthenticatedLayout>
        <BackLink :href="route('admin.website.news.index')">All posts</BackLink>
        <h1 class="page-heading">{{ isEdit ? 'Edit post' : 'New post' }}</h1>

        <form class="card-container max-w-3xl" @submit.prevent="submit">
            <div class="grid gap-5">
                <div>
                    <InputLabel for="title" value="Title" />
                    <TextInput id="title" v-model="form.title" type="text" class="mt-1" />
                    <InputError :message="form.errors.title" class="mt-1" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="category" value="Category" />
                        <SelectInput id="category" v-model="form.category" :options="categoryOptions" class="mt-1" />
                        <InputError :message="form.errors.category" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="image" value="Image (optional)" />
                        <img v-if="post?.image" :src="post.image" alt="" class="mt-1 mb-2 h-16 w-24 rounded object-cover" />
                        <input id="image" type="file" accept="image/*" class="mt-1 block w-full text-sm" @input="form.image = $event.target.files[0]" />
                        <InputError :message="form.errors.image" class="mt-1" />
                    </div>
                </div>
                <div>
                    <InputLabel for="body" value="Body" />
                    <TextArea id="body" v-model="form.body" rows="10" class="mt-1" />
                    <p class="mt-1 text-xs text-text-muted">Separate paragraphs with a blank line.</p>
                    <InputError :message="form.errors.body" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="student_id" value="Link to a student (optional)" />
                    <SelectInput
                        id="student_id"
                        v-model="form.student_id"
                        :options="studentOptions"
                        placeholder="No student"
                        class="mt-1"
                    />
                    <p class="mt-1 text-xs text-text-muted">
                        Only students whose guardian has consented to their story appear here.
                    </p>
                    <InputError :message="form.errors.student_id" class="mt-1" />
                </div>
                <label class="flex items-center gap-2">
                    <input v-model="form.is_published" type="checkbox" class="checkbox-input" />
                    <span class="text-sm text-text-base dark:text-text-inverted">Published (visible on the website)</span>
                </label>
                <InputError :message="form.errors.is_published" class="-mt-3" />
                <div>
                    <button type="submit" class="btn-primary" :disabled="form.processing">
                        {{ isEdit ? 'Save post' : 'Create post' }}
                    </button>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

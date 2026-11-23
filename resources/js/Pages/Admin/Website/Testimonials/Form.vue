<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    testimonial: { type: Object, default: null },
});

const isEdit = computed(() => props.testimonial !== null);

const form = useForm({
    name: props.testimonial?.name ?? '',
    role: props.testimonial?.role ?? '',
    quote: props.testimonial?.quote ?? '',
    is_published: props.testimonial?.is_published ?? true,
    display_order: props.testimonial?.display_order ?? 0,
    photo: null,
});

const submit = () => {
    form.transform((data) => ({ ...data, is_published: data.is_published ? 1 : 0, ...(isEdit.value ? { _method: 'put' } : {}) }));
    form.post(isEdit.value ? route('admin.website.testimonials.update', props.testimonial.id) : route('admin.website.testimonials.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="isEdit ? 'Edit testimonial' : 'New testimonial'" />

    <AuthenticatedLayout>
        <BackLink :href="route('admin.website.testimonials.index')">All testimonials</BackLink>
        <h1 class="page-heading">{{ isEdit ? 'Edit testimonial' : 'New testimonial' }}</h1>

        <form class="card-container max-w-2xl" @submit.prevent="submit">
            <div class="grid gap-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1" placeholder="e.g. Grace M." required />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="role" value="Role (optional)" />
                        <TextInput id="role" v-model="form.role" type="text" class="mt-1" placeholder="e.g. Form 4 student, Parent, Sponsor" />
                        <InputError :message="form.errors.role" class="mt-1" />
                    </div>
                </div>
                <div>
                    <InputLabel for="quote" value="Quote" />
                    <TextArea id="quote" v-model="form.quote" rows="5" maxlength="600" class="mt-1" required />
                    <p class="mt-1 text-xs text-text-muted">{{ form.quote.length }} / 600 characters. Short quotes read best on the moving cards.</p>
                    <InputError :message="form.errors.quote" class="mt-1" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="photo" value="Photo (optional)" />
                        <img v-if="testimonial?.photo" :src="testimonial.photo" alt="" class="mb-2 mt-1 h-16 w-16 rounded-full object-cover" />
                        <input id="photo" type="file" accept="image/*" class="mt-1 block w-full text-sm" @input="form.photo = $event.target.files[0]" />
                        <p class="mt-1 text-xs text-text-muted">Only use a photo the person has agreed to share.</p>
                        <InputError :message="form.errors.photo" class="mt-1" />
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
                        {{ isEdit ? 'Save testimonial' : 'Add testimonial' }}
                    </button>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

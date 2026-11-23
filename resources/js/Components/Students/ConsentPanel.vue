<script setup>
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { can } from '@/Helpers/can';
import { useForm } from '@inertiajs/vue3';
import { ShieldCheck, ShieldOff } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    student: { type: Object, required: true },
    consent: { type: Object, default: null },
    documents: { type: Array, default: () => [] },
});

const canEdit = can('manage student documents');
const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    photo_allowed: props.consent?.photo_allowed ?? false,
    story_allowed: props.consent?.story_allowed ?? false,
    signed_on: props.consent?.signed_on ?? '',
    document_id: props.consent?.document_id ?? null,
});

const consentForms = computed(() =>
    props.documents
        .filter((doc) => doc.type === 'consent')
        .map((doc) => ({ value: doc.id, label: `${doc.name} (${new Date(doc.uploaded_at).toLocaleDateString()})` })),
);
const grantsSomething = computed(() => form.photo_allowed || form.story_allowed);

const submit = () =>
    form.put(route('students.consent.update', props.student.id), {
        preserveScroll: true,
        onSuccess: () => form.defaults(), // saved values become the new baseline
    });
</script>

<template>
    <section class="card-container">
        <div class="mb-1 flex items-center gap-2">
            <component :is="consent?.photo_allowed || consent?.story_allowed ? ShieldCheck : ShieldOff" class="h-5 w-5 text-mustGreen" />
            <h2 class="page-heading-2 !mb-0">Guardian consent</h2>
        </div>
        <p class="mb-4 text-sm text-text-muted">
            Nothing about this student — photo or story — can appear on the public website unless the
            guardian has agreed in a signed form. Withdrawing consent hides any published story at once.
        </p>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-2">
                <label class="flex items-center gap-2 text-sm">
                    <Checkbox v-model="form.photo_allowed" :disabled="!canEdit" />
                    May use the student's <strong>photo</strong> on the website
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <Checkbox v-model="form.story_allowed" :disabled="!canEdit" />
                    May publish the student's <strong>story</strong> on the website
                </label>
            </div>

            <div v-if="grantsSomething || consent?.document_id" class="grid gap-4 sm:grid-cols-[1fr_11rem]">
                <div>
                    <InputLabel for="consent_doc" value="Signed consent form" />
                    <SelectInput
                        v-if="consentForms.length"
                        id="consent_doc"
                        v-model="form.document_id"
                        :options="consentForms"
                        placeholder="Choose the signed form"
                        class="mt-1 block w-full"
                        :disabled="!canEdit"
                    />
                    <p v-else class="mt-1 rounded-lg bg-yl-gold/10 p-2 text-xs text-amber-800 dark:bg-yl-gold/10 dark:text-yl-gold">
                        Upload the signed form under Documents with the type <strong>Consent form</strong> first.
                    </p>
                    <InputError class="mt-2" :message="form.errors.document_id" />
                </div>
                <div>
                    <InputLabel for="signed_on" value="Signed on" />
                    <TextInput id="signed_on" v-model="form.signed_on" type="date" :max="today" class="mt-1 block w-full" :disabled="!canEdit" />
                    <InputError class="mt-2" :message="form.errors.signed_on" />
                </div>
            </div>

            <div v-if="canEdit" class="flex justify-end">
                <button type="submit" class="btn-primary-sm" :disabled="form.processing || !form.isDirty">Save consent</button>
            </div>
        </form>
    </section>
</template>

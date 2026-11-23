<script setup>
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import { can } from '@/Helpers/can';
import { router, useForm } from '@inertiajs/vue3';
import { Download, ExternalLink, FileText, Image, Lock, Trash2, Upload } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    student: { type: Object, required: true },
    documents: { type: Array, default: () => [] },
    documentTypes: { type: Array, default: () => [] },
});

const canUpload = can('manage student documents');
const canDelete = can('delete student documents');

const fileInput = ref(null);
const form = useForm({ type: 'birth_cert', file: null });

const upload = () =>
    form.post(route('students.documents.store', props.student.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset('file');
            if (fileInput.value) fileInput.value.value = '';
        },
    });

const size = (bytes) => {
    if (!bytes) return '';
    return bytes < 1024 * 1024 ? `${Math.max(1, Math.round(bytes / 1024))} KB` : `${(bytes / 1024 / 1024).toFixed(1)} MB`;
};

const isImage = (doc) => doc.mime_type?.startsWith('image/');
const viewUrl = (doc) => route('students.documents.show', [props.student.id, doc.id]);
const downloadUrl = (doc) => route('students.documents.show', { student: props.student.id, document: doc.id, download: 1 });

const deleting = ref(null);
const destroy = () => {
    router.delete(route('students.documents.destroy', [props.student.id, deleting.value.id]), { preserveScroll: true });
    deleting.value = null;
};
</script>

<template>
    <section class="card-container">
        <div class="mb-1 flex items-center gap-2">
            <h2 class="page-heading-2 !mb-0">Documents</h2>
            <span class="inline-flex items-center gap-1 text-xs text-text-muted"><Lock class="h-3 w-3" /> Private — staff only</span>
        </div>
        <p class="mb-4 text-sm text-text-muted">Birth certificate, admission letters, report cards, receipts and signed consent forms.</p>

        <form v-if="canUpload" class="mb-5 grid gap-3 rounded-lg border border-dashed border-mustBorder-light p-4 sm:grid-cols-[12rem_1fr_auto] sm:items-end dark:border-mustBorder-dark" @submit.prevent="upload">
            <div>
                <InputLabel for="doc_type" value="Type" />
                <SelectInput id="doc_type" v-model="form.type" :options="documentTypes" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.type" />
            </div>
            <div>
                <InputLabel for="doc_file" value="File (PDF or photo, max 10 MB)" />
                <input
                    id="doc_file"
                    ref="fileInput"
                    type="file"
                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                    class="mt-1 block w-full text-sm text-text-muted file:mr-3 file:rounded-md file:border-0 file:bg-mustGreen/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-mustGreen"
                    @input="form.file = $event.target.files[0]"
                />
                <InputError class="mt-2" :message="form.errors.file" />
            </div>
            <button type="submit" class="btn-primary-sm inline-flex items-center gap-1" :disabled="form.processing || !form.file">
                <Upload class="h-4 w-4" /> Upload
            </button>
            <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="h-1 w-full sm:col-span-3">
                {{ form.progress.percentage }}%
            </progress>
        </form>

        <p v-if="!documents.length" class="py-6 text-center text-sm text-text-muted">No documents uploaded yet.</p>

        <ul v-else class="divide-y divide-mustBorder-light dark:divide-mustBorder-dark">
            <li v-for="doc in documents" :key="doc.id" class="flex flex-wrap items-center gap-3 py-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-mustGreen/10 text-mustGreen">
                    <component :is="isImage(doc) ? Image : FileText" class="h-4 w-4" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-mustBlue dark:text-text-inverted">{{ doc.name }}</p>
                    <p class="text-xs text-text-muted">
                        {{ doc.type_label }}
                        <template v-if="doc.size"> · {{ size(doc.size) }}</template>
                        <template v-if="doc.uploaded_at"> · {{ new Date(doc.uploaded_at).toLocaleDateString() }}</template>
                        <template v-if="doc.uploaded_by"> · {{ doc.uploaded_by }}</template>
                        <span v-if="doc.is_consent_evidence" class="ml-1 rounded-full bg-mustGreen/15 px-1.5 py-0.5 text-yl-green-deep">Backs current consent</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a :href="viewUrl(doc)" target="_blank" rel="noopener" class="action-icon" :aria-label="`Open ${doc.name}`">
                        <ExternalLink class="h-4 w-4" />
                    </a>
                    <a :href="downloadUrl(doc)" class="action-icon" :aria-label="`Download ${doc.name}`">
                        <Download class="h-4 w-4" />
                    </a>
                    <button
                        v-if="canDelete && !doc.is_consent_evidence"
                        type="button"
                        class="text-red-500 transition hover:text-red-700"
                        :aria-label="`Delete ${doc.name}`"
                        @click="deleting = doc"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </li>
        </ul>

        <ConfirmationModal :show="!!deleting" :close="() => (deleting = null)" @confirm="destroy">
            <template #title>Delete document</template>
            <template #message>Permanently delete <strong>{{ deleting?.name }}</strong>? The file is removed and can't be recovered.</template>
        </ConfirmationModal>
    </section>
</template>

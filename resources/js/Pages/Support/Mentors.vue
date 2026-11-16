<script setup>
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { date } from '@/Helpers';
import { can } from '@/Helpers/can';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, SquarePen, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    mentors: { type: Array, default: () => [] },
});

const canDelete = can('delete support records');
const showForm = ref(false);
const editing = ref(null);
const form = useForm({ name: '', phone: '', email: '', is_active: true });

const open = (mentor = null) => {
    editing.value = mentor;
    form.clearErrors();
    form.name = mentor?.name ?? '';
    form.phone = mentor?.phone ?? '';
    form.email = mentor?.email ?? '';
    form.is_active = mentor?.is_active ?? true;
    showForm.value = true;
};
const save = () => {
    const opts = { preserveScroll: true, onSuccess: () => (showForm.value = false) };
    editing.value ? form.put(route('support.mentors.update', editing.value.id), opts) : form.post(route('support.mentors.store'), opts);
};

const deleting = ref(null);
const destroy = () => {
    router.delete(route('support.mentors.destroy', deleting.value.id), { preserveScroll: true });
    deleting.value = null;
};
</script>

<template>
    <Head title="Mentors" />

    <AuthenticatedLayout>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="page-heading !mb-1">Mentors and counsellors</h1>
                <p class="text-sm text-text-muted dark:text-text-dark-muted">Mentors don't log in — this list is for assigning them to students and recording sessions.</p>
            </div>
            <button type="button" class="btn-primary inline-flex items-center gap-1" @click="open()"><Plus class="h-4 w-4" /> Add mentor</button>
        </div>

        <div class="card-container overflow-x-auto">
            <p v-if="!mentors.length" class="py-8 text-center text-sm text-text-muted">No mentors yet.</p>
            <table v-else class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">Contact</th>
                        <th class="px-3 py-2 text-right">Students now</th>
                        <th class="px-3 py-2 text-right">Sessions</th>
                        <th class="px-3 py-2">Last session</th>
                        <th class="px-3 py-2"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="m in mentors" :key="m.id" class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark" :class="{ 'opacity-60': !m.is_active }">
                        <td class="px-3 py-2 font-medium text-mustBlue dark:text-text-inverted">
                            {{ m.name }}
                            <span v-if="!m.is_active" class="ml-1 rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-700">Inactive</span>
                        </td>
                        <td class="px-3 py-2 text-text-muted">{{ [m.phone, m.email].filter(Boolean).join(' · ') || '—' }}</td>
                        <td class="px-3 py-2 text-right">{{ m.students_count }}</td>
                        <td class="px-3 py-2 text-right">{{ m.sessions_count }}</td>
                        <td class="px-3 py-2 text-text-muted">{{ m.last_session ? date.formatDate(m.last_session) : '—' }}</td>
                        <td class="px-3 py-2">
                            <div class="flex justify-end gap-3">
                                <button type="button" class="action-icon" :aria-label="`Edit ${m.name}`" @click="open(m)"><SquarePen class="h-4 w-4" /></button>
                                <button v-if="canDelete && !m.in_use" type="button" class="text-red-500 hover:text-red-700" :aria-label="`Delete ${m.name}`" @click="deleting = m"><Trash2 class="h-4 w-4" /></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Modal maxWidth="md" :show="showForm" :closeOnClickOutside="false" @close="showForm = false">
            <div class="p-6">
                <ModalHeader :title="editing ? 'Edit mentor' : 'Add a mentor'" :close="() => (showForm = false)" />
                <form class="space-y-4" @submit.prevent="save">
                    <div>
                        <InputLabel for="m_name" value="Name" />
                        <TextInput id="m_name" v-model="form.name" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="m_phone" value="Phone" />
                        <TextInput id="m_phone" v-model="form.phone" class="mt-1 block w-full" />
                        <InputError class="mt-1" :message="form.errors.phone" />
                    </div>
                    <div>
                        <InputLabel for="m_email" value="Email (optional)" />
                        <TextInput id="m_email" v-model="form.email" type="email" class="mt-1 block w-full" />
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>
                    <label class="flex items-center gap-2 text-sm"><Checkbox v-model="form.is_active" /> Active (can be assigned to students)</label>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-outline-sm" @click="showForm = false">Cancel</button>
                        <button type="submit" class="btn-primary-sm" :disabled="form.processing">Save</button>
                    </div>
                </form>
            </div>
        </Modal>

        <ConfirmationModal :show="!!deleting" :close="() => (deleting = null)" @confirm="destroy">
            <template #title>Delete mentor</template>
            <template #message>Delete <strong>{{ deleting?.name }}</strong>? They have no students or sessions on record.</template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

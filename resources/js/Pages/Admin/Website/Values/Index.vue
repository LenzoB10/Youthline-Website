<script setup>
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, SquarePen, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    values: { type: Array, default: () => [] },
});

const form = useForm({ id: null, name: '', description: '', display_order: 0 });
const showModal = ref(false);

const openCreate = () => {
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (value) => {
    form.id = value.id;
    form.name = value.name;
    form.description = value.description;
    form.display_order = value.display_order;
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => (showModal.value = false) };
    if (form.id) {
        form.put(route('admin.website.values.update', form.id), options);
    } else {
        form.post(route('admin.website.values.store'), options);
    }
};

const showDelete = ref(false);
const deleteId = ref(null);
const confirmDelete = (id) => {
    deleteId.value = id;
    showDelete.value = true;
};
const destroy = () => {
    router.delete(route('admin.website.values.destroy', deleteId.value), { preserveScroll: true });
    showDelete.value = false;
};
</script>

<template>
    <Head title="Core Values" />

    <AuthenticatedLayout>
        <Link :href="route('admin.website.index')" class="mb-4 inline-flex items-center gap-1 text-sm text-mustBlue dark:text-text-dark-muted">
            <ArrowLeft class="h-4 w-4" /> Website content
        </Link>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="page-heading !mb-0">Core Values</h1>
            <button type="button" class="btn-primary inline-flex items-center gap-1" @click="openCreate">
                <Plus class="h-4 w-4" /> New value
            </button>
        </div>

        <div class="card-container overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">Description</th>
                        <th class="px-3 py-2">Order</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!values.length">
                        <td colspan="4" class="px-3 py-8 text-center text-text-muted">No values yet.</td>
                    </tr>
                    <tr
                        v-for="value in values"
                        :key="value.id"
                        class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark"
                    >
                        <td class="px-3 py-2 font-medium text-mustBlue dark:text-text-inverted">{{ value.name }}</td>
                        <td class="max-w-md truncate px-3 py-2 text-text-muted">{{ value.description }}</td>
                        <td class="px-3 py-2 text-text-muted">{{ value.display_order }}</td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-end gap-3">
                                <button type="button" class="action-icon" @click="openEdit(value)">
                                    <SquarePen class="h-4 w-4" />
                                </button>
                                <button type="button" class="action-icon text-red-500" @click="confirmDelete(value.id)">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Modal :show="showModal" @close="showModal = false" maxWidth="md" :closeOnClickOutside="false">
            <div class="p-6">
                <ModalHeader :title="form.id ? 'Edit value' : 'New value'" :close="() => (showModal = false)" />
                <form class="mt-4" @submit.prevent="submit">
                    <div class="grid gap-4">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1" />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="description" value="Description" />
                        <TextArea id="description" v-model="form.description" rows="3" class="mt-1" />
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="display_order" value="Display order" />
                        <TextInput id="display_order" v-model="form.display_order" type="number" min="0" class="mt-1" />
                        <InputError :message="form.errors.display_order" class="mt-1" />
                    </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn-outline-sm" @click="showModal = false">Cancel</button>
                            <button type="submit" class="btn-primary-sm" :disabled="form.processing">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </Modal>

        <ConfirmationModal :show="showDelete" :close="() => (showDelete = false)" @confirm="destroy">
            <template #title>Delete value</template>
            <template #message>Remove this core value from the website?</template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

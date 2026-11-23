<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import DataTable from '@/Components/Common/Datatable.vue';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus, SquarePen, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    institutions: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    types: { type: Array, default: () => [] },
});

const typeLabel = (value) => props.types.find((type) => type.value === value)?.label ?? value;

const showForm = ref(false);
const form = useForm({ id: null, name: '', type: 'secondary', district: '', phone: '' });

const openCreate = () => {
    form.reset();
    form.clearErrors();
    showForm.value = true;
};

const openEdit = (institution) => {
    form.clearErrors();
    form.id = institution.id;
    form.name = institution.name;
    form.type = institution.type;
    form.district = institution.district ?? '';
    form.phone = institution.phone ?? '';
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    form.reset();
};

const submit = () => {
    const options = { preserveScroll: true, onSuccess: closeForm };
    form.id
        ? form.put(route('admin.settings.institutions.update', form.id), options)
        : form.post(route('admin.settings.institutions.store'), options);
};

const showDelete = ref(false);
const deleteId = ref(null);
const confirmDelete = (id) => {
    deleteId.value = id;
    showDelete.value = true;
};
const destroy = () => {
    router.delete(route('admin.settings.institutions.destroy', deleteId.value), { preserveScroll: true });
    showDelete.value = false;
};
</script>

<template>
    <Head title="Institutions" />

    <AuthenticatedLayout>
        <BackLink :href="route('admin.settings.index')">Settings</BackLink>
        <div class="flex items-center justify-between">
            <h1 class="page-heading">Institutions</h1>
            <button type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="openCreate">
                <Plus class="h-4 w-4" /> Add institution
            </button>
        </div>

        <DataTable
            :data="institutions"
            :columns="[
                { key: 'name', label: 'Name' },
                { key: 'type', label: 'Type' },
                { key: 'district', label: 'District' },
                { key: 'enrollments_count', label: 'Enrolments' },
            ]"
            :route-name="route().current()"
            :filters="filters"
            sort-by="name"
            sort-direction="asc"
            :filters-config="{
                type: { component: 'select', label: 'Type', options: types, placeholder: 'All types' },
            }"
        >
            <template #column-type="{ item }">{{ typeLabel(item.type) }}</template>
            <template #column-district="{ item }">{{ item.district || '—' }}</template>

            <template #actions="{ item }">
                <div class="flex gap-3">
                    <button type="button" class="action-icon" :aria-label="`Edit ${item.name}`" @click="openEdit(item)">
                        <SquarePen class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        class="text-red-500 transition hover:text-red-700"
                        :aria-label="`Delete ${item.name}`"
                        @click="confirmDelete(item.id)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </template>
        </DataTable>

        <Modal maxWidth="lg" :show="showForm" :closeOnClickOutside="false" @close="closeForm">
            <div class="p-6">
                <ModalHeader :title="form.id ? 'Edit institution' : 'Add institution'" :close="closeForm" />
                <form class="mt-2 space-y-3" @submit.prevent="submit">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required autofocus />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="type" value="Type" />
                        <SelectInput id="type" v-model="form.type" :options="types" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.type" />
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <InputLabel for="district" value="District" />
                            <TextInput id="district" v-model="form.district" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.district" />
                        </div>
                        <div>
                            <InputLabel for="phone" value="Phone" />
                            <TextInput id="phone" v-model="form.phone" type="tel" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.phone" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn-outline-sm" @click="closeForm">Cancel</button>
                        <button type="submit" class="btn-primary-sm" :disabled="form.processing">Save</button>
                    </div>
                </form>
            </div>
        </Modal>

        <ConfirmationModal :show="showDelete" :close="() => (showDelete = false)" @confirm="destroy">
            <template #title>Delete institution</template>
            <template #message>
                Remove this institution? Institutions already used in enrolment or fee records cannot be deleted.
            </template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

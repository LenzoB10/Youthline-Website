<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import DataTable from '@/Components/Common/Datatable.vue';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus, SquarePen, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    categories: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    currency: { type: String, default: 'MWK' },
});

const money = (value) =>
    value === null || value === undefined
        ? '—'
        : `${props.currency} ${Number(value).toLocaleString(undefined, { maximumFractionDigits: 2 })}`;

const showForm = ref(false);
const form = useForm({ id: null, name: '', unit_cost: '' });

const openForm = (category = null) => {
    form.reset();
    form.clearErrors();
    if (category) {
        form.id = category.id;
        form.name = category.name;
        form.unit_cost = category.unit_cost ?? '';
    }
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    form.reset();
};

const submit = () => {
    const options = { preserveScroll: true, onSuccess: closeForm };
    form.id
        ? form.put(route('admin.settings.item-categories.update', form.id), options)
        : form.post(route('admin.settings.item-categories.store'), options);
};

const deleteId = ref(null);
const destroy = () => {
    router.delete(route('admin.settings.item-categories.destroy', deleteId.value), { preserveScroll: true });
    deleteId.value = null;
};
</script>

<template>
    <Head title="Item Categories" />

    <AuthenticatedLayout>
        <BackLink :href="route('admin.settings.index')">Settings</BackLink>
        <div class="flex items-center justify-between">
            <h1 class="page-heading">Item Categories</h1>
            <button type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="openForm()">
                <Plus class="h-4 w-4" /> Add category
            </button>
        </div>
        <p class="mb-4 text-sm text-text-muted dark:text-text-dark-muted">
            Basic-needs items given to students. The unit cost is a guide used to value distributions.
        </p>

        <DataTable
            :data="categories"
            :columns="[
                { key: 'name', label: 'Item' },
                { key: 'unit_cost', label: 'Unit cost' },
                { key: 'distributions_count', label: 'Times distributed' },
            ]"
            :route-name="route().current()"
            :filters="filters"
            sort-by="name"
            sort-direction="asc"
        >
            <template #column-unit_cost="{ item }">{{ money(item.unit_cost) }}</template>

            <template #actions="{ item }">
                <div class="flex gap-3">
                    <button type="button" class="action-icon" :aria-label="`Edit ${item.name}`" @click="openForm(item)">
                        <SquarePen class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        class="text-red-500 transition hover:text-red-700"
                        :aria-label="`Delete ${item.name}`"
                        @click="deleteId = item.id"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </template>
        </DataTable>

        <Modal maxWidth="md" :show="showForm" :closeOnClickOutside="false" @close="closeForm">
            <div class="p-6">
                <ModalHeader :title="form.id ? 'Edit item category' : 'Add item category'" :close="closeForm" />
                <form class="mt-2 space-y-3" @submit.prevent="submit">
                    <div>
                        <InputLabel for="name" value="Item" />
                        <TextInput id="name" v-model="form.name" class="mt-1 block w-full" placeholder="e.g. Uniforms" required autofocus />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="unit_cost" :value="`Unit cost (${currency})`" />
                        <TextInput id="unit_cost" v-model="form.unit_cost" type="number" min="0" step="0.01" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.unit_cost" />
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn-outline-sm" @click="closeForm">Cancel</button>
                        <button type="submit" class="btn-primary-sm" :disabled="form.processing">Save</button>
                    </div>
                </form>
            </div>
        </Modal>

        <ConfirmationModal :show="deleteId !== null" :close="() => (deleteId = null)" @confirm="destroy">
            <template #title>Delete item category</template>
            <template #message>
                Remove this category? Categories that have already been distributed cannot be deleted.
            </template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import DataTable from '@/Components/Common/Datatable.vue';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus, SquarePen, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

defineProps({
    reasons: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const showForm = ref(false);
const editing = ref(null);
const form = useForm({ name: '', description: '', display_order: 0, is_active: true });

// A reason already on student records keeps its name (students store the text).
const nameLocked = computed(() => (editing.value?.students_count ?? 0) > 0);

const openForm = (reason = null) => {
    form.reset();
    form.clearErrors();
    editing.value = reason;
    if (reason) {
        form.name = reason.name;
        form.description = reason.description ?? '';
        form.display_order = reason.display_order;
        form.is_active = reason.is_active;
    }
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editing.value = null;
    form.reset();
};

const submit = () => {
    const options = { preserveScroll: true, onSuccess: closeForm };
    editing.value
        ? form.put(route('admin.settings.exit-reasons.update', editing.value.id), options)
        : form.post(route('admin.settings.exit-reasons.store'), options);
};

const deleteId = ref(null);
const destroy = () => {
    router.delete(route('admin.settings.exit-reasons.destroy', deleteId.value), { preserveScroll: true });
    deleteId.value = null;
};
</script>

<template>
    <Head title="Exit Reasons" />

    <AuthenticatedLayout>
        <BackLink :href="route('admin.settings.index')">Settings</BackLink>
        <div class="flex items-center justify-between">
            <h1 class="page-heading">Exit Reasons</h1>
            <button type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="openForm()">
                <Plus class="h-4 w-4" /> Add reason
            </button>
        </div>
        <p class="mb-4 text-sm text-text-muted dark:text-text-dark-muted">
            Students are never deleted. When one leaves, a manager must choose one of these active reasons.
            Reasons already used on student records can be deactivated but not renamed or deleted.
        </p>

        <DataTable
            :data="reasons"
            :columns="[
                { key: 'display_order', label: 'Order' },
                { key: 'name', label: 'Reason' },
                { key: 'students_count', label: 'Students' },
                { key: 'is_active', label: 'Status' },
            ]"
            :route-name="route().current()"
            :filters="filters"
            sort-by="display_order"
            sort-direction="asc"
            :filters-config="{
                is_active: {
                    component: 'select',
                    label: 'Status',
                    options: [
                        { value: 'active', label: 'Active' },
                        { value: 'inactive', label: 'Inactive' },
                    ],
                    placeholder: 'All',
                },
            }"
        >
            <template #column-name="{ item }">
                <p class="font-medium text-mustBlue dark:text-text-inverted">{{ item.name }}</p>
                <p v-if="item.description" class="text-xs text-text-muted">{{ item.description }}</p>
            </template>
            <template #column-is_active="{ item }">
                <span
                    class="rounded-full px-2 py-1 text-xs font-medium"
                    :class="item.is_active ? 'bg-mustGreen/15 text-yl-green-deep' : 'bg-gray-100 text-gray-600'"
                >
                    {{ item.is_active ? 'Active' : 'Inactive' }}
                </span>
            </template>

            <template #actions="{ item }">
                <div class="flex gap-3">
                    <button type="button" class="action-icon" :aria-label="`Edit ${item.name}`" @click="openForm(item)">
                        <SquarePen class="h-4 w-4" />
                    </button>
                    <button
                        v-if="!item.students_count"
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

        <Modal maxWidth="lg" :show="showForm" :closeOnClickOutside="false" @close="closeForm">
            <div class="p-6">
                <ModalHeader :title="editing ? 'Edit exit reason' : 'Add exit reason'" :close="closeForm" />
                <form class="mt-2 space-y-3" @submit.prevent="submit">
                    <div>
                        <InputLabel for="name" value="Reason" />
                        <TextInput id="name" v-model="form.name" class="mt-1 block w-full" :disabled="nameLocked" required autofocus />
                        <p v-if="nameLocked" class="mt-1 text-xs text-text-muted">
                            Used for {{ editing.students_count }} student(s), so the name can't change.
                            Deactivate it and add a new reason instead.
                        </p>
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="description" value="Description (optional)" />
                        <TextArea id="description" v-model="form.description" rows="2" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>
                    <div class="grid items-end gap-3 sm:grid-cols-2">
                        <div>
                            <InputLabel for="display_order" value="Display order" />
                            <TextInput id="display_order" v-model="form.display_order" type="number" min="0" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.display_order" />
                        </div>
                        <label class="flex items-center gap-2 pb-2 text-sm text-text-muted">
                            <Checkbox v-model="form.is_active" />
                            Active (offered when a student leaves)
                        </label>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn-outline-sm" @click="closeForm">Cancel</button>
                        <button type="submit" class="btn-primary-sm" :disabled="form.processing">Save</button>
                    </div>
                </form>
            </div>
        </Modal>

        <ConfirmationModal :show="deleteId !== null" :close="() => (deleteId = null)" @confirm="destroy">
            <template #title>Delete exit reason</template>
            <template #message>Delete this reason? It has not been used for any student yet.</template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

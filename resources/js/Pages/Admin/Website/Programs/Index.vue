<script setup>
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Plus, SquarePen, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    programs: { type: Array, default: () => [] },
});

const showDelete = ref(false);
const deleteId = ref(null);

const confirmDelete = (id) => {
    deleteId.value = id;
    showDelete.value = true;
};

const destroy = () => {
    router.delete(route('admin.website.programs.destroy', deleteId.value), { preserveScroll: true });
    showDelete.value = false;
};
</script>

<template>
    <Head title="Programs" />

    <AuthenticatedLayout>
        <Link :href="route('admin.website.index')" class="mb-4 inline-flex items-center gap-1 text-sm text-mustBlue dark:text-text-dark-muted">
            <ArrowLeft class="h-4 w-4" /> Website content
        </Link>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="page-heading !mb-0">Programs</h1>
            <Link :href="route('admin.website.programs.create')" class="btn-primary inline-flex items-center gap-1">
                <Plus class="h-4 w-4" /> New program
            </Link>
        </div>

        <div class="card-container overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                        <th class="px-3 py-2">Program</th>
                        <th class="px-3 py-2">Students</th>
                        <th class="px-3 py-2">Order</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!programs.length">
                        <td colspan="4" class="px-3 py-8 text-center text-text-muted">No programs yet.</td>
                    </tr>
                    <tr
                        v-for="program in programs"
                        :key="program.id"
                        class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark"
                    >
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-3">
                                <img v-if="program.image" :src="program.image" :alt="program.name" class="h-9 w-9 rounded object-cover" />
                                <span class="font-medium text-mustBlue dark:text-text-inverted">{{ program.name }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-text-muted">{{ program.students_count }}</td>
                        <td class="px-3 py-2 text-text-muted">{{ program.display_order }}</td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-end gap-3">
                                <Link :href="route('admin.website.programs.edit', program.id)" class="action-icon">
                                    <SquarePen class="h-4 w-4" />
                                </Link>
                                <button type="button" class="action-icon text-red-500" @click="confirmDelete(program.id)">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ConfirmationModal :show="showDelete" :close="() => (showDelete = false)" @confirm="destroy">
            <template #title>Delete program</template>
            <template #message>Remove this program? Students linked to it are not deleted.</template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, SquarePen, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    members: { type: Array, default: () => [] },
});

const showDelete = ref(false);
const deleteId = ref(null);

const confirmDelete = (id) => {
    deleteId.value = id;
    showDelete.value = true;
};

const destroy = () => {
    router.delete(route('admin.website.team.destroy', deleteId.value), { preserveScroll: true });
    showDelete.value = false;
};
</script>

<template>
    <Head title="Team" />

    <AuthenticatedLayout>
        <BackLink :href="route('admin.website.index')">Website content</BackLink>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="page-heading !mb-0">Leadership Team</h1>
            <Link :href="route('admin.website.team.create')" class="btn-primary inline-flex items-center gap-1">
                <Plus class="h-4 w-4" /> New member
            </Link>
        </div>

        <div class="card-container overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-mustBorder-light text-text-muted dark:border-mustBorder-dark">
                        <th class="px-3 py-2">Member</th>
                        <th class="px-3 py-2">Position</th>
                        <th class="px-3 py-2">Order</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!members.length">
                        <td colspan="4" class="px-3 py-8 text-center text-text-muted">No team members yet.</td>
                    </tr>
                    <tr
                        v-for="member in members"
                        :key="member.id"
                        class="border-b border-mustBorder-light last:border-0 dark:border-mustBorder-dark"
                    >
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-3">
                                <img v-if="member.photo" :src="member.photo" :alt="member.name" class="h-9 w-9 rounded-full object-cover" />
                                <span v-else class="flex h-9 w-9 items-center justify-center rounded-full bg-mustGreen/10 text-xs font-bold text-mustGreen">
                                    {{ member.name?.charAt(0) }}
                                </span>
                                <span class="font-medium text-mustBlue dark:text-text-inverted">{{ member.name }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-text-muted">{{ member.position }}</td>
                        <td class="px-3 py-2 text-text-muted">{{ member.display_order }}</td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-end gap-3">
                                <Link :href="route('admin.website.team.edit', member.id)" class="action-icon">
                                    <SquarePen class="h-4 w-4" />
                                </Link>
                                <button type="button" class="action-icon text-red-500" @click="confirmDelete(member.id)">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ConfirmationModal :show="showDelete" :close="() => (showDelete = false)" @confirm="destroy">
            <template #title>Remove team member</template>
            <template #message>Remove this person from the website team?</template>
        </ConfirmationModal>
    </AuthenticatedLayout>
</template>

<template>
    <div v-for="role in roles" :key="role.id" class="card-container">
        <div class="">
            <Link :href="route('admin.roles.show', role.id)" class="card-title">
                {{ role.name }}
            </Link>

            <div
                class="text-md space-y-1 text-mustText-base dark:text-mustText-muted"
            >
                <span
                    v-for="permission in role.permissions.slice(0, 8)"
                    :key="permission.id"
                    class="text-mustText-neatral inline-block rounded-md px-1 py-1 text-xs dark:text-mustText-light"
                >
                    {{ permission.name }},
                </span>

                <span
                    v-if="role.permissions.length > 8"
                    class="rounded-display inline-block"
                >
                    +{{ role.permissions.length - 8 }} more
                </span>
            </div>

            <div
                class="mt-2 flex flex-col gap-3 md:mt-4 md:flex-row md:items-center md:justify-between"
            >
                <p class="text-md text-mustText-base dark:text-mustText-muted">
                    <template v-if="role.users_count > 0">
                        <span
                            class="font-medium text-gray-900 dark:text-mustText-light"
                            >{{ role.users_count }}</span
                        >
                        users assigned
                    </template>
                    <template v-else>
                        <span
                            class="italic text-gray-500 dark:text-mustText-muted"
                            >No users assigned</span
                        >
                    </template>
                </p>
                <div class="flex gap-3">
                    <Link
                        v-can="'see role details'"
                        :href="route('admin.roles.show', role.id)"
                        class="action-icon"
                    >
                        <Eye class="h-[19px] w-[19px]" />
                    </Link>

                    <button
                        v-can="'update role'"
                        @click="emit('editRole', role)"
                        class="action-icon"
                    >
                        <SquarePen class="h-4 w-4" />
                    </button>

                    <button
                        v-can="'delete role'"
                        @click="emit('confirmDelete', role.id)"
                        class="text-red-500 transition duration-300 hover:text-red-700"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { Eye, SquarePen, Trash2 } from 'lucide-vue-next';

defineProps({
    roles: Array,
});

const emit = defineEmits(['editRole', 'confirmDelete']);
</script>

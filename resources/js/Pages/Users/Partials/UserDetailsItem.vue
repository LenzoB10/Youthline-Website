<template>
    <div
        class="flex items-center gap-3 rounded-lg bg-gray-100 p-3 text-gray-800 dark:bg-background-dark-soft dark:text-mustText-light"
    >
        <Mail v-if="icon === 'email'" />
        <Lock v-if="icon === 'role'" />
        <RefreshCcw v-if="icon === 'status'" />
        <span class="font-medium text-gray-900 dark:text-mustText-light">{{
            title
        }}</span>
    </div>
</template>

<script setup>
import { Mail, Lock, RefreshCcw } from 'lucide-vue-next';
defineProps({
    title: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        required: true,
    },
});
</script>

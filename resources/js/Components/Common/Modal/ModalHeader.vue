<template>
    <div class="flex items-center justify-between">
        <h3 class="modal-title">{{ title }}</h3>
        <X @click="close" class="modal-title-close-icon" />
    </div>
</template>

<script setup>
import { X } from 'lucide-vue-next';
defineProps({
    title: {
        type: String,
        required: true,
    },
    close: {
        type: Function,
        required: true,
    },
});
</script>

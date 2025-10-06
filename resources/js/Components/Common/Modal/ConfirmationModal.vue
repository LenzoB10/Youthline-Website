<template>
    <Modal
        :show="show"
        @close="close"
        maxWidth="sm"
        :closeOnClickOutside="false"
    >
        <div class="p-6">
            <div class="flex justify-end">
                <X @click="close" class="modal-title-close-icon" />
            </div>
            <div class="text-center">
                <!-- Warning Icon -->
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full"
                    :class="{
                        'bg-red-100': type === 'danger',
                        'bg-yellow-100': type === 'warning',
                        'bg-green-100': type === 'success',
                    }"
                >
                    <svg
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        :class="{
                            'text-red-600': type === 'danger',
                            'text-yellow-600': type === 'warning',
                            'text-green-600': type === 'success',
                        }"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                </div>

                <!-- Title -->
                <h3
                    class="mt-3 text-lg font-medium text-text-base dark:text-text-inverted"
                >
                    <slot name="title">Confirm Deletion</slot>
                </h3>

                <!-- Message -->
                <div class="mt-2">
                    <p class="text-sm text-text-base dark:text-text-dark-muted">
                        <slot name="message"
                            >Are you sure you want to delete this item? This
                            action cannot be undone.</slot
                        >
                    </p>
                </div>

                <!-- Action Buttons -->
                <div
                    class="mt-6 flex gap-2"
                    :class="{
                        'flex-row-reverse': type != 'danger',
                        'items-center justify-end': type == 'danger',
                    }"
                >
                    <button
                        type="button"
                        @click="submit"
                        class="btn-danger-sm"
                        v-if="type === 'danger'"
                    >
                        <slot name="confirmText">Delete</slot>
                    </button>
                    <button
                        type="button"
                        @click="submit"
                        class="btn-primary-sm"
                        v-else
                    >
                        <slot name="confirmText">Confirm</slot>
                    </button>
                    <button type="button" @click="close" class="btn-outline-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import Modal from '@/Components/Common/Modal/Modal.vue';
import { X } from 'lucide-vue-next';
defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    close: {
        type: Function,
        required: false,
        default: () => {},
    },
    type: {
        type: String,
        default: 'danger',
        validator: (value) => {
            return ['danger', 'warning', 'success'].includes(value);
        },
    },
});

const emit = defineEmits(['confirm']);

const submit = () => {
    emit('confirm');
};
</script>

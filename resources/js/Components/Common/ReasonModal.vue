<script setup>
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

/**
 * Asks for a written reason, then PUTs { reason } to `url`.
 * Used to reject an approval or mark a payment as an error.
 */
const props = defineProps({
    show: { type: Boolean, default: false },
    url: { type: String, default: null },
    title: { type: String, required: true },
    intro: { type: String, default: '' },
    label: { type: String, default: 'Reason' },
    confirmText: { type: String, default: 'Confirm' },
});
const emit = defineEmits(['close']);

const form = useForm({ reason: '' });

watch(
    () => props.show,
    (open) => {
        if (open) {
            form.reset();
            form.clearErrors();
        }
    },
);

const close = () => emit('close');
const submit = () => form.put(props.url, { preserveScroll: true, onSuccess: close });
</script>

<template>
    <Modal maxWidth="md" :show="show" :closeOnClickOutside="false" @close="close">
        <div class="p-6">
            <ModalHeader :title="title" :close="close" />
            <p v-if="intro" class="mb-4 text-sm text-text-muted">{{ intro }}</p>
            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <InputLabel for="reason" :value="label" />
                    <TextArea id="reason" v-model="form.reason" rows="3" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="form.errors.reason" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-outline-sm" @click="close">Cancel</button>
                    <button type="submit" class="btn-danger-sm" :disabled="form.processing">{{ confirmText }}</button>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle } from 'lucide-vue-next';
import { watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    student: { type: Object, required: true },
});
const emit = defineEmits(['close']);

const form = useForm({ confirm_name: '' });
watch(() => props.show, () => form.reset());

const close = () => emit('close');
const submit = () => form.delete(route('students.destroy', props.student.id));
</script>

<template>
    <Modal maxWidth="lg" :show="show" :closeOnClickOutside="false" @close="close">
        <div class="p-6">
            <ModalHeader title="Permanently delete student" :close="close" />
            <div class="mb-4 flex gap-3 rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-red-500/10 dark:text-red-300">
                <AlertTriangle class="h-5 w-5 shrink-0" />
                <p>
                    This erases <strong>{{ student.full_name }}</strong> and their entire history — enrolments,
                    results, fees, documents and support records. It can't be undone.
                    If the student has simply left, change their status instead.
                </p>
            </div>
            <form class="space-y-3" @submit.prevent="submit">
                <div>
                    <InputLabel for="confirm_name" :value="`Type ${student.full_name} to confirm`" />
                    <TextInput id="confirm_name" v-model="form.confirm_name" class="mt-1 block w-full" autocomplete="off" />
                    <InputError class="mt-2" :message="form.errors.confirm_name" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn-outline-sm" @click="close">Cancel</button>
                    <button type="submit" class="btn-danger-sm" :disabled="form.processing || !form.confirm_name">Delete permanently</button>
                </div>
            </form>
        </div>
    </Modal>
</template>

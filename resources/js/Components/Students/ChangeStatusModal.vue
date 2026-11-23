<script setup>
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    student: { type: Object, required: true },
    transitions: { type: Array, default: () => [] },
    exitReasons: { type: Array, default: () => [] },
});
const emit = defineEmits(['close']);

const today = new Date().toISOString().slice(0, 10);
const form = useForm({ status: '', exit_reason: '', exit_date: today });

watch(
    () => props.show,
    (open) => {
        if (open) {
            form.reset();
            form.clearErrors();
            // Nothing pre-selected: every status is offered, so the choice must be deliberate.
            form.status = '';
        }
    },
);

const selected = computed(() => props.transitions.find((t) => t.value === form.status));
const EXITS = ['graduated', 'dropped_out', 'transferred'];
// Moving a student who left back to a non-exit status reopens the programs their exit closed.
const undoingExit = computed(() => EXITS.includes(props.student.status) && form.status && !EXITS.includes(form.status));
const reasonOptions = computed(() =>
    props.exitReasons.map((reason) => ({ value: reason.name, label: reason.name })),
);
const reasonHelp = computed(() => props.exitReasons.find((r) => r.name === form.exit_reason)?.description);

const close = () => emit('close');
const submit = () =>
    form
        .transform((data) =>
            selected.value?.requires_reason ? data : { status: data.status },
        )
        .put(route('students.status.update', props.student.id), { preserveScroll: true, onSuccess: close });
</script>

<template>
    <Modal maxWidth="lg" :show="show" :closeOnClickOutside="false" @close="close">
        <div class="p-6">
            <ModalHeader title="Change status" :close="close" />
            <p class="mb-4 text-sm text-text-muted">
                {{ student.full_name }} is currently <strong>{{ student.status_label }}</strong>.
                Students are never deleted — their status records where they are.
                If the status was set by mistake, just choose the correct one.
            </p>

            <form class="space-y-4" @submit.prevent="submit">
                <fieldset>
                    <legend class="sr-only">New status</legend>
                    <div class="space-y-2">
                        <label
                            v-for="option in transitions"
                            :key="option.value"
                            class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm transition"
                            :class="form.status === option.value ? 'border-mustGreen bg-mustGreen/5' : 'border-mustBorder-light dark:border-mustBorder-dark'"
                        >
                            <input v-model="form.status" type="radio" name="status" :value="option.value" class="mt-0.5 text-mustGreen focus:ring-mustGreen" />
                            <span>
                                <span class="font-medium text-mustBlue dark:text-text-inverted">{{ option.label }}</span>
                                <span class="block text-text-muted">{{ option.description }}</span>
                            </span>
                        </label>
                    </div>
                    <InputError class="mt-2" :message="form.errors.status" />
                    <p v-if="undoingExit" class="mt-2 text-xs text-text-muted">
                        The programs that were closed when they became {{ student.status_label }} will be reopened.
                    </p>
                </fieldset>

                <div v-if="selected?.requires_reason" class="grid gap-4 sm:grid-cols-[1fr_11rem]">
                    <div>
                        <InputLabel for="exit_reason" value="Reason" />
                        <SelectInput id="exit_reason" v-model="form.exit_reason" :options="reasonOptions" placeholder="Choose a reason" class="mt-1 block w-full" required />
                        <p v-if="reasonHelp" class="mt-1 text-xs text-text-muted">{{ reasonHelp }}</p>
                        <InputError class="mt-2" :message="form.errors.exit_reason" />
                    </div>
                    <div>
                        <InputLabel for="exit_date" value="Date" />
                        <TextInput id="exit_date" v-model="form.exit_date" type="date" :max="today" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.exit_date" />
                    </div>
                    <p v-if="['graduated', 'dropped_out', 'transferred'].includes(form.status)" class="text-xs text-text-muted sm:col-span-2">
                        Their current programs will be ended on this date. All records are kept.
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn-outline-sm" @click="close">Cancel</button>
                    <button type="submit" class="btn-primary-sm" :disabled="form.processing || !form.status">Save status</button>
                </div>
            </form>
        </div>
    </Modal>
</template>

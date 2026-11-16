<script setup>
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    sponsor: { type: Object, default: null }, // editing when set
    types: { type: Array, default: () => [] },
});
const emit = defineEmits(['saved', 'cancel']);

const form = useForm({
    name: props.sponsor?.name ?? '',
    type: props.sponsor?.type ?? 'person',
    email: props.sponsor?.email ?? '',
    phone: props.sponsor?.phone ?? '',
    notes: props.sponsor?.notes ?? '',
});

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => emit('saved') };
    props.sponsor ? form.put(route('sponsors.update', props.sponsor.id), options) : form.post(route('sponsors.store'), options);
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-[1fr_11rem]">
            <div>
                <InputLabel for="sp_name" value="Name" />
                <TextInput id="sp_name" v-model="form.name" class="mt-1 block w-full" required />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>
            <div>
                <InputLabel for="sp_type" value="Type" />
                <SelectInput id="sp_type" v-model="form.type" :options="types" required />
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <InputLabel for="sp_email" value="Email" />
                <TextInput id="sp_email" v-model="form.email" type="email" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>
            <div>
                <InputLabel for="sp_phone" value="Phone" />
                <TextInput id="sp_phone" v-model="form.phone" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="form.errors.phone" />
            </div>
        </div>
        <div>
            <InputLabel for="sp_notes" value="Notes (private)" />
            <TextArea id="sp_notes" v-model="form.notes" rows="3" class="mt-1 block w-full" />
            <InputError class="mt-1" :message="form.errors.notes" />
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn-outline-sm" @click="emit('cancel')">Cancel</button>
            <button type="submit" class="btn-primary-sm" :disabled="form.processing">Save</button>
        </div>
    </form>
</template>

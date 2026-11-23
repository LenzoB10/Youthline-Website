<script setup>
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { useForm } from '@inertiajs/vue3';

// Add a family (no student needed) or edit an existing one.
const props = defineProps({
    household: { type: Object, default: null },
});
const emit = defineEmits(['saved', 'cancel']);

const form = useForm({
    head_name: props.household?.head_name ?? '',
    village_area: props.household?.village_area ?? '',
    district: props.household?.district ?? '',
    phone: props.household?.phone ?? '',
    members_count: props.household?.members_count ?? '',
    notes: props.household?.notes ?? '',
    is_supported: props.household?.is_supported ?? true,
});

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => emit('saved') };
    const payload = (d) => ({ ...d, members_count: d.members_count === '' ? null : d.members_count });
    props.household
        ? form.transform(payload).put(route('support.families.update', props.household.id), options)
        : form.transform(payload).post(route('support.families.store'), options);
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div>
            <InputLabel for="fam_head" value="Head of the family" />
            <TextInput id="fam_head" v-model="form.head_name" class="mt-1 block w-full" required />
            <InputError class="mt-1" :message="form.errors.head_name" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <InputLabel for="fam_area" value="Village / area" />
                <TextInput id="fam_area" v-model="form.village_area" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="form.errors.village_area" />
            </div>
            <div>
                <InputLabel for="fam_district" value="District" />
                <TextInput id="fam_district" v-model="form.district" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="form.errors.district" />
            </div>
            <div>
                <InputLabel for="fam_phone" value="Phone" />
                <TextInput id="fam_phone" v-model="form.phone" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="form.errors.phone" />
            </div>
            <div>
                <InputLabel for="fam_members" value="People in the home" />
                <TextInput id="fam_members" v-model="form.members_count" type="number" min="1" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="form.errors.members_count" />
            </div>
        </div>
        <div>
            <InputLabel for="fam_notes" value="Why Youthline supports this family (optional)" />
            <TextArea id="fam_notes" v-model="form.notes" rows="3" class="mt-1 block w-full" />
            <InputError class="mt-1" :message="form.errors.notes" />
        </div>
        <label class="flex items-start gap-2 text-sm">
            <Checkbox v-model="form.is_supported" class="mt-0.5" />
            <span>
                Currently supported
                <span class="block text-text-muted">Untick when support ends — the family's history is kept and visit reminders stop.</span>
            </span>
        </label>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn-outline-sm" @click="emit('cancel')">Cancel</button>
            <button type="submit" class="btn-primary-sm" :disabled="form.processing">{{ household ? 'Save' : 'Add family' }}</button>
        </div>
    </form>
</template>

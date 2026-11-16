<script setup>
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import SearchableSelectInput from '@/Components/FormElements/SearchableSelectInput.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    student: { type: Object, default: null },
    currentYear: { type: String, default: null },
    households: { type: Array, default: () => [] },
    institutions: { type: Array, default: () => [] },
    levels: { type: Array, default: () => [] },
    programs: { type: Array, default: () => [] },
});

const editing = computed(() => !!props.student);
const s = props.student ?? {};

const form = useForm({
    first_name: s.first_name ?? '',
    last_name: s.last_name ?? '',
    date_of_birth: s.date_of_birth ?? '',
    gender: s.gender ?? '',
    phone: s.phone ?? '',
    home_location: s.home_location ?? '',
    guardian_name: s.guardian_name ?? '',
    guardian_phone: s.guardian_phone ?? '',
    guardian_relationship: s.guardian_relationship ?? '',
    household_mode: s.household_id ? 'existing' : 'none',
    household_id: s.household_id ?? null,
    household: { head_name: '', village_area: '', district: '', phone: '', members_count: '' },
    institution_name: '',
    level: '',
    course: '',
    program_ids: s.program_ids ?? [],
});

const genders = [
    { value: 'female', label: 'Female' },
    { value: 'male', label: 'Male' },
    { value: 'other', label: 'Other' },
];

const householdModes = [
    { value: 'none', label: 'Not linked yet' },
    { value: 'existing', label: 'Existing household (e.g. a brother or sister is already supported)' },
    { value: 'new', label: 'New household' },
];

const toggleProgram = (id) => {
    form.program_ids = form.program_ids.includes(id)
        ? form.program_ids.filter((p) => p !== id)
        : [...form.program_ids, id];
};

const submit = () => {
    form.transform((data) => {
        const payload = { ...data };
        if (payload.household_mode !== 'new') delete payload.household;
        if (payload.household_mode !== 'existing') payload.household_id = null;
        if (editing.value) {
            delete payload.institution_name;
            delete payload.level;
            delete payload.course;
        }
        return payload;
    });

    editing.value
        ? form.put(route('students.update', props.student.id))
        : form.post(route('students.store'));
};
</script>

<template>
    <Head :title="editing ? `Edit ${student.full_name}` : 'Add student'" />

    <AuthenticatedLayout>
        <Link
            :href="editing ? route('students.show', student.id) : route('students.index')"
            class="mb-4 inline-flex items-center gap-1 text-sm text-mustBlue dark:text-text-dark-muted"
        >
            <ArrowLeft class="h-4 w-4" /> {{ editing ? student.full_name : 'Students' }}
        </Link>
        <h1 class="page-heading">{{ editing ? 'Edit student' : 'Add student' }}</h1>
        <p v-if="!editing" class="mb-6 text-sm text-text-muted dark:text-text-dark-muted">
            New students start as <strong>Applicant</strong>. After the assessment (usually a home visit),
            change their status to Active from their profile.
        </p>

        <form class="max-w-4xl space-y-6" @submit.prevent="submit">
            <!-- Personal details -->
            <section class="card-container">
                <h2 class="page-heading-2">Personal details</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="first_name" value="First name" />
                        <TextInput id="first_name" v-model="form.first_name" class="mt-1 block w-full" required autofocus />
                        <InputError class="mt-2" :message="form.errors.first_name" />
                    </div>
                    <div>
                        <InputLabel for="last_name" value="Last name" />
                        <TextInput id="last_name" v-model="form.last_name" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.last_name" />
                    </div>
                    <div>
                        <InputLabel for="date_of_birth" value="Date of birth" />
                        <TextInput id="date_of_birth" v-model="form.date_of_birth" type="date" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.date_of_birth" />
                    </div>
                    <div>
                        <InputLabel for="gender" value="Gender" />
                        <SelectInput id="gender" v-model="form.gender" :options="genders" placeholder="Select" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.gender" />
                    </div>
                    <div>
                        <InputLabel for="phone" value="Student's phone (optional)" />
                        <TextInput id="phone" v-model="form.phone" type="tel" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.phone" />
                    </div>
                    <div>
                        <InputLabel for="home_location" value="Home location" />
                        <TextInput id="home_location" v-model="form.home_location" class="mt-1 block w-full" placeholder="Village / area, district" />
                        <InputError class="mt-2" :message="form.errors.home_location" />
                    </div>
                </div>
            </section>

            <!-- Guardian & household -->
            <section class="card-container">
                <h2 class="page-heading-2">Guardian and household</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <InputLabel for="guardian_name" value="Guardian's name" />
                        <TextInput id="guardian_name" v-model="form.guardian_name" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.guardian_name" />
                    </div>
                    <div>
                        <InputLabel for="guardian_relationship" value="Relationship" />
                        <TextInput id="guardian_relationship" v-model="form.guardian_relationship" class="mt-1 block w-full" placeholder="e.g. Mother, Aunt" />
                        <InputError class="mt-2" :message="form.errors.guardian_relationship" />
                    </div>
                    <div>
                        <InputLabel for="guardian_phone" value="Guardian's phone" />
                        <TextInput id="guardian_phone" v-model="form.guardian_phone" type="tel" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.guardian_phone" />
                    </div>
                </div>

                <fieldset class="mt-5">
                    <legend class="text-sm font-medium text-mustBlue dark:text-text-inverted">Household</legend>
                    <div class="mt-2 space-y-2">
                        <label v-for="mode in householdModes" :key="mode.value" class="flex items-center gap-2 text-sm">
                            <input v-model="form.household_mode" type="radio" name="household_mode" :value="mode.value" class="text-mustGreen focus:ring-mustGreen" />
                            {{ mode.label }}
                        </label>
                    </div>
                    <InputError class="mt-2" :message="form.errors.household_mode" />
                </fieldset>

                <div v-if="form.household_mode === 'existing'" class="mt-3 max-w-xl">
                    <InputLabel for="household_id" value="Choose household" />
                    <SearchableSelectInput
                        id="household_id"
                        v-model="form.household_id"
                        :options="households"
                        placeholder="Search by head of household or village"
                        class="mt-1"
                    />
                    <InputError class="mt-2" :message="form.errors.household_id" />
                </div>

                <div v-if="form.household_mode === 'new'" class="mt-3 grid gap-4 rounded-lg border border-mustBorder-light p-4 sm:grid-cols-2 dark:border-mustBorder-dark">
                    <div>
                        <InputLabel for="hh_head" value="Head of household" />
                        <TextInput id="hh_head" v-model="form.household.head_name" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors['household.head_name']" />
                    </div>
                    <div>
                        <InputLabel for="hh_phone" value="Household phone" />
                        <TextInput id="hh_phone" v-model="form.household.phone" type="tel" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors['household.phone']" />
                    </div>
                    <div>
                        <InputLabel for="hh_village" value="Village / area" />
                        <TextInput id="hh_village" v-model="form.household.village_area" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors['household.village_area']" />
                    </div>
                    <div>
                        <InputLabel for="hh_district" value="District" />
                        <TextInput id="hh_district" v-model="form.household.district" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors['household.district']" />
                    </div>
                    <div>
                        <InputLabel for="hh_members" value="People living in the home" />
                        <TextInput id="hh_members" v-model="form.household.members_count" type="number" min="1" max="50" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors['household.members_count']" />
                    </div>
                </div>
            </section>

            <!-- School & level (registration only) -->
            <section v-if="!editing" class="card-container">
                <h2 class="page-heading-2 !mb-1">School and level</h2>
                <p class="mb-4 text-sm text-text-muted">
                    <template v-if="currentYear">Recorded for the current academic year, <strong>{{ currentYear }}</strong>.</template>
                    <template v-else>
                        No current academic year is set, so a level can't be recorded yet.
                        An Admin can set one under Settings → Academic Calendar.
                    </template>
                </p>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <InputLabel for="institution_name" value="School / university" />
                        <TextInput
                            id="institution_name"
                            v-model="form.institution_name"
                            type="text"
                            list="institution-suggestions"
                            placeholder="e.g. Zomba Secondary School"
                            autocomplete="off"
                            class="mt-1 block w-full"
                        />
                        <datalist id="institution-suggestions">
                            <option v-for="i in institutions" :key="i.value" :value="i.label" />
                        </datalist>
                        <p class="mt-1 text-xs text-text-muted">Type the name — pick a suggestion if it's already on the list, or a new school is added for you.</p>
                        <InputError class="mt-2" :message="form.errors.institution_name" />
                    </div>
                    <div>
                        <InputLabel for="level" value="Current level" />
                        <SelectInput id="level" v-model="form.level" :options="levels" placeholder="Select level" class="mt-1 block w-full" :disabled="!currentYear" />
                        <InputError class="mt-2" :message="form.errors.level" />
                    </div>
                    <div class="sm:col-span-3">
                        <InputLabel for="course" value="Course (college / university only)" />
                        <TextInput id="course" v-model="form.course" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.course" />
                    </div>
                </div>
            </section>

            <!-- Programs -->
            <section class="card-container">
                <h2 class="page-heading-2 !mb-1">Programs</h2>
                <p class="mb-4 text-sm text-text-muted">
                    Tick every program this student is part of.
                    <template v-if="editing">Unticking ends their membership from today; the history is kept.</template>
                </p>
                <div class="grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="program in programs"
                        :key="program.id"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm transition"
                        :class="form.program_ids.includes(program.id) ? 'border-mustGreen bg-mustGreen/5' : 'border-mustBorder-light dark:border-mustBorder-dark'"
                    >
                        <input
                            type="checkbox"
                            class="checkbox-input"
                            :checked="form.program_ids.includes(program.id)"
                            @change="toggleProgram(program.id)"
                        />
                        {{ program.name }}
                    </label>
                </div>
                <InputError class="mt-2" :message="form.errors.program_ids" />
            </section>

            <div class="flex justify-end gap-2">
                <Link :href="editing ? route('students.show', student.id) : route('students.index')" class="btn-outline">Cancel</Link>
                <button type="submit" class="btn-primary" :disabled="form.processing">
                    {{ editing ? 'Save changes' : 'Add student' }}
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

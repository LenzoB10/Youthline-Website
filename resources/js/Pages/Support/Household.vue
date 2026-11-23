<script setup>
import BackLink from '@/Components/Common/BackLink.vue';
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import Checkbox from '@/Components/FormElements/Checkbox.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import FamilyForm from '@/Components/Support/FamilyForm.vue';
import HomeVisitsPanel from '@/Components/Support/HomeVisitsPanel.vue';
import { date } from '@/Helpers';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Camera, MapPin, Phone, SquarePen, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    currency: { type: String, default: 'MWK' },
    household: { type: Object, required: true },
    photoConsent: { type: Boolean, default: false },
    nextVisitDue: { type: String, default: null },
    visitOverdue: { type: Boolean, default: false },
    defaultNextVisitMonths: { type: Number, default: 12 },
    supportTypes: { type: Array, default: () => [] },
    visits: { type: Array, default: () => [] },
    support: { type: Array, default: () => [] },
    supportTotal: { type: Number, default: 0 },
});

const today = new Date().toISOString().slice(0, 10);
const editing = ref(false);

// The visits panel expects one object with the visit and support details.
const panelData = computed(() => ({
    photoConsent: props.photoConsent,
    nextVisitDue: props.nextVisitDue,
    visitOverdue: props.household.is_supported && props.visitOverdue,
    defaultNextVisitMonths: props.defaultNextVisitMonths,
    supportTypes: props.supportTypes,
    visits: props.visits,
    support: props.support,
    supportTotal: props.supportTotal,
}));

// --- Photo consent (the family's own) ---
const consentForm = useForm({
    photo_consent: props.household.photo_consent,
    photo_consent_on: props.household.photo_consent_on ?? today,
});
const saveConsent = () => consentForm.put(route('support.families.consent', props.household.id), { preserveScroll: true });
const consentFromStudent = computed(() => props.photoConsent && !props.household.photo_consent);
</script>

<template>
    <Head :title="`${household.name} family`" />

    <AuthenticatedLayout>
        <BackLink :href="route('support.families')">All families</BackLink>

        <!-- Family header -->
        <div class="card-container mb-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-bold text-mustBlue dark:text-text-inverted">{{ household.name }} family</h1>
                        <span v-if="household.is_supported" class="rounded-full bg-mustGreen/15 px-2.5 py-0.5 text-xs font-medium text-yl-green-deep">Currently supported</span>
                        <span v-else class="rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-medium text-gray-700">No longer supported</span>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="household.students.length ? 'bg-yl-gold/15 text-amber-800' : 'bg-gray-100 text-gray-700'">
                            {{ household.students.length ? 'Student family' : 'Community family' }}
                        </span>
                    </div>
                    <p class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-text-muted">
                        <span v-if="household.village_area || household.district" class="inline-flex items-center gap-1">
                            <MapPin class="h-4 w-4" /> {{ [household.village_area, household.district].filter(Boolean).join(', ') }}
                        </span>
                        <span v-if="household.phone" class="inline-flex items-center gap-1"><Phone class="h-4 w-4" /> {{ household.phone }}</span>
                        <span v-if="household.members_count" class="inline-flex items-center gap-1"><Users class="h-4 w-4" /> {{ household.members_count }} people in the home</span>
                    </p>
                </div>
                <button type="button" class="btn-outline-sm inline-flex items-center gap-1" @click="editing = true"><SquarePen class="h-4 w-4" /> Edit family</button>
            </div>

            <p v-if="household.notes" class="mt-3 whitespace-pre-line rounded-md bg-mustGreen/5 p-3 text-sm">{{ household.notes }}</p>

            <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                <span class="text-text-muted">Students from this home:</span>
                <template v-if="household.students.length">
                    <Link
                        v-for="s in household.students"
                        :key="s.id"
                        :href="route('students.show', s.id)"
                        class="rounded-full bg-mustGreen/10 px-3 py-1 text-mustGreen hover:bg-mustGreen/20"
                    >
                        {{ s.name }} <span class="text-xs text-text-muted">· {{ s.status }}</span>
                    </Link>
                </template>
                <span v-else class="text-text-muted">none — Youthline supports this family directly.</span>
            </div>
        </div>

        <!-- Photo consent -->
        <section class="card-container mb-4">
            <h2 class="page-heading-2 !mb-1 flex items-center gap-2"><Camera class="h-4 w-4" /> Photo consent</h2>
            <p class="mb-3 text-sm text-text-muted">Photos (home visits and support given) can only be added when the family agrees.</p>
            <form class="flex flex-wrap items-end gap-4" @submit.prevent="saveConsent">
                <label class="flex items-center gap-2 text-sm">
                    <Checkbox v-model="consentForm.photo_consent" /> The family agreed to photos of their home
                </label>
                <div v-if="consentForm.photo_consent" class="w-48">
                    <InputLabel for="consent_on" value="Date agreed" />
                    <TextInput id="consent_on" v-model="consentForm.photo_consent_on" type="date" :max="today" class="mt-1 block w-full" required />
                </div>
                <button type="submit" class="btn-primary-sm" :disabled="consentForm.processing || !consentForm.isDirty">Save</button>
            </form>
            <InputError class="mt-1" :message="consentForm.errors.photo_consent_on" />
            <p v-if="household.photo_consent && household.photo_consent_on" class="mt-2 text-xs text-text-muted">Agreed on {{ date.formatDate(household.photo_consent_on) }}.</p>
            <p v-else-if="consentFromStudent" class="mt-2 text-xs text-text-muted">Photos are already allowed through a student's guardian consent.</p>
        </section>

        <HomeVisitsPanel :key="`${photoConsent}`" :household="household" :data="panelData" :currency="currency" />

        <Modal maxWidth="lg" :show="editing" :closeOnClickOutside="false" @close="editing = false">
            <div class="p-6">
                <ModalHeader title="Edit family" :close="() => (editing = false)" />
                <FamilyForm :household="household" @saved="editing = false" @cancel="editing = false" />
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

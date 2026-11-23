<script setup>
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';
import Modal from '@/Components/Common/Modal/Modal.vue';
import ModalHeader from '@/Components/Common/Modal/ModalHeader.vue';
import InputError from '@/Components/FormElements/InputError.vue';
import InputLabel from '@/Components/FormElements/InputLabel.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { date } from '@/Helpers';
import { can } from '@/Helpers/can';
import { formatMoney } from '@/Helpers/money';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import { router, useForm } from '@inertiajs/vue3';
import { Camera, HandHeart, Home, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

// Home visits and the support given to one family (Support → Families → a family).
const props = defineProps({
    household: { type: Object, required: true }, // { id, name }
    data: { type: Object, required: true },
    currency: { type: String, default: 'MWK' },
});

const canManage = can('manage home visits');
const canDelete = can('delete support records');
const today = new Date().toISOString().slice(0, 10);
const money = (v) => formatMoney(v, props.currency);
const fileClass =
    'mt-1 block w-full text-sm text-text-muted file:mr-3 file:rounded-md file:border-0 file:bg-mustGreen/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-mustGreen';

// --- Visits ---
const showVisit = ref(false);
const visitForm = useForm({ visit_date: today, members_count: '', living_conditions: '', income_sources: '', needs_identified: '', next_visit_due: '', photos: [] });
const openVisit = () => {
    visitForm.reset();
    visitForm.clearErrors();
    visitForm.visit_date = today;
    showVisit.value = true;
};
const saveVisit = () =>
    visitForm.post(route('support.families.visits.store', props.household.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (showVisit.value = false),
    });

// --- Support given ---
const showSupport = ref(false);
const supportForm = useForm({ given_on: today, type: '', description: '', amount: '', note: '', photos: [] });
const openSupport = () => {
    supportForm.reset();
    supportForm.clearErrors();
    supportForm.given_on = today;
    showSupport.value = true;
};
const saveSupport = () =>
    supportForm.post(route('support.families.support.store', props.household.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (showSupport.value = false),
    });

const deleting = ref(null); // { kind: 'visit'|'support entry', item }
const destroy = () => {
    const { kind, item } = deleting.value;
    router.delete(route(kind === 'visit' ? 'support.families.visits.destroy' : 'support.families.support.destroy', [props.household.id, item.id]), { preserveScroll: true });
    deleting.value = null;
};
</script>

<template>
    <div class="space-y-4">
        <!-- Visits -->
        <section class="card-container">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="page-heading-2 !mb-0 flex items-center gap-2"><Home class="h-4 w-4" /> Home visits</h2>
                    <p class="text-sm text-text-muted">
                        Next visit due:
                        <strong :class="data.visitOverdue ? 'text-red-700' : 'text-mustBlue dark:text-text-inverted'">
                            {{ data.nextVisitDue ? date.formatDate(data.nextVisitDue) : 'never visited' }}
                        </strong>
                        <span v-if="data.visitOverdue" class="ml-1 rounded-full px-2 py-0.5 text-xs bg-gray-700 text-white dark:bg-gray-300 dark:text-gray-900">Due</span>
                    </p>
                </div>
                <button v-if="canManage" type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="openVisit">
                    <Plus class="h-4 w-4" /> Record visit
                </button>
            </div>

            <p v-if="!data.visits.length" class="py-6 text-center text-sm text-text-muted">No visits recorded yet.</p>
            <ul v-else class="space-y-3">
                <li v-for="v in data.visits" :key="v.id" class="rounded-lg border border-mustBorder-light p-4 text-sm dark:border-mustBorder-dark">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span class="font-semibold text-mustBlue dark:text-text-inverted">{{ date.formatDate(v.visit_date) }}</span>
                        <span v-if="v.members_count" class="text-text-muted">{{ v.members_count }} people in the home</span>
                        <span class="text-text-muted">· next visit {{ date.formatDate(v.next_visit_due) }}</span>
                        <span class="text-xs text-text-muted">· by {{ v.recorded_by ?? 'unknown' }}</span>
                        <button v-if="canDelete" type="button" class="ml-auto text-red-500 hover:text-red-700" :aria-label="`Delete visit on ${v.visit_date}`" @click="deleting = { kind: 'visit', item: v }">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                    <dl class="mt-2 grid gap-x-4 gap-y-1 sm:grid-cols-[10rem_1fr]">
                        <template v-if="v.living_conditions"><dt class="text-text-muted">Living conditions</dt><dd>{{ v.living_conditions }}</dd></template>
                        <template v-if="v.income_sources"><dt class="text-text-muted">Income sources</dt><dd>{{ v.income_sources }}</dd></template>
                        <template v-if="v.needs_identified"><dt class="text-text-muted">Needs found</dt><dd>{{ v.needs_identified }}</dd></template>
                    </dl>
                    <div v-if="v.photos.length" class="mt-3 flex flex-wrap gap-2">
                        <a v-for="p in v.photos" :key="p.id" :href="p.url" target="_blank" rel="noopener" :aria-label="`Open photo ${p.name ?? ''}`">
                            <img :src="p.url" alt="" class="h-20 w-28 rounded-md object-cover" loading="lazy" />
                        </a>
                    </div>
                </li>
            </ul>
        </section>

        <!-- Support given -->
        <section class="card-container">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="page-heading-2 !mb-0 flex items-center gap-2"><HandHeart class="h-4 w-4" /> Support given</h2>
                    <p class="text-sm text-text-muted">
                        Everything Youthline has given this family: money, food, household items, home repairs and more.
                        <template v-if="data.support.length"> Total <strong class="text-mustBlue dark:text-text-inverted">{{ money(data.supportTotal) }}</strong>.</template>
                    </p>
                </div>
                <button v-if="canManage" type="button" class="btn-primary-sm inline-flex items-center gap-1" @click="openSupport">
                    <Plus class="h-4 w-4" /> Record support
                </button>
            </div>

            <p v-if="!data.support.length" class="py-6 text-center text-sm text-text-muted">No support recorded yet.</p>
            <ul v-else class="divide-y divide-mustBorder-light dark:divide-mustBorder-dark">
                <li v-for="s in data.support" :key="s.id" class="py-3 text-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-mustBlue dark:text-text-inverted">{{ s.description }}</span>
                                <span class="rounded-full bg-mustGreen/15 px-2 py-0.5 text-xs text-yl-green-deep dark:text-accent-light">{{ s.type_label }}</span>
                            </p>
                            <p class="text-text-muted">
                                {{ date.formatDate(s.given_on) }}
                                <template v-if="s.recorded_by"> · by {{ s.recorded_by }}</template>
                            </p>
                            <p v-if="s.note" class="mt-1 text-text-muted">{{ s.note }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-semibold text-mustBlue dark:text-text-inverted">{{ s.amount !== null ? money(s.amount) : '—' }}</span>
                            <button v-if="canDelete" type="button" class="text-red-500 hover:text-red-700" :aria-label="`Delete support on ${s.given_on}`" @click="deleting = { kind: 'support entry', item: s }">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    <div v-if="s.photos.length" class="mt-3 flex flex-wrap gap-2">
                        <a v-for="p in s.photos" :key="p.id" :href="p.url" target="_blank" rel="noopener" :aria-label="`Open photo ${p.name ?? ''}`">
                            <img :src="p.url" alt="" class="h-20 w-28 rounded-md object-cover" loading="lazy" />
                        </a>
                    </div>
                </li>
            </ul>
        </section>

        <!-- Record visit -->
        <Modal maxWidth="2xl" :show="showVisit" :closeOnClickOutside="false" @close="showVisit = false">
            <div class="p-6">
                <ModalHeader title="Record a home visit" :close="() => (showVisit = false)" />
                <form class="space-y-4" @submit.prevent="saveVisit">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <InputLabel for="v_date" value="Visit date" />
                            <TextInput id="v_date" v-model="visitForm.visit_date" type="date" :max="today" class="mt-1 block w-full" required />
                            <InputError class="mt-1" :message="visitForm.errors.visit_date" />
                        </div>
                        <div>
                            <InputLabel for="v_members" value="People in the home" />
                            <TextInput id="v_members" v-model="visitForm.members_count" type="number" min="1" class="mt-1 block w-full" />
                            <InputError class="mt-1" :message="visitForm.errors.members_count" />
                        </div>
                        <div>
                            <InputLabel for="v_next" value="Next visit due" />
                            <TextInput id="v_next" v-model="visitForm.next_visit_due" type="date" class="mt-1 block w-full" />
                            <p class="mt-1 text-xs text-text-muted">Blank = in {{ data.defaultNextVisitMonths }} months</p>
                            <InputError class="mt-1" :message="visitForm.errors.next_visit_due" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="v_living" value="Living conditions" />
                        <TextArea id="v_living" v-model="visitForm.living_conditions" rows="2" class="mt-1 block w-full" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="v_income" value="Income sources" />
                            <TextArea id="v_income" v-model="visitForm.income_sources" rows="2" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="v_needs" value="Needs found" />
                            <TextArea id="v_needs" v-model="visitForm.needs_identified" rows="2" class="mt-1 block w-full" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="v_photos" value="Photos (optional)" />
                        <template v-if="data.photoConsent">
                            <input id="v_photos" type="file" multiple accept=".jpg,.jpeg,.png,.webp" :class="fileClass" @input="visitForm.photos = Array.from($event.target.files)" />
                            <p class="mt-1 text-xs text-text-muted">Up to 8 photos, 8 MB each. Kept private.</p>
                        </template>
                        <p v-else class="mt-1 flex items-start gap-2 rounded-md bg-yl-gold/10 p-2 text-xs text-amber-800 dark:bg-yl-gold/10 dark:text-yl-gold">
                            <Camera class="mt-0.5 h-3.5 w-3.5 shrink-0" /> This family hasn't given photo consent, so photos can't be added. Record it in the Photo consent box.
                        </p>
                        <InputError class="mt-1" :message="visitForm.errors.photos || visitForm.errors['photos.0']" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-outline-sm" @click="showVisit = false">Cancel</button>
                        <button type="submit" class="btn-primary-sm" :disabled="visitForm.processing">Save visit</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Record support -->
        <Modal maxWidth="lg" :show="showSupport" :closeOnClickOutside="false" @close="showSupport = false">
            <div class="p-6">
                <ModalHeader title="Record support given" :close="() => (showSupport = false)" />
                <form class="space-y-4" @submit.prevent="saveSupport">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="s_date" value="Date given" />
                            <TextInput id="s_date" v-model="supportForm.given_on" type="date" :max="today" class="mt-1 block w-full" required />
                            <InputError class="mt-1" :message="supportForm.errors.given_on" />
                        </div>
                        <div>
                            <InputLabel for="s_type" value="Type" />
                            <SelectInput id="s_type" v-model="supportForm.type" :options="data.supportTypes" placeholder="Choose a type" class="mt-1 block w-full" required />
                            <InputError class="mt-1" :message="supportForm.errors.type" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="s_desc" value="What was given" />
                        <TextInput id="s_desc" v-model="supportForm.description" maxlength="250" placeholder="e.g. Maize and cooking oil; roof sheets replaced" class="mt-1 block w-full" required />
                        <InputError class="mt-1" :message="supportForm.errors.description" />
                    </div>
                    <div>
                        <InputLabel for="s_amount" :value="`Amount or value (${currency}, optional)`" />
                        <TextInput id="s_amount" v-model="supportForm.amount" type="number" min="0" step="0.01" placeholder="e.g. 45000" class="mt-1 block w-full" />
                        <InputError class="mt-1" :message="supportForm.errors.amount" />
                    </div>
                    <div>
                        <InputLabel for="s_note" value="Note (optional)" />
                        <TextInput id="s_note" v-model="supportForm.note" maxlength="255" class="mt-1 block w-full" />
                        <InputError class="mt-1" :message="supportForm.errors.note" />
                    </div>
                    <div>
                        <InputLabel for="s_photos" value="Photos (optional)" />
                        <template v-if="data.photoConsent">
                            <input id="s_photos" type="file" multiple accept=".jpg,.jpeg,.png,.webp" :class="fileClass" @input="supportForm.photos = Array.from($event.target.files)" />
                            <p class="mt-1 text-xs text-text-muted">Up to 8 photos, 8 MB each. Kept private.</p>
                        </template>
                        <p v-else class="mt-1 flex items-start gap-2 rounded-md bg-yl-gold/10 p-2 text-xs text-amber-800 dark:bg-yl-gold/10 dark:text-yl-gold">
                            <Camera class="mt-0.5 h-3.5 w-3.5 shrink-0" /> This family hasn't given photo consent, so photos can't be added. Record it in the Photo consent box.
                        </p>
                        <InputError class="mt-1" :message="supportForm.errors.photos || supportForm.errors['photos.0']" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-outline-sm" @click="showSupport = false">Cancel</button>
                        <button type="submit" class="btn-primary-sm" :disabled="supportForm.processing">Save</button>
                    </div>
                </form>
            </div>
        </Modal>

        <ConfirmationModal :show="!!deleting" :close="() => (deleting = null)" @confirm="destroy">
            <template #title>Delete {{ deleting?.kind }}</template>
            <template #message>Delete this {{ deleting?.kind }} and its photos? This can't be undone.</template>
        </ConfirmationModal>
    </div>
</template>

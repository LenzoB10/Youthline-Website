<script setup>
import { ref, computed } from 'vue';
import { Upload } from 'lucide-vue-next'
import { toast } from 'vue3-toastify';
import { useThemeStore } from '@/Stores/theme';
import ConfirmationModal from '@/Components/Common/Modal/ConfirmationModal.vue';

const theme = useThemeStore();

const props = defineProps({
    modelValue: {
        type: [String, Array],
        default: () => [],
    },
    accept: {
        type: String,
        default: '',
    },
    multiple: {
        type: Boolean,
        default: true,
    },
    maxFiles: {
        type: Number,
        default: 10,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    maxFileSize: {
        type: Number,
        default: 5 * 1024 * 1024, // 5MB in bytes
    },
    alreadyUploadedFiles: {
        type: Array,
        default: () => [],
    }
});

const emit = defineEmits(['update:modelValue', 'remove-already-uploaded-media']);

const dragActive = ref(false);
const inputRef = ref(null);
const showDeleteModal = ref(false);
const deleteId = ref(null);
function confirmDelete() {
    emit('remove-already-uploaded-media', deleteId.value);
    closeConfirmDeleteModal();
}
function openConfirmDeleteModal(id) {
    deleteId.value = id;
    showDeleteModal.value = true;
}

function closeConfirmDeleteModal() {
    showDeleteModal.value = false;
}

const files = computed(() => Array.isArray(props.modelValue) ? props.modelValue : []);

// Calculate total files (new + already uploaded)
const totalFiles = computed(() => files.value.length + props.alreadyUploadedFiles.length);

// Calculate remaining file slots
const remainingSlots = computed(() => Math.max(0, props.maxFiles - totalFiles.value));

function onFilesSelected(event) {
    let selectedFiles = Array.from(event.target.files);
    addFiles(selectedFiles);
    event.target.value = '';
}

function onDrop(event) {
    event.preventDefault();
    dragActive.value = false;
    if (props.disabled) return;
    let droppedFiles = Array.from(event.dataTransfer.files);
    addFiles(droppedFiles);
}

function onDragOver(event) {
    event.preventDefault();
    if (!props.disabled) dragActive.value = true;
}

function onDragLeave(event) {
    event.preventDefault();
    dragActive.value = false;
}

function isAcceptedFileType(file) {
    if (!props.accept) return true;
    const acceptedTypes = props.accept.split(',').map(type => type.trim());
    return acceptedTypes.some(type => {
        if (type.endsWith('/*')) {
            // Accept any file with the same major type (e.g., image/*)
            return file.type.startsWith(type.replace('/*', ''));
        }
        // Accept by exact MIME type or file extension
        return file.type === type || file.name.endsWith(type);
    });
}

function addFiles(newFiles) {
    let currentFiles = [...files.value];
    for (const file of newFiles) {
        if (file.size > props.maxFileSize) {
            toast.error(`File "${file.name}" exceeds the maximum size of ${Math.round(props.maxFileSize / (1024 * 1024))}MB.`, {
                theme: theme.darkMode === true ? 'dark' : 'light',
                transition: toast.TRANSITIONS.SLIDE,
                position: toast.POSITION.BOTTOM_RIGHT,
            });
            continue;
        }
        if (!isAcceptedFileType(file)) {
            toast.error(`File "${file.name}" has an invalid file type.`, {
                theme: theme.darkMode === true ? 'dark' : 'light',
                transition: toast.TRANSITIONS.SLIDE,
                position: toast.POSITION.BOTTOM_RIGHT,
            });
            continue;
        }
        // Check for duplicates in both new and already uploaded files
        const isDuplicate = currentFiles.some(f => f.name === file.name && f.size === file.size) ||
            props.alreadyUploadedFiles.some(f => f.name === file.name && f.size === file.size);
        if (isDuplicate) {
            toast.error(`File "${file.name}" is already in the list.`, {
                theme: theme.darkMode === true ? 'dark' : 'light',
                transition: toast.TRANSITIONS.SLIDE,
                position: toast.POSITION.BOTTOM_RIGHT,
            });
            continue;
        }
        // Check max files limit
        if ((props.alreadyUploadedFiles.length + currentFiles.length) >= props.maxFiles) {
            toast.error(`You can only upload up to ${props.maxFiles} files in total (including ${props.alreadyUploadedFiles.length} already uploaded).`, {
                theme: theme.darkMode === true ? 'dark' : 'light',
                transition: toast.TRANSITIONS.SLIDE,
                position: toast.POSITION.BOTTOM_RIGHT,
            });
            break;
        }
        currentFiles.push(file);
    }
    emit('update:modelValue', currentFiles);
}

function removeFile(index) {
    let currentFiles = [...files.value];
    currentFiles.splice(index, 1);
    emit('update:modelValue', currentFiles);
}

function openFileDialog() {
    if (!props.disabled && remainingSlots.value > 0) inputRef.value.click();
    else if (remainingSlots.value <= 0) {
        toast.error(`Maximum number of files (${props.maxFiles}) reached. Remove some files first.`, {
            theme: theme.darkMode === true ? 'dark' : 'light',
            transition: toast.TRANSITIONS.SLIDE,
            position: toast.POSITION.BOTTOM_RIGHT,
        });
    }
}
</script>

<template>
    <div class="mt-2">
        <!-- New Uploads Section -->
        <div>
            <div class="relative flex flex-col items-center justify-center border-2 border-dashed rounded-lg transition-colors duration-200 cursor-pointer bg-background-soft dark:bg-background-dark-soft"
                :class="{
                    'border-accent bg-accent-light/10 dark:border-accent-dark dark:bg-accent-dark/10': dragActive,
                    'border-mustBorder-light dark:border-mustBorder-dark': !dragActive,
                    'opacity-60 pointer-events-none': disabled
                }" @click="openFileDialog" @dragover="onDragOver" @dragleave="onDragLeave" @drop="onDrop">
                <input ref="inputRef" type="file" :accept="accept" :multiple="multiple" class="hidden"
                    :disabled="disabled" @change="onFilesSelected" />
                <div class="flex flex-col items-center py-8 px-4">
                    <Upload class="w-12 h-12 text-accent mb-2" />
                    <span class="text-text-muted dark:text-text-dark-muted text-sm">
                        Drag & drop {{ maxFiles > 1 ? 'files' : 'a file' }} here, or <span
                            class="text-accent underline">browse</span>
                    </span>
                    <span class="text-xs text-text-muted dark:text-text-dark-muted mt-1">
                        ({{ remainingSlots }} of {{ maxFiles }} slots available)
                    </span>
                    <span class="text-xs text-text-muted dark:text-text-dark-muted mt-1">
                        Accepts: {{ accept }}
                    </span>

                </div>
            </div>

            <!-- Already Uploaded Files Section -->
            <div v-if="alreadyUploadedFiles.length" class="mb-4">
                <h4 class="font-medium text-sm my-2 text-text-base dark:text-text-muted">Already Uploaded Files</h4>
                <div class="space-y-2">
                    <div v-for="file in alreadyUploadedFiles" :key="file.id"
                        class="flex items-center justify-between px-3 py-2 rounded bg-background-card dark:bg-background-dark-card border border-mustBorder-light dark:border-mustBorder-dark max-w-full overflow-x-hidden">
                        <div class="flex-1 min-w-0 break-words">
                            <a :href="file.url" target="_blank" class="font-medium text-accent underline block break-words">{{ file.name }}</a>
                            <span class="ml-2 text-xs text-text-muted dark:text-text-dark-muted">({{ (file.size /
                                1024).toFixed(1) }} KB)</span>
                        </div>
                        <button type="button" class="ml-3 text-red-500 hover:text-red-700 text-xs font-semibold"
                            @click.stop="openConfirmDeleteModal(file.id)" :disabled="disabled">
                            Remove
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="files.length" class="mt-3">
                <h4 class="font-medium text-sm mb-2 text-text-base dark:text-text-muted">New
                    Files</h4>
                <div class="space-y-2">
                    <div v-for="(file, idx) in files" :key="file.name + file.size"
                        class="flex items-center justify-between px-3 py-2 rounded bg-background-card dark:bg-background-dark-card border border-mustBorder-light dark:border-mustBorder-dark max-w-full overflow-x-hidden">
                        <div class="flex-1 min-w-0 break-words">
                            <span class="font-medium text-text-base dark:text-text-muted block break-words">{{ file.name }}</span>
                            <span class="ml-2 text-xs text-text-muted dark:text-text-dark-muted">({{ (file.size /
                                1024).toFixed(1) }} KB)</span>
                        </div>
                        <button type="button" class="ml-3 text-red-500 hover:text-red-700 text-xs font-semibold"
                            @click.stop="removeFile(idx)" :disabled="disabled">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Remove existing media  Modal -->
        <ConfirmationModal :show="showDeleteModal" :close="closeConfirmDeleteModal" @confirm="confirmDelete">
            <template #message>
                Are you sure you want to delete file? This will permanently remove it from our storage. This action cannot be undone.
            </template>
            <template #title>
                Remove File
            </template>
        </ConfirmationModal>
    </div>
</template>
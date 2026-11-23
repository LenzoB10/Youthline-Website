<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { ChevronDown, X, XCircle } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: [String, Number, Array],
        default: () => []
    },
    options: {
        type: Array,
        required: true, // Expects an array of objects like { value: 'val', label: 'Label' }
    },
    placeholder: {
        type: String,
        default: 'Select an option',
    },
    id: String,
    required: Boolean,
    disabled: Boolean,
    multiple: {
        type: Boolean,
        default: false,
    },
    searchable: {
        type: Boolean,
        default: true,
    },
    clearable: {
        type: Boolean,
        default: true,
    }
});

const emit = defineEmits(['update:modelValue']);


const isOpen = ref(false);
const searchTerm = ref('');
const selectElement = ref(null);
const searchInput = ref(null);
const dropdownMenu = ref(null);
const focusedOptionIndex = ref(-1);
let focusedOptionRef = null;

// Ensure modelValue is always an array when multiple is true
const internalValue = computed({
    get() {
        if (props.multiple) {
            return Array.isArray(props.modelValue) ? props.modelValue : [];
        }
        return props.modelValue;
    },
    set(value) {
        emit('update:modelValue', value);
    }
});

// Check if an option is selected
const isOptionSelected = (option) => {
    if (props.multiple) {
        return Array.isArray(internalValue.value) &&
               internalValue.value.includes(option.value);
    }
    return internalValue.value === option.value;
};

// Get selected options
const selectedOptions = computed(() => {
    if (props.multiple) {
        return props.options.filter(option => isOptionSelected(option));
    }
    const selected = props.options.find(option => option.value === internalValue.value);
    return selected ? [selected] : [];
});

// Computed property to get the display text
const displayText = computed(() => {
    if (props.multiple) {
        if (selectedOptions.value.length === 0) return props.placeholder;
        if (selectedOptions.value.length === 1) return selectedOptions.value[0].label;
        return `${selectedOptions.value.length} selected`;
    }
    const selected = props.options.find(option => option.value === internalValue.value);
    return selected ? selected.label : props.placeholder;
});

// Filter out selected options when in multiple mode
const filteredOptions = computed(() => {
    let result = [...props.options];

    // Apply search filter
    if (searchTerm.value) {
        const search = searchTerm.value.toLowerCase();
        result = result.filter(option =>
            option.label.toLowerCase().includes(search)
        );
    }

    // Filter out already selected options in multiple mode
    if (props.multiple && internalValue.value && internalValue.value.length) {
        result = result.filter(option =>
            !internalValue.value.includes(option.value)
        );
    }

    return result;
});

// Toggle dropdown visibility
const toggleDropdown = () => {
    if (props.disabled) return;

    isOpen.value = !isOpen.value;

    if (isOpen.value) {
        // Focus the search input when dropdown opens
        nextTick(() => {
            if (searchInput.value) {
                searchInput.value.focus();
            }

            // Position the dropdown
            positionDropdown();
        });
    } else {
        searchTerm.value = ''; // Clear search term when closing
    }
};

// Position the dropdown to avoid overflow
const positionDropdown = () => {
    if (!dropdownMenu.value || !selectElement.value) return;

    const selectRect = selectElement.value.getBoundingClientRect();
    const spaceBelow = window.innerHeight - selectRect.bottom;
    const spaceAbove = selectRect.top;
    const dropdownHeight = 200; // Approximate height

    // Default to opening below
    let top = selectRect.height;
    let maxHeight;

    // If there's not enough space below but more space above, open upwards
    if (spaceBelow < dropdownHeight && spaceAbove > spaceBelow) {
        top = 'auto';
        maxHeight = `${Math.min(spaceAbove - 20, 300)}px`;
        dropdownMenu.value.style.bottom = `${selectRect.height + 4}px`;
    } else {
        // Otherwise, ensure we don't go off the bottom of the screen
        maxHeight = `${Math.min(spaceBelow - 20, 300)}px`;
        dropdownMenu.value.style.bottom = 'auto';
    }

    dropdownMenu.value.style.top = top === 'auto' ? 'auto' : `${top + 4}px`;
    dropdownMenu.value.style.maxHeight = maxHeight;
};

// Select an option
const selectOption = (option) => {
    if (props.disabled) return;

    if (props.multiple) {
        const newValue = Array.isArray(internalValue.value) ? [...internalValue.value] : [];
        if (!newValue.includes(option.value)) {
            newValue.push(option.value);
            internalValue.value = newValue;
        }
        searchTerm.value = ''; // Clear search term after selection
        // Keep dropdown open for multiple selection
        nextTick(() => {
            if (searchInput.value) {
                searchInput.value.focus();
            }
        });
    } else {
        internalValue.value = option.value;
        isOpen.value = false;
        searchTerm.value = ''; // Clear search term after selection
    }
};

// Remove a selected option (for multiple mode)
const removeOption = (value, event) => {
    event.stopPropagation();
    if (props.disabled || !props.multiple) return;

    const newValue = Array.isArray(internalValue.value)
        ? internalValue.value.filter(v => v !== value)
        : [];
    internalValue.value = newValue;

    // Focus back to input after removing
    nextTick(() => {
        if (searchInput.value) {
            searchInput.value.focus();
        }
    });
};

// Clear all selected options
const clearSelection = (event) => {
    event.stopPropagation();
    if (props.disabled) return;

    if (props.multiple) {
        internalValue.value = [];
    } else {
        internalValue.value = null;
    }

    searchTerm.value = '';

    if (!props.multiple) {
        isOpen.value = false;
    } else {
        // Keep focus on input after clearing
        nextTick(() => {
            if (searchInput.value) {
                searchInput.value.focus();
            }
        });
    }
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (selectElement.value && !selectElement.value.contains(event.target)) {
        isOpen.value = false;
        searchTerm.value = ''; // Clear search term
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('resize', positionDropdown);
    window.addEventListener('scroll', positionDropdown, true);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('resize', positionDropdown);
    window.removeEventListener('scroll', positionDropdown, true);
});

// Watch for modelValue changes from outside to clear search term if needed
watch(() => props.modelValue, (newValue) => {
    if (!newValue || (Array.isArray(newValue) && newValue.length === 0)) {
        searchTerm.value = '';
    }
}, { deep: true });

// Watch for dropdown open/close to position it correctly
watch(isOpen, (newValue) => {
    if (newValue) {
        nextTick(() => {
            positionDropdown();
        });
    }
});

</script>

<template>
    <div class="relative w-full" ref="selectElement" :id="id ? `${id}-wrapper` : undefined">
        <!-- Select Button -->
        <div @click="toggleDropdown" :disabled="props.disabled"
            class="text-input flex w-full items-center justify-between text-left cursor-pointer"
            :class="{
                'border-accent dark:border-accent-dark ring-1 ring-accent dark:ring-accent-dark': isOpen,
                'opacity-50 cursor-not-allowed': props.disabled,
                '!py-1': props.multiple && selectedOptions.length > 0
            }"
            :aria-expanded="isOpen"
            :aria-haspopup="true"
            role="combobox"
            :aria-controls="id ? `${id}-dropdown` : undefined"
            :aria-labelledby="id ? `${id}-label` : undefined"
            tabindex="0"
            @keydown.space.prevent="toggleDropdown"
            @keydown.enter.prevent="toggleDropdown"
            @keydown.esc="isOpen = false">

            <!-- Selected Items (Multiple) -->
            <div v-if="props.multiple" class="flex-1 flex flex-wrap gap-1.5 overflow-hidden">
                <template v-if="selectedOptions.length > 0">
                    <div v-for="option in selectedOptions" :key="option.value"
                        class="inline-flex items-center bg-accent/10 text-accent dark:bg-accent-dark/20 dark:text-accent-dark text-xs px-2 py-1 rounded">
                        <span class="truncate max-w-[120px]">{{ option.label }}</span>
                        <XCircle class="ml-1.5 h-3.5 w-3.5 text-accent/70 hover:text-accent dark:text-accent-dark/70 dark:hover:text-accent-dark"
                            @click.stop="removeOption(option.value, $event)" />
                    </div>
                </template>
                
                <span v-else class="text-gray-500 dark:text-gray-400 text-sm py-0.5">
                    {{ placeholder }}
                </span>

                <!-- Search input for multiple select -->
                <input
                    v-if="props.multiple && isOpen"
                    ref="searchInput"
                    v-model="searchTerm"
                    class="text-input"
                    type="text"
                    @click.stop
                    @keydown.esc="isOpen = false"
                    @keydown.delete="handleBackspace"
                    :placeholder="selectedOptions.length === 0 ? 'Type to search...' : ''"
                />
            </div>

            <!-- Selected Item (Single) -->
            <span v-else class="flex-1 text-sm text-gray-900 dark:text-gray-100 truncate"
                :class="{ 'text-gray-500 dark:text-gray-400': !internalValue }">
                {{ displayText }}
            </span>

            <!-- Icons -->
            <div class="flex items-center ml-2">
                <X v-if="(internalValue !== null && internalValue !== '' && (Array.isArray(internalValue) ? internalValue.length > 0 : true)) && props.clearable && !props.disabled"
                    class="mr-1.5 h-4 w-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    @click.stop="clearSelection" />
                <ChevronDown class="h-4 w-4 text-gray-400 transition-transform duration-200"
                    :class="{ 'rotate-180': isOpen }" />
            </div>
        </div>

        <!-- Dropdown Panel -->
        <transition enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95">
            <div v-if="isOpen && !props.disabled"
                ref="dropdownMenu"
                :id="id ? `${id}-dropdown` : undefined"
                class="absolute z-50 w-full surface-raised rounded-xl"
                :class="{
                    'mt-1': !props.multiple,
                    'mt-0.5': props.multiple
                }"
                style="min-width: 100%;">

                <!-- Search Input (Single Select) -->
                <div v-if="searchable && !props.multiple" class="sticky top-0 z-10 bg-inherit p-2 border-b border-gray-100 dark:border-gray-700">
                    <input
                        ref="searchInput"
                        v-model="searchTerm"
                        type="text"
                        placeholder="Type to search..."
                        class="text-input w-full text-sm px-2.5 py-1.5"
                        @keydown.esc="isOpen = false"
                        @input="searchTerm = $event.target.value"
                        @keydown.down.prevent="focusNextOption(1)"
                        @keydown.up.prevent="focusNextOption(-1)"
                        @keydown.enter.prevent="selectedOptions"
                    >
                </div>

                <!-- Options List -->
                <div class="max-h-60 overflow-auto py-1 text-sm">
                    <div v-if="filteredOptions.length === 0"
                        class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ searchTerm ? 'No options found' : 'No options available' }}
                    </div>

                    <div v-else>
                        <div v-for="(option, index) in filteredOptions"
                            :key="option.value"
                            @click="selectOption(option)"
                            :ref="el => { if (index === focusedOptionIndex) focusedOptionRef = el }"
                            :class="[
                                'relative cursor-pointer select-none dark:text-text-muted px-4 py-2 hover:bg-accent/10',
                                {
                                    'bg-accent/10 text-accent dark:text-text-inverted': isOptionSelected(option),
                                    'bg-gray-100 dark:bg-background-dark-card text-text-muted dark:text-text-muted-dark': index === focusedOptionIndex
                                }
                            ]"
                            role="option"
                            :aria-selected="isOptionSelected(option)"
                            @mouseenter="focusedOptionIndex = index"
                            @keydown.enter.prevent="selectOption(option)">
                            <div class="flex items-center">
                                <span v-if="props.multiple" class="mr-2">
                                    <input
                                        type="checkbox"
                                        :checked="isOptionSelected(option)"
                                        class="rounded border-gray-300 text-accent focus:ring-accent dark:border-gray-600 dark:bg-transparent dark:ring-offset-gray-900 dark:focus:ring-accent-dark"
                                        @click.stop
                                        @change="selectOption(option)">
                                </span>
                                <span class="block truncate">
                                    {{ option.label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected Count (Multiple) -->
                <div v-if="props.multiple && selectedOptions.length > 0"
                    class="sticky bottom-0 bg-gray-50 dark:bg-background-dark-card border-t border-gray-100 dark:border-gray-700 px-3 py-1.5 text-xs text-gray-500 dark:text-gray-400">
                    {{ selectedOptions.length }}
                    {{
                        selectedOptions.length === 1 ? "item" : "items"
                    }}
                    selected
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
/* Custom scrollbar for dropdown */
.scrollbar-custom::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.scrollbar-custom::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.scrollbar-custom::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.scrollbar-custom::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Dark mode scrollbar */
.dark .scrollbar-custom::-webkit-scrollbar-track {
    background: #2d3748;
}

.dark .scrollbar-custom::-webkit-scrollbar-thumb {
    background: #4a5568;
}

.dark .scrollbar-custom::-webkit-scrollbar-thumb:hover {
    background: #718096;
}
</style>

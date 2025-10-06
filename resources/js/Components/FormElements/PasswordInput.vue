<!-- resources/js/Components/FormElements/PasswordInput.vue -->
<script setup>
import { ref, computed } from 'vue';
import TextInput from './TextInput.vue';
import { Eye, EyeOff } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    showToggle: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue']);
const showPassword = ref(false);

const inputType = computed(() => (showPassword.value ? 'text' : 'password'));
const toggleIcon = computed(() => (showPassword.value ? EyeOff : Eye));

const togglePassword = () => {
    showPassword.value = !showPassword.value;
};
</script>

<template>
    <div class="relative">
        <TextInput
            :type="inputType"
            :model-value="modelValue"
            @update:model-value="(value) => $emit('update:modelValue', value)"
            v-bind="$attrs"
        />
        <button
            v-if="showToggle"
            type="button"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            @click="togglePassword"
            tabindex="-1"
        >
            <component :is="toggleIcon" class="h-5 w-5" />
        </button>
    </div>
</template>

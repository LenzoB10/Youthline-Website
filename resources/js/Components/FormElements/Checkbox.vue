<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:modelValue']);

const props = defineProps({
    modelValue: {
        type: [Array, Boolean],
        required: false,
    },
    value: {
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const proxyChecked = computed({
    get() {
        // If modelValue is an array, return whether value is in the array
        if (Array.isArray(props.modelValue)) {
            return props.modelValue.includes(props.value);
        }
        return props.modelValue;
    },

    set(val) {
        if (Array.isArray(props.modelValue)) {
            const newValue = [...props.modelValue];
            if (val) {
                newValue.push(props.value);
            } else {
                newValue.splice(newValue.indexOf(props.value), 1);
            }
            emit('update:modelValue', newValue);
        } else {
            emit('update:modelValue', val);
        }
    },
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        :disabled="disabled"
        class="checkbox-input"
    />
</template>

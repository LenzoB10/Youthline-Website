<!-- example usage -->
<!-- <SelectInput
    v-model="form.role_id"
    :options="roles"
    placeholder="Select a role"
/> -->

<template>
    <div>
        <select
            ref="select"
            class="text-input mt-1 block w-full"
            :value="modelValue"
            @change="handleChange"
            :disabled="disabled"
            :required="required"
            v-bind="$attrs"
        >
            <option value="" disabled selected>{{ placeholder }}</option>
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
                :disabled="option.disabled"
            >
                {{ option.label }}
            </option>
        </select>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    options: {
        type: Array,
        required: true,
        validator: (value) => {
            return value.every(
                (option) => 'value' in option && 'label' in option,
            );
        },
    },
    placeholder: {
        type: String,
        default: 'Select an option',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const select = ref(null);

onMounted(() => {
    if (select.value.hasAttribute('autofocus')) {
        select.value.focus();
    }
});

const handleChange = (event) => {
    emit('update:modelValue', event.target.value);
};

defineExpose({ focus: () => select.value?.focus() });
</script>

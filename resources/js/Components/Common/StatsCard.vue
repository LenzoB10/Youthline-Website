<template>
  <div class="card-container p-4 flex flex-col gap-2 items-start">
    <div class="flex items-center justify-between w-full">
      <h3 class="text-sm font-medium text-text-muted dark:text-text-dark-muted">{{ label }}</h3>
      <component :is="icon" class="w-5 h-5" :class="iconClass" />
    </div>
    <p class="text-2xl font-semibold text-text-base dark:text-text-inverted">{{ value }}</p>
    <p v-if="subtitle" class="text-xs text-text-muted dark:text-text-dark-muted">{{ subtitle }}</p>
    <slot />
  </div>
</template>

<script setup>
defineProps({
  label: String,
  value: [String, Number],
  subtitle: String,
  icon: {
    type: [Object, Function, String],
    required: true,
  },
  iconClass: {
    type: String,
    default: '',
  },
});
</script> 
<template>
    <div class="flex flex-wrap gap-2">
        <div v-for="permission in permissions" :key="permission.id">
            <div class="rounded-display">
                {{ permission.name }}
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    permissions: Array,
});
</script>

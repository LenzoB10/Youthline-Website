<template>
    <div
        v-if="$page.props.flash.error"
        class="mb-4 rounded-md bg-red-50 p-4 dark:bg-red-900/20"
    >
        <div class="flex">
            <div class="flex-shrink-0">
                <XCircleIcon
                    class="h-5 w-5 text-red-400 dark:text-red-300"
                    aria-hidden="true"
                />
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                    {{ $page.props.flash.error }}
                </h3>
                <div
                    v-if="$page.props.flash.error_details"
                    class="mt-2 text-sm text-red-700 dark:text-red-300"
                >
                    <p>{{ $page.props.flash.error_details }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { XCircleIcon } from 'lucide-vue-next';
</script>

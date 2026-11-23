<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { HelpCircle, LogOut, Settings, User } from 'lucide-vue-next';
import { computed } from 'vue';

// The signed-in user's menu, opened from the sidebar user card. Styled like the sidebar.
const user = computed(() => usePage().props.auth?.user ?? {});
const initials = computed(() => (user.value.name ?? '?').split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase());

const item =
    'group flex w-full items-center gap-3 rounded-lg px-3 py-2 text-[14px] font-medium text-gray-600 transition hover:bg-gray-100 hover:text-mustBlue dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white';
const icon = 'h-[18px] w-[18px] text-gray-400 transition group-hover:text-mustGreen';
</script>

<template>
    <div class="surface-raised w-full overflow-hidden rounded-[10px] p-1.5" role="menu">
        <div class="mb-1 flex items-center gap-3 rounded-lg px-2.5 py-2.5">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-mustGreen to-yl-green-deep text-xs font-bold text-white">
                {{ initials }}
            </span>
            <span class="min-w-0 leading-tight">
                <span class="block truncate text-sm font-semibold text-mustBlue dark:text-text-inverted">{{ user.name }}</span>
                <span class="block truncate text-[11px] text-text-muted dark:text-text-dark-muted">{{ user.email }}</span>
            </span>
        </div>

        <div class="mx-1.5 mb-1 h-px bg-gray-200 dark:bg-white/10"></div>

        <Link :href="route('profile.edit')" :class="item" role="menuitem"><User :class="icon" /> Profile</Link>
        <a href="#" :class="item" role="menuitem"><Settings :class="icon" /> Settings</a>
        <a href="#" :class="item" role="menuitem"><HelpCircle :class="icon" /> Help</a>

        <div class="mx-1.5 my-1 h-px bg-gray-200 dark:bg-white/10"></div>

        <Link
            :href="route('logout')"
            method="post"
            as="button"
            role="menuitem"
            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-[14px] font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
        >
            <LogOut class="h-[18px] w-[18px]" /> Log out
        </Link>
    </div>
</template>

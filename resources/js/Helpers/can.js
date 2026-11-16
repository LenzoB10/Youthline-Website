import { usePage } from '@inertiajs/vue3';

/**
 * Permission check for script logic (the v-can directive covers templates).
 * The server always re-checks — this only decides what to show.
 */
export function can(permission) {
    return (usePage().props.auth?.permissions ?? []).includes(permission);
}

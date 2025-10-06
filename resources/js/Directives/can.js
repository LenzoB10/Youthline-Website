// a custom directive to check if a user has a permission to perform an action
// if the user does not have the permission, the element is removed
// usage: v-can="permission_name"
// example: <button v-can="update_role">Update Role</button>

import { usePage } from '@inertiajs/vue3';

export default {
    mounted(el, binding) {
        // Access permissions from Inertia page props
        const permissions = usePage().props.auth.permissions || [];
        const requiredPermission = binding.value;

        if (!permissions.includes(requiredPermission)) {
            el.remove();
        }
    },
};

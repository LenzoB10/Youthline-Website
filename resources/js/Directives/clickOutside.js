export default {
    beforeMount(el, binding) {
        el._clickOutsideHandler = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value(event);
            }
        };
        setTimeout(() => {
            document.addEventListener('click', el._clickOutsideHandler);
        }, 10);
    },
    unmounted(el) {
        if (el._clickOutsideHandler) {
            document.removeEventListener('click', el._clickOutsideHandler);
            delete el._clickOutsideHandler;
        }
    },
};

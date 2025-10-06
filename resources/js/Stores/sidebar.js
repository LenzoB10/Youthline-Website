import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useSidebarStore = defineStore('sidebar', () => {
    const isOpen = ref(true);
    const isMobile = ref(false);
    const isMobileSidebarOpen = ref(false);

    const toggle = () => {
        if (isMobile.value) {
            isMobileSidebarOpen.value = !isMobileSidebarOpen.value;
            document.body.style.overflow = isMobileSidebarOpen.value
                ? 'hidden'
                : '';
        } else {
            isOpen.value = !isOpen.value;
        }
    };

    const setMobile = (value) => {
        isMobile.value = value;
        if (!value) {
            isMobileSidebarOpen.value = false;
            document.body.style.overflow = '';
        }
    };

    const closeMobile = () => {
        if (isMobile.value) {
            isMobileSidebarOpen.value = false;
            document.body.style.overflow = '';
        }
    };

    return {
        isOpen,
        isMobile,
        isMobileSidebarOpen,
        toggle,
        setMobile,
        closeMobile,
    };
});

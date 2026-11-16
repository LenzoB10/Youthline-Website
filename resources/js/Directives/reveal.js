/**
 * v-reveal: fades/slides an element in the first time it scrolls into view.
 *
 *   <div v-reveal>                    fade up
 *   <div v-reveal:left>               slide in from the left (also :right, :zoom)
 *   <div v-reveal="200">              wait 200ms before animating
 *   <div v-reveal.stagger>            animate each child in turn (cards in a grid)
 *   <div v-reveal.stagger="120">      ...with 120ms between children
 *
 * Visitors who ask their device for reduced motion see everything at once.
 * Styles live in app.css (.reveal / .is-visible).
 */

const STAGGER_DEFAULT = 100;
const MAX_STAGGER = 600; // long lists shouldn't keep people waiting

let observer = null;

const reducedMotion = () => window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

const getObserver = () => {
    if (!observer) {
        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
        );
    }

    return observer;
};

const prepare = (el, direction, delay) => {
    el.classList.add('reveal');
    if (direction) {
        el.classList.add(`reveal-${direction}`);
    }
    if (delay) {
        el.style.setProperty('--reveal-delay', `${delay}ms`);
    }
};

const watch = (el) => {
    if (reducedMotion() || !('IntersectionObserver' in window)) {
        el.classList.add('is-visible');
        return;
    }
    getObserver().observe(el);
};

export default {
    mounted(el, binding) {
        const direction = binding.arg;

        if (binding.modifiers.stagger) {
            const step = Number(binding.value) || STAGGER_DEFAULT;
            Array.from(el.children).forEach((child, index) => {
                prepare(child, direction, Math.min(index * step, MAX_STAGGER));
                watch(child);
            });
            return;
        }

        prepare(el, direction, Number(binding.value) || 0);
        watch(el);
    },
    unmounted(el) {
        if (!observer) {
            return;
        }
        observer.unobserve(el);
        Array.from(el.children).forEach((child) => observer.unobserve(child));
    },
};

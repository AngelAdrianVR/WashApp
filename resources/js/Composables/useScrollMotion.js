import { onMounted, onUnmounted } from 'vue';

const clamp = (value, min = 0, max = 1) => Math.min(max, Math.max(min, value));

/**
 * Movimiento ligado al scroll.
 *
 * Escribe dos variables CSS en los elementos marcados:
 * - [data-motion]          → --p        (0 fuera de foco → 1 en foco)
 * - [data-motion-parallax] → --parallax (desplazamiento en px)
 *
 * `data-motion-delay` escalona la entrada de tarjetas vecinas (0 → 0.6).
 */
export function useScrollMotion() {
    let frame = null;
    let reducedMotion = false;

    const update = () => {
        frame = null;

        const viewportHeight = window.innerHeight || 1;
        const viewportCenter = viewportHeight / 2;

        document.querySelectorAll('[data-motion]').forEach((el) => {
            if (reducedMotion) {
                el.style.setProperty('--p', '1');
                return;
            }

            const rect = el.getBoundingClientRect();
            const raw = clamp((viewportHeight * 0.94 - rect.top) / (viewportHeight * 0.62));
            const delay = clamp(parseFloat(el.dataset.motionDelay || '0'), 0, 0.6);
            const progress = clamp((raw - delay) / (1 - delay || 1));

            el.style.setProperty('--p', progress.toFixed(4));
        });

        document.querySelectorAll('[data-motion-parallax]').forEach((el) => {
            if (reducedMotion) {
                el.style.setProperty('--parallax', '0px');
                return;
            }

            const rect = el.getBoundingClientRect();
            const factor = parseFloat(el.dataset.motionParallax) || 0;
            const offset = (rect.top + rect.height / 2 - viewportCenter) * factor;

            el.style.setProperty('--parallax', `${offset.toFixed(2)}px`);
        });
    };

    const requestUpdate = () => {
        if (frame) return;
        frame = requestAnimationFrame(update);
    };

    onMounted(() => {
        reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Se ejecuta antes del primer paint para evitar saltos en la carga.
        update();

        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate);
        window.addEventListener('load', requestUpdate);
    });

    onUnmounted(() => {
        window.removeEventListener('scroll', requestUpdate);
        window.removeEventListener('resize', requestUpdate);
        window.removeEventListener('load', requestUpdate);

        if (frame) cancelAnimationFrame(frame);
    });

    return { refresh: requestUpdate };
}

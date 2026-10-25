// Microinteracciones de las páginas públicas: inclinación 3D con foco, botones magnéticos y logotipo del pie.

// Tarjetas y fotogramas: se inclinan hacia el cursor y un foco de luz lo sigue (variables CSS, sin layout).
export function iniciarInclinacion() {
    if (!window.matchMedia('(pointer: fine)').matches) return;
    document.querySelectorAll('[data-inclinar]').forEach((tarjeta) => {
        let marco = 0;
        tarjeta.addEventListener('pointermove', (e) => {
            if (e.pointerType !== 'mouse') return;
            cancelAnimationFrame(marco);
            marco = requestAnimationFrame(() => {
                const r = tarjeta.getBoundingClientRect();
                const px = (e.clientX - r.left) / r.width;
                const py = (e.clientY - r.top) / r.height;
                tarjeta.classList.add('is-mueve');
                tarjeta.style.setProperty('--ry', `${((px - 0.5) * 18).toFixed(2)}deg`);
                tarjeta.style.setProperty('--rx', `${((0.5 - py) * 14).toFixed(2)}deg`);
                tarjeta.style.setProperty('--mx', `${(px * 100).toFixed(1)}%`);
                tarjeta.style.setProperty('--my', `${(py * 100).toFixed(1)}%`);
            });
        });
        tarjeta.addEventListener('pointerleave', () => {
            cancelAnimationFrame(marco);
            tarjeta.classList.remove('is-mueve');
            tarjeta.style.setProperty('--rx', '0deg');
            tarjeta.style.setProperty('--ry', '0deg');
        });
    });
}

// Botones magnéticos: el botón principal se acerca un poco al cursor y vuelve con un muelle.
export function iniciarMagneticos(gsap) {
    if (!window.matchMedia('(pointer: fine)').matches) return;
    const botones = document.querySelectorAll('[data-magnetico], .btn-primary:not([type="submit"])');
    botones.forEach((boton) => {
        const x = gsap.quickTo(boton, 'x', { duration: 0.6, ease: 'elastic.out(1, 0.55)' });
        const y = gsap.quickTo(boton, 'y', { duration: 0.6, ease: 'elastic.out(1, 0.55)' });
        boton.addEventListener('pointermove', (e) => {
            if (e.pointerType !== 'mouse') return;
            const r = boton.getBoundingClientRect();
            x((e.clientX - (r.left + r.width / 2)) * 0.3);
            y((e.clientY - (r.top + r.height / 2)) * 0.4);
        });
        boton.addEventListener('pointerleave', () => { x(0); y(0); });
    });
}

// El logotipo a todo el ancho sube y se enciende al llegar al pie.
export function iniciarPieLogo(gsap, ScrollTrigger) {
    const logo = document.querySelector('[data-pie-logo] svg');
    if (!logo) return;
    gsap.from(logo, {
        yPercent: 28,
        opacity: 0,
        duration: 1.1,
        ease: 'expo.out',
        scrollTrigger: { trigger: '[data-pie-logo]', start: 'top 92%', once: true },
    });
}

// Haz de proyector: la imagen del haz entra con un fundido y barre la escena girando sobre su foco.
// Mismo barrido que tenía el degradado CSS (11 s, ida y vuelta). Se pausa cuando su contenedor sale de pantalla.
// Con prefers-reduced-motion este archivo ni se carga: el haz queda quieto, como lo deja el CSS.

export function iniciarHaz(gsap, ScrollTrigger) {
    // Móvil y táctil: el haz solo se funde al entrar y se queda quieto. Girar una imagen enorme con máscara y mezcla
    // «screen» a 60 fotogramas por segundo es lo que más tiraba en el móvil.
    const quieto = window.matchMedia('(max-width: 1023px), (pointer: coarse)').matches;
    document.querySelectorAll('[data-haz]').forEach((img) => {
        const apagado = parseFloat(getComputedStyle(img).opacity) || 0.8;
        gsap.set(img, { opacity: 0, rotation: quieto ? -2 : -6 });
        gsap.to(img, { opacity: apagado, duration: quieto ? 1 : 1.6, delay: quieto ? 0.5 : 0.9, ease: 'power2.out' });
        if (quieto) return;
        const barrido = gsap.to(img, { rotation: 7, duration: 11, ease: 'sine.inOut', yoyo: true, repeat: -1 });
        ScrollTrigger.create({
            trigger: img.parentElement?.closest('.hero-haz, .ficha-fondo') ?? img,
            start: 'top bottom',
            end: 'bottom top',
            onToggle: (self) => (self.isActive ? barrido.play() : barrido.pause()),
        });
    });
}

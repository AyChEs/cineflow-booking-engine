// Haz de proyector: la imagen del haz entra con un fundido y barre la escena girando sobre su foco.
// Mismo barrido que tenía el degradado CSS (11 s, ida y vuelta). Se pausa cuando su contenedor sale de pantalla.
// Con prefers-reduced-motion este archivo ni se carga: el haz queda quieto, como lo deja el CSS.

export function iniciarHaz(gsap, ScrollTrigger) {
    document.querySelectorAll('[data-haz]').forEach((img) => {
        const apagado = parseFloat(getComputedStyle(img).opacity) || 0.8;
        gsap.set(img, { opacity: 0, rotation: -6 });
        gsap.to(img, { opacity: apagado, duration: 1.6, delay: 0.9, ease: 'power2.out' });
        const barrido = gsap.to(img, { rotation: 7, duration: 11, ease: 'sine.inOut', yoyo: true, repeat: -1 });
        ScrollTrigger.create({
            trigger: img.parentElement?.closest('.hero-haz, .ficha-fondo') ?? img,
            start: 'top bottom',
            end: 'bottom top',
            onToggle: (self) => (self.isActive ? barrido.play() : barrido.pause()),
        });
    });
}

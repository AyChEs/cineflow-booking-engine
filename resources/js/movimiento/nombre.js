// El nombre a todo el ancho: el contorno se rellena con el scroll (la ventana y el relleno se mueven en
// sentidos contrarios, así que solo hay transform) y los pósteres lo cruzan a distintas velocidades.

export function iniciarNombre({ gsap, ScrollTrigger }) {
    const seccion = document.querySelector('[data-nombre]');
    if (!seccion) return;
    const ventana = seccion.querySelector('[data-nombre-ventana]');
    const relleno = seccion.querySelector('[data-nombre-relleno]');
    const lienzo = seccion.querySelector('.nombre-lienzo');

    gsap.set(ventana, { xPercent: -100 });
    gsap.set(relleno, { xPercent: 100 });
    gsap.timeline({
        defaults: { ease: 'none' },
        scrollTrigger: { trigger: lienzo, start: 'top 88%', end: 'bottom 38%', scrub: 0.8 },
    })
        .to(ventana, { xPercent: 0 }, 0)
        .to(relleno, { xPercent: 0 }, 0);

    const velocidad = { 1: [160, -140], 2: [260, -260], 3: [120, -200] };
    seccion.querySelectorAll('[data-cruce]').forEach((cartel) => {
        const [desde, hasta] = velocidad[cartel.dataset.cruce] ?? [150, -150];
        gsap.fromTo(cartel, { y: desde }, {
            y: hasta,
            ease: 'none',
            scrollTrigger: { trigger: seccion, start: 'top bottom', end: 'bottom top', scrub: 0.8 },
        });
    });
}

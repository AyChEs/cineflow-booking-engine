// Menú móvil: botón con aria-expanded, panel inerte cerrado, Esc cierra y devuelve el foco.
export function iniciarMenu() {
    const boton = document.getElementById('menu-toggle');
    const panel = document.getElementById('menu-movil');
    if (!boton || !panel) return;

    const abrir = () => {
        panel.hidden = false;
        panel.inert = false;
        boton.setAttribute('aria-expanded', 'true');
        boton.setAttribute('aria-label', 'Cerrar menú');
        document.body.style.overflow = 'hidden';
        panel.querySelector('a, button')?.focus();
    };

    const cerrar = (devolverFoco = true) => {
        panel.hidden = true;
        panel.inert = true;
        boton.setAttribute('aria-expanded', 'false');
        boton.setAttribute('aria-label', 'Abrir menú');
        document.body.style.overflow = '';
        if (devolverFoco) boton.focus();
    };

    boton.addEventListener('click', () => (panel.hidden ? abrir() : cerrar()));

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !panel.hidden) cerrar();
    });

    // Si la ventana crece hasta escritorio con el menú abierto, se cierra sin mover el foco.
    window.matchMedia('(min-width: 768px)').addEventListener('change', (e) => {
        if (e.matches && !panel.hidden) cerrar(false);
    });
}

// Apariciones al hacer scroll (.reveal, .reveal-stagger, .section-title-accent). Sin movimiento si se pide.
export function iniciarApariciones() {
    const elementos = document.querySelectorAll('.reveal:not(.is-visible), .reveal-stagger:not(.is-visible), .section-title-accent:not(.is-visible)');
    const reducir = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reducir || !('IntersectionObserver' in window)) {
        elementos.forEach((el) => el.classList.add('is-visible'));
        return;
    }
    const io = new IntersectionObserver((entradas) => {
        for (const e of entradas) {
            if (e.isIntersecting) {
                e.target.classList.add('is-visible');
                io.unobserve(e.target);
            }
        }
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    elementos.forEach((el) => io.observe(el));
}

// Tras un error de validación, el foco va al primer campo con error.
export function enfocarPrimerError() {
    const campo = document.querySelector('[aria-invalid="true"]');
    if (campo) campo.focus();
}

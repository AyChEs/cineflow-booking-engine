@extends('layout')
@section('title', 'Privacidad')
@section('description', 'Política de privacidad de '.config('app.name').': qué datos se guardan, para qué, cuánto tiempo y cómo ejercer tus derechos.')
@section('content')
<article class="legal mx-auto max-w-3xl px-4 py-pagina sm:px-6" aria-labelledby="legal-titulo">
    <h1 id="legal-titulo" class="display text-5xl text-texto md:text-7xl">Privacidad</h1>
    <p class="legal-fecha">Última actualización: 25 de octubre de 2026.</p>

    <p class="legal-aviso"><strong>Recomendación:</strong> esto es una demo. No uses datos reales. Escribe un nombre inventado, un correo que no te importe perder y, para pagar, la tarjeta de prueba <span class="cifras">4242 4242 4242 4242</span>.</p>

    <section aria-labelledby="p-responsable">
        <h2 id="p-responsable">Responsable</h2>
        <p>Ayman Charoui Essamadi, que mantiene y despliega {{ config('app.name') }}, un proyecto final de ciclo de demostración hecho junto a Ismael Achamrouk y Danna Guevara (consulta el <a href="{{ route('legal.aviso') }}">aviso legal</a>). Contacto para cualquier asunto de privacidad: <a href="mailto:aymanessamadi72@gmail.com">aymanessamadi72@gmail.com</a>.</p>
    </section>

    <section aria-labelledby="p-finalidad">
        <h2 id="p-finalidad">Para qué se usan tus datos</h2>
        <p>Solo para probar la demo: crear tu cuenta, simular la compra de entradas, guardar tus reservas, enseñarte tus entradas con su código QR y, si el envío de correo está activado, mandártelas por correo. No se usan para publicidad ni para hacer perfiles, y no se venden ni se ceden a nadie salvo a los proveedores que se indican más abajo.</p>
    </section>

    <section aria-labelledby="p-datos">
        <h2 id="p-datos">Qué datos se guardan</h2>
        <ul>
            <li><strong>Cuenta:</strong> nombre, apellidos, correo electrónico, teléfono (si lo escribes) y contraseña. La contraseña se guarda cifrada con un hash; nadie puede leerla.</li>
            <li><strong>Compras:</strong> la sesión, las butacas, el tipo de entrada, el importe, el nombre y el correo de quien compra, y el código QR firmado de la entrada. Se pueden hacer sin cuenta.</li>
            <li><strong>Pago:</strong> la web no guarda números de tarjeta. Con el pago simulado, si decides guardarla, solo se conservan las cuatro últimas cifras. Con Stripe en modo de pruebas, los datos de la tarjeta de prueba los recibe Stripe y no llegan a esta web.</li>
            <li><strong>Datos técnicos:</strong> la dirección IP y el navegador, que quedan en la sesión y en el registro de intentos excesivos (límite de peticiones), y las butacas que tienes retenidas mientras compras.</li>
        </ul>
    </section>

    <section aria-labelledby="p-base">
        <h2 id="p-base">Base legal</h2>
        <p>Tu consentimiento (artículo 6.1.a del RGPD), que das al marcar «He leído la política de privacidad» al crear la cuenta o al enviar tus datos en un formulario. Puedes retirarlo en cualquier momento borrando tu cuenta o escribiendo al correo de contacto; eso no afecta a lo ya tratado. Esta demo no está pensada para menores de 14 años.</p>
    </section>

    <section aria-labelledby="p-conservacion">
        <h2 id="p-conservacion">Cuánto tiempo se conservan</h2>
        <ul>
            <li>Los datos de la cuenta y de tus reservas, hasta que borres la cuenta (en tu perfil) o la demo se retire. La base de datos de la demo puede reiniciarse al desplegar una versión nueva, y entonces se pierde todo.</li>
            <li>La sesión caduca a las 2 horas sin actividad. Las butacas retenidas se liberan a los 8 minutos.</li>
        </ul>
    </section>

    <section aria-labelledby="p-encargados">
        <h2 id="p-encargados">Quién más interviene</h2>
        <ul>
            <li><strong>Render:</strong> aloja la web y su base de datos (SQLite en el propio servicio; MySQL si se configurara).</li>
            <li><strong>Stripe, en modo de pruebas:</strong> procesa el pago de prueba. No se cobra nada.</li>
            <li><strong>Correo:</strong> si el envío está activado, el proveedor de correo que se configure (por ejemplo, Brevo) recibe tu dirección y la entrada para entregarla.</li>
            <li><strong>TMDB:</strong> no recibe tus datos de cuenta, pero tu navegador pide los pósteres directamente a <span class="cifras">image.tmdb.org</span>, que ve tu dirección IP.</li>
        </ul>
    </section>

    <section aria-labelledby="p-transferencias">
        <h2 id="p-transferencias">Transferencias internacionales</h2>
        <p>Render y Stripe son empresas con sede en Estados Unidos y pueden tratar datos fuera del Espacio Económico Europeo. Según sus condiciones, lo hacen con cláusulas contractuales tipo de la Comisión Europea. Consulta sus políticas de privacidad para el detalle.</p>
    </section>

    <section aria-labelledby="p-derechos">
        <h2 id="p-derechos">Tus derechos</h2>
        <p>Puedes pedir acceso, rectificación, supresión, oposición, limitación del tratamiento y portabilidad de tus datos, y no ser objeto de decisiones automatizadas (aquí no hay ninguna). Escribe a <a href="mailto:aymanessamadi72@gmail.com">aymanessamadi72@gmail.com</a> indicando el correo de tu cuenta; se responde en el plazo máximo de un mes.</p>
        <p>Si crees que tus datos no se tratan bien, puedes reclamar ante la Agencia Española de Protección de Datos (<a href="https://www.aepd.es">www.aepd.es</a>).</p>
    </section>

    <section aria-labelledby="p-cookies">
        <h2 id="p-cookies">Cookies</h2>
        <p>Solo hay dos cookies técnicas, necesarias para que la web funcione. Por eso no hay aviso de cookies: no necesitan tu consentimiento (artículo 22.2 de la LSSI-CE). No hay cookies de analítica ni de publicidad.</p>
        <dl class="legal-datos">
            <div><dt><span class="cifras">cineflow-session</span></dt><dd>Mantiene tu sesión y tu compra en curso. Dura 2 horas, no se puede leer desde JavaScript (HttpOnly), solo viaja por HTTPS (Secure) y no se envía desde otras webs (SameSite=Lax).</dd></div>
            <div><dt><span class="cifras">XSRF-TOKEN</span></dt><dd>Protege los formularios contra envíos falsos desde otras webs. Dura 2 horas, solo viaja por HTTPS (Secure) y es SameSite=Lax.</dd></div>
        </dl>
    </section>
</article>
@endsection

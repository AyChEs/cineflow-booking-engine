@extends('layout')
@section('title', 'Aviso legal')
@section('description', 'Aviso legal de '.config('app.name').': proyecto final de ciclo de demostración, sin actividad comercial.')
@section('content')
<article class="legal mx-auto max-w-3xl px-4 py-pagina sm:px-6" aria-labelledby="legal-titulo">
    <h1 id="legal-titulo" class="display text-5xl text-texto md:text-7xl">Aviso legal</h1>
    <p class="legal-fecha">Última actualización: 25 de octubre de 2026.</p>

    <section aria-labelledby="l-titular">
        <h2 id="l-titular">Quién está detrás</h2>
        <p>{{ config('app.name') }} es un proyecto final de ciclo de demostración, hecho por Ayman Charoui Essamadi, Ismael Achamrouk y Danna Guevara. Ayman lo retomó después por su cuenta y es quien lo mantiene y lo despliega.</p>
        <p>No tiene actividad comercial ni ánimo de lucro. No es un cine real y no vende nada.</p>
        <dl class="legal-datos">
            <div><dt>Contacto</dt><dd><a href="mailto:aymanessamadi72@gmail.com">aymanessamadi72@gmail.com</a></dd></div>
            <div><dt>Código</dt><dd><a href="https://github.com/AyChEs/cineflow-booking-engine">github.com/AyChEs/cineflow-booking-engine</a></dd></div>
        </dl>
        <p>La Ley 34/2002 (LSSI-CE, artículo 10) pide publicar el NIF y el domicilio a quien presta servicios con actividad económica. Este proyecto no la tiene, así que no se publican. Si necesitas algún dato de identificación, escribe al correo de contacto.</p>
    </section>

    <section aria-labelledby="l-ficticio">
        <h2 id="l-ficticio">Todo es ficticio</h2>
        <ul>
            <li>Los cines, las salas, las sesiones, los precios y las reservas son ficticios. Las entradas no valen en ningún cine real.</li>
            <li>Los pagos son de prueba. Con Stripe en modo de pruebas no se cobra nada, y el pago simulado tampoco. Para probar, usa la tarjeta <span class="cifras">4242 4242 4242 4242</span>, cualquier fecha futura y cualquier CVC.</li>
            <li>La demo puede cambiar, reiniciarse o dejar de funcionar sin aviso. Se ofrece tal cual, sin garantías de ningún tipo.</li>
        </ul>
    </section>

    <section aria-labelledby="l-contenidos">
        <h2 id="l-contenidos">Contenidos y propiedad intelectual</h2>
        <ul>
            <li>La cartelera usa títulos y pósteres de películas reales que facilita TMDB. Los derechos de esas obras y de sus imágenes son de sus titulares. Este producto usa la API de TMDB, pero no está avalado ni certificado por TMDB.</li>
            <li>El código está publicado en GitHub con licencia MIT.</li>
            <li>La marca {{ config('app.name') }} y sus imágenes son de demostración. Las imágenes de marca están generadas con IA por Ayman Charoui.</li>
            <li>Las tipografías (Big Shoulders Display y Archivo) tienen licencia libre (OFL) y se sirven desde esta misma web.</li>
        </ul>
    </section>

    <section aria-labelledby="l-enlaces">
        <h2 id="l-enlaces">Enlaces y servicios de terceros</h2>
        <p>La web enlaza con GitHub y con YouTube (tráileres) y redirige a Stripe para el pago de prueba. Cada uno tiene sus propias condiciones y su propia política de privacidad, y esta web no responde de ellas.</p>
    </section>

    <section aria-labelledby="l-datos">
        <h2 id="l-datos">Datos personales y cookies</h2>
        <p>Cómo se tratan tus datos, con qué base legal, durante cuánto tiempo y cómo ejercer tus derechos está en la <a href="{{ route('legal.privacidad') }}">política de privacidad</a>. Las únicas cookies son técnicas y no necesitan aviso previo.</p>
    </section>

    <section aria-labelledby="l-ley">
        <h2 id="l-ley">Ley aplicable</h2>
        <p>Este aviso se rige por la legislación española.</p>
    </section>
</article>
@endsection

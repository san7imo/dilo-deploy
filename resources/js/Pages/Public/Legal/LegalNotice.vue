<script setup>
import { Head, Link } from "@inertiajs/vue3";
import PublicLayout from "@/Layouts/PublicLayout.vue";

defineOptions({ layout: PublicLayout });
</script>

<template>
  <Head title="Aviso legal | Dilo Records" />

  <section class="legal-shell">
    <div class="legal-card">
      <p class="legal-kicker">Legal</p>
      <h1 class="legal-title">Aviso legal</h1>
      <p class="legal-updated">Última actualización: 14 de marzo de 2026</p>

      <p class="legal-lead">
        En cumplimiento de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la Información y Comercio
        Electrónico (LSSI-CE), se informa de los datos identificativos del titular del sitio web.
      </p>

      <div class="legal-note">
        Completa los siguientes datos antes de publicación definitiva: razón social, NIF/CIF, domicilio y datos
        registrales.
      </div>

      <section class="legal-section">
        <h2>1. Datos identificativos</h2>
        <ul>
          <li><strong>Titular:</strong> Dilo Records</li>
          <li><strong>NIF/CIF:</strong> [pendiente de completar]</li>
          <li><strong>Domicilio social:</strong> [pendiente de completar]</li>
          <li><strong>Email:</strong> [pendiente de completar]</li>
          <li><strong>Teléfono:</strong> [pendiente de completar]</li>
          <li><strong>Datos registrales:</strong> [pendiente de completar, si aplica]</li>
        </ul>
      </section>

      <section class="legal-section">
        <h2>2. Objeto y ámbito de aplicación</h2>
        <p>
          El presente aviso regula el acceso, navegación y uso del sitio web, así como las responsabilidades derivadas
          de la utilización de sus contenidos.
        </p>
      </section>

      <section class="legal-section">
        <h2>3. Condiciones de uso</h2>
        <ul>
          <li>La persona usuaria se compromete a utilizar el sitio web de forma lícita y diligente.</li>
          <li>No se permite el uso del sitio para actividades ilícitas o lesivas de derechos de terceros.</li>
          <li>El titular puede modificar contenidos o servicios sin previo aviso.</li>
        </ul>
      </section>

      <section class="legal-section">
        <h2>4. Propiedad intelectual e industrial</h2>
        <p>
          Todos los contenidos (textos, marcas, logos, diseño, código, imágenes y materiales) están protegidos por la
          normativa de propiedad intelectual e industrial. Queda prohibida su reproducción o explotación sin autorización.
        </p>
      </section>

      <section class="legal-section">
        <h2>5. Responsabilidad</h2>
        <p>
          El titular no garantiza la disponibilidad permanente del sitio ni la ausencia absoluta de errores, aunque
          aplicará medidas razonables para su correcto funcionamiento.
        </p>
      </section>

      <section class="legal-section">
        <h2>6. Enlaces externos</h2>
        <p>
          El sitio puede contener enlaces a webs de terceros. El titular no responde de los contenidos, políticas o
          prácticas de dichos sitios.
        </p>
      </section>

      <section class="legal-section">
        <h2>7. Protección de datos</h2>
        <p>
          El tratamiento de datos personales se rige por la <Link :href="route('public.privacy')" class="legal-link-inline">Política de privacidad</Link>.
        </p>
      </section>

      <section class="legal-section">
        <h2>8. Uso de cookies</h2>
        <p>
          Este sitio utiliza cookies conforme a la <Link :href="route('public.cookies')" class="legal-link-inline">Política de cookies</Link>.
        </p>
      </section>

      <section class="legal-section">
        <h2>9. Legislación aplicable y jurisdicción</h2>
        <p>
          Este aviso se rige por la legislación española. Para cualquier controversia, las partes se someten a los
          juzgados y tribunales que correspondan conforme a derecho.
        </p>
      </section>

      <div class="legal-actions">
        <Link :href="route('public.privacy')" class="legal-link">Política de privacidad</Link>
        <Link :href="route('public.cookies')" class="legal-link">Política de cookies</Link>
        <Link :href="route('public.home')" class="legal-link">Volver al inicio</Link>
      </div>
    </div>
  </section>
</template>

<style scoped>
.legal-shell {
  @apply bg-black text-white py-20 px-4 sm:px-6 lg:px-8;
}

.legal-card {
  @apply mx-auto max-w-4xl rounded-2xl border border-white/10 bg-white/[0.03] p-6 sm:p-8;
}

.legal-kicker {
  @apply text-xs uppercase tracking-[0.25em] text-white/50;
}

.legal-title {
  @apply mt-2 text-3xl sm:text-4xl font-bold;
}

.legal-updated {
  @apply mt-2 text-sm text-white/50;
}

.legal-lead {
  @apply mt-6 text-sm sm:text-base leading-relaxed text-white/80;
}

.legal-note {
  @apply mt-4 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200;
}

.legal-section {
  @apply mt-6 space-y-2 text-sm sm:text-base text-white/80;
}

.legal-section h2 {
  @apply text-lg font-semibold text-white;
}

.legal-section ul {
  @apply list-disc pl-5 space-y-1;
}

.legal-actions {
  @apply mt-8 flex flex-wrap items-center gap-4;
}

.legal-link {
  @apply text-sm text-[#ffa236] hover:underline;
}

.legal-link-inline {
  @apply text-[#ffa236] hover:underline;
}
</style>


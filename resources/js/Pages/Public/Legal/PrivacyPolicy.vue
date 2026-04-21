<script setup>
import { Head, Link } from "@inertiajs/vue3";
import PublicLayout from "@/Layouts/PublicLayout.vue";

defineOptions({ layout: PublicLayout });
</script>

<template>
  <Head title="Política de privacidad | Dilo Records" />

  <section class="legal-shell">
    <div class="legal-card">
      <p class="legal-kicker">Legal</p>
      <h1 class="legal-title">Política de privacidad</h1>
      <p class="legal-updated">Última actualización: 14 de marzo de 2026</p>

      <p class="legal-lead">
        Esta política regula el tratamiento de datos personales realizado a través del sitio web de Dilo Records,
        conforme al Reglamento (UE) 2016/679 (RGPD), la Ley Orgánica 3/2018 (LOPDGDD) y normativa complementaria.
      </p>

      <div class="legal-note">
        Importante: antes de publicar en producción, completa los datos identificativos del responsable del tratamiento
        (NIF/CIF, domicilio social y correo de contacto legal).
      </div>

      <section class="legal-section">
        <h2>1. Responsable del tratamiento</h2>
        <ul>
          <li><strong>Entidad:</strong> Dilo Records</li>
          <li><strong>NIF/CIF:</strong> [pendiente de completar]</li>
          <li><strong>Domicilio:</strong> [pendiente de completar]</li>
          <li><strong>Email de privacidad:</strong> [pendiente de completar]</li>
        </ul>
      </section>

      <section class="legal-section">
        <h2>2. Datos que tratamos</h2>
        <p>Podemos tratar las siguientes categorías de datos:</p>
        <ul>
          <li>Datos identificativos y de contacto (nombre, correo electrónico, teléfono, etc.).</li>
          <li>Datos de navegación y uso del sitio web.</li>
          <li>Información facilitada en formularios de contacto o de alta.</li>
          <li>Datos contractuales, de facturación o gestión de servicios, cuando aplique.</li>
        </ul>
      </section>

      <section class="legal-section">
        <h2>3. Finalidades y bases jurídicas</h2>
        <ul>
          <li><strong>Atención de consultas:</strong> interés legítimo y/o medidas precontractuales.</li>
          <li><strong>Prestación de servicios:</strong> ejecución de contrato.</li>
          <li><strong>Cumplimiento legal:</strong> obligaciones fiscales, contables o regulatorias.</li>
          <li><strong>Comunicaciones comerciales:</strong> consentimiento o interés legítimo, según corresponda.</li>
          <li><strong>Analítica web:</strong> consentimiento para cookies no esenciales.</li>
        </ul>
      </section>

      <section class="legal-section">
        <h2>4. Plazos de conservación</h2>
        <p>
          Conservaremos los datos durante el tiempo necesario para cumplir la finalidad para la que se recabaron y,
          posteriormente, durante los plazos legalmente exigibles para atender responsabilidades.
        </p>
      </section>

      <section class="legal-section">
        <h2>5. Destinatarios y encargados</h2>
        <p>
          Podemos comunicar datos a proveedores que actúan como encargados del tratamiento (alojamiento, correo,
          analítica, soporte técnico) con contratos y garantías de protección de datos.
        </p>
      </section>

      <section class="legal-section">
        <h2>6. Transferencias internacionales</h2>
        <p>
          Si algún proveedor está fuera del Espacio Económico Europeo, se aplicarán garantías adecuadas conforme al
          RGPD (p. ej., cláusulas contractuales tipo).
        </p>
      </section>

      <section class="legal-section">
        <h2>7. Derechos de las personas usuarias</h2>
        <p>Puedes ejercer los derechos de acceso, rectificación, supresión, oposición, limitación y portabilidad.</p>
        <p>
          Para ello, envía una solicitud al correo de privacidad identificándote adecuadamente. También puedes reclamar
          ante la Agencia Española de Protección de Datos (AEPD): <a href="https://www.aepd.es" target="_blank" rel="noopener">www.aepd.es</a>.
        </p>
      </section>

      <section class="legal-section">
        <h2>8. Seguridad</h2>
        <p>
          Aplicamos medidas técnicas y organizativas razonables para proteger la confidencialidad, integridad y
          disponibilidad de la información.
        </p>
      </section>

      <section class="legal-section">
        <h2>9. Menores de edad</h2>
        <p>
          Los servicios no se dirigen a menores que no cuenten con la autorización legalmente exigible cuando sea
          necesaria.
        </p>
      </section>

      <section class="legal-section">
        <h2>10. Cambios en esta política</h2>
        <p>
          Podremos actualizar esta política para adaptarla a cambios normativos o funcionales. La versión vigente será
          siempre la publicada en esta página.
        </p>
      </section>

      <div class="legal-actions">
        <Link :href="route('public.cookies')" class="legal-link">Política de cookies</Link>
        <Link :href="route('public.legal-notice')" class="legal-link">Aviso legal</Link>
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
</style>


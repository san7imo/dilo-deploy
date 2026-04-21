<script setup>
import { Head, Link } from "@inertiajs/vue3";
import PublicLayout from "@/Layouts/PublicLayout.vue";

defineOptions({ layout: PublicLayout });
</script>

<template>
  <Head title="Política de cookies | Dilo Records" />

  <section class="legal-shell">
    <div class="legal-card">
      <p class="legal-kicker">Legal</p>
      <h1 class="legal-title">Política de cookies</h1>
      <p class="legal-updated">Última actualización: 14 de marzo de 2026</p>

      <p class="legal-lead">
        Esta política explica cómo utilizamos cookies y tecnologías similares, de acuerdo con el RGPD, la LOPDGDD y
        el artículo 22.2 de la LSSI-CE.
      </p>

      <section class="legal-section">
        <h2>1. ¿Qué son las cookies?</h2>
        <p>
          Son pequeños archivos que se almacenan en tu dispositivo al visitar una web y que permiten recordar
          información sobre tu navegación.
        </p>
      </section>

      <section class="legal-section">
        <h2>2. Tipos de cookies que utilizamos</h2>
        <div class="overflow-x-auto rounded-xl border border-white/10">
          <table class="w-full min-w-[700px] text-left text-sm text-white/80">
            <thead class="bg-white/5 text-xs uppercase tracking-wide text-white/60">
              <tr>
                <th class="px-3 py-2">Tipo</th>
                <th class="px-3 py-2">Finalidad</th>
                <th class="px-3 py-2">Base legal</th>
                <th class="px-3 py-2">Duración</th>
              </tr>
            </thead>
            <tbody>
              <tr class="border-t border-white/10">
                <td class="px-3 py-2 font-medium">Técnicas (necesarias)</td>
                <td class="px-3 py-2">Funcionamiento básico, seguridad y navegación.</td>
                <td class="px-3 py-2">Interés legítimo / excepción legal</td>
                <td class="px-3 py-2">Sesión o corta duración</td>
              </tr>
              <tr class="border-t border-white/10">
                <td class="px-3 py-2 font-medium">Analíticas</td>
                <td class="px-3 py-2">Medición de uso y mejora del servicio.</td>
                <td class="px-3 py-2">Consentimiento</td>
                <td class="px-3 py-2">Variable según proveedor</td>
              </tr>
              <tr class="border-t border-white/10">
                <td class="px-3 py-2 font-medium">Funcionales</td>
                <td class="px-3 py-2">Recordar preferencias de experiencia.</td>
                <td class="px-3 py-2">Consentimiento</td>
                <td class="px-3 py-2">Variable</td>
              </tr>
              <tr class="border-t border-white/10">
                <td class="px-3 py-2 font-medium">Marketing</td>
                <td class="px-3 py-2">Personalización publicitaria y remarketing.</td>
                <td class="px-3 py-2">Consentimiento</td>
                <td class="px-3 py-2">Variable</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="legal-section">
        <h2>3. Gestión del consentimiento</h2>
        <ul>
          <li>Al entrar en la web, puedes aceptar, rechazar o configurar cookies no esenciales.</li>
          <li>Las cookies no necesarias están desactivadas por defecto hasta obtener consentimiento.</li>
          <li>Puedes cambiar tu decisión eliminando las cookies del navegador o desde la configuración de consentimiento del sitio cuando esté disponible.</li>
        </ul>
      </section>

      <section class="legal-section">
        <h2>4. Cookies de terceros</h2>
        <p>
          Algunas cookies pueden ser gestionadas por terceros proveedores de analítica, vídeo, mapas o redes sociales.
          Recomendamos revisar sus respectivas políticas.
        </p>
      </section>

      <section class="legal-section">
        <h2>5. Cómo desactivar cookies en el navegador</h2>
        <p>
          Puedes bloquear o eliminar cookies desde la configuración de tu navegador. Ten en cuenta que esto puede afectar
          al funcionamiento de determinadas funcionalidades.
        </p>
      </section>

      <section class="legal-section">
        <h2>6. Más información sobre protección de datos</h2>
        <p>
          Consulta también nuestra <Link :href="route('public.privacy')" class="legal-link-inline">Política de privacidad</Link>
          y el <Link :href="route('public.legal-notice')" class="legal-link-inline">Aviso legal</Link>.
        </p>
      </section>

      <div class="legal-actions">
        <Link :href="route('public.privacy')" class="legal-link">Política de privacidad</Link>
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


<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";

type ConsentState = {
  necessary: true;
  analytics: boolean;
  functional: boolean;
  marketing: boolean;
  status: "accepted_all" | "rejected_optional" | "custom";
  version: string;
  updated_at: string;
};

const STORAGE_KEY = "dilo_cookie_consent_v1";
const VERSION = "1.0";

const visible = ref(false);
const showSettings = ref(false);
const analytics = ref(false);
const functional = ref(false);
const marketing = ref(false);

const emitConsent = (payload: ConsentState) => {
  if (typeof window === "undefined") return;
  window.dispatchEvent(new CustomEvent("cookie-consent-updated", { detail: payload }));
};

const persistConsent = (status: ConsentState["status"]) => {
  const payload: ConsentState = {
    necessary: true,
    analytics: analytics.value,
    functional: functional.value,
    marketing: marketing.value,
    status,
    version: VERSION,
    updated_at: new Date().toISOString(),
  };

  localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
  emitConsent(payload);
  visible.value = false;
  showSettings.value = false;
};

const acceptAll = () => {
  analytics.value = true;
  functional.value = true;
  marketing.value = true;
  persistConsent("accepted_all");
};

const rejectOptional = () => {
  analytics.value = false;
  functional.value = false;
  marketing.value = false;
  persistConsent("rejected_optional");
};

const saveCustom = () => {
  persistConsent("custom");
};

onMounted(() => {
  const saved = localStorage.getItem(STORAGE_KEY);
  visible.value = !saved;
});
</script>

<template>
  <transition
    enter-active-class="transition-all duration-300 ease-out"
    enter-from-class="opacity-0 translate-y-2"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition-all duration-200 ease-in"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 translate-y-2"
  >
    <section
      v-if="visible"
      class="fixed inset-x-4 bottom-4 z-[80] mx-auto max-w-3xl rounded-2xl border border-white/15 bg-black/85 p-4 text-white shadow-2xl backdrop-blur-md sm:p-5"
      role="dialog"
      aria-live="polite"
      aria-label="Consentimiento de cookies"
    >
      <div class="space-y-3">
        <h2 class="text-sm font-semibold tracking-wide text-white">Configuración de cookies</h2>
        <p class="text-sm text-white/75">
          Utilizamos cookies técnicas necesarias y, con tu consentimiento, cookies de analítica y personalización
          conforme al RGPD, la LOPDGDD y el artículo 22.2 de la LSSI-CE.
        </p>

        <div class="flex flex-wrap items-center gap-3 text-xs text-white/70">
          <Link :href="route('public.cookies')" class="underline hover:text-white">Política de cookies</Link>
          <Link :href="route('public.privacy')" class="underline hover:text-white">Política de privacidad</Link>
          <Link :href="route('public.legal-notice')" class="underline hover:text-white">Aviso legal</Link>
        </div>

        <div v-if="showSettings" class="rounded-xl border border-white/10 bg-white/5 p-3">
          <div class="space-y-2 text-sm">
            <label class="flex items-center justify-between gap-4">
              <span class="text-white/90">Cookies técnicas (necesarias)</span>
              <span class="text-xs rounded-full border border-emerald-500/40 bg-emerald-500/20 px-2 py-1 text-emerald-200">Siempre activas</span>
            </label>

            <label class="flex items-center justify-between gap-4">
              <span class="text-white/90">Cookies de analítica</span>
              <input v-model="analytics" type="checkbox" class="h-4 w-4 rounded border-white/20 bg-black text-[#ffa236] focus:ring-[#ffa236]" />
            </label>

            <label class="flex items-center justify-between gap-4">
              <span class="text-white/90">Cookies funcionales</span>
              <input v-model="functional" type="checkbox" class="h-4 w-4 rounded border-white/20 bg-black text-[#ffa236] focus:ring-[#ffa236]" />
            </label>

            <label class="flex items-center justify-between gap-4">
              <span class="text-white/90">Cookies de marketing</span>
              <input v-model="marketing" type="checkbox" class="h-4 w-4 rounded border-white/20 bg-black text-[#ffa236] focus:ring-[#ffa236]" />
            </label>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 pt-1">
          <button type="button" class="btn-subtle" @click="showSettings = !showSettings">
            {{ showSettings ? "Ocultar configuración" : "Configurar" }}
          </button>
          <button type="button" class="btn-subtle" @click="rejectOptional">Rechazar opcionales</button>
          <button v-if="showSettings" type="button" class="btn-primary" @click="saveCustom">Guardar preferencias</button>
          <button v-else type="button" class="btn-primary" @click="acceptAll">Aceptar cookies</button>
        </div>
      </div>
    </section>
  </transition>
</template>

<style scoped>
.btn-subtle {
  @apply rounded-lg border border-white/20 px-3 py-2 text-xs font-medium text-white/90 hover:bg-white/10 transition;
}

.btn-primary {
  @apply rounded-lg bg-[#ffa236] px-3 py-2 text-xs font-semibold text-black hover:bg-[#ffb54d] transition;
}
</style>


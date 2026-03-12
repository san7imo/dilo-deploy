<script setup>
import { Head, Link } from "@inertiajs/vue3";
import PublicLayout from "@/Layouts/PublicLayout.vue";

defineOptions({ layout: PublicLayout });

defineProps({
  title: { type: String, required: true },
  description: { type: String, default: "" },
});
</script>

<template>
  <Head :title="`${title} — Dilo Records`" />

  <section class="relative min-h-[70vh] overflow-hidden bg-black text-white">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,#222_0%,#000_60%)] opacity-80" />

    <div class="relative mx-auto max-w-5xl px-6 py-32 text-center">
      <p class="text-xs tracking-[0.3em] text-gray-400 uppercase mb-4">Menu Dilo Records</p>
      <h1 class="text-4xl sm:text-5xl font-bold mb-6">{{ title }}</h1>
      <p class="text-base sm:text-lg text-gray-300 max-w-2xl mx-auto">
        {{ description }}
      </p>

      <div class="mt-10">
        <Link
          :href="route('public.home')"
          class="inline-flex items-center gap-2 rounded-lg border border-white/20 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10 transition"
        >
          <span aria-hidden="true">←</span>
          Volver al inicio
        </Link>
      </div>
    </div>
  </section>
</template>

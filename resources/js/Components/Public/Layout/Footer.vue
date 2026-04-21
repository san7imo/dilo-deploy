<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
</script>

<template>
  <footer class="border-t border-white/10 bg-black">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
      <div class="flex flex-col md:flex-row items-center justify-between gap-6 text-sm text-white/60">
        <p>Copyright {{ new Date().getFullYear() }} © Dilo Records. All rights Reserved.</p>
        <nav class="flex flex-wrap items-center gap-x-6 gap-y-3">
          <Link :href="route('public.privacy')" class="hover:text-white">Política de privacidad</Link>
          <span class="opacity-30">–</span>
          <Link :href="route('public.legal-notice')" class="hover:text-white">Aviso legal</Link>
          <span class="opacity-30">–</span>
          <Link :href="route('public.cookies')" class="hover:text-white">Política de cookies</Link>
        </nav>
      </div>
    </div>
  </footer>
</template>

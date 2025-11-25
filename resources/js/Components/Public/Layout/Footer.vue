<template>
  <footer class="border-t border-white/10 bg-black">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
      <div class="flex flex-col md:flex-row items-center justify-between gap-6 text-sm text-white/60">
        <p>Copyright {{ new Date().getFullYear() }} © Dilo Records. All rights Reserved.</p>
        <nav class="flex flex-wrap items-center gap-x-6 gap-y-3">
          <a href="/privacidad" class="hover:text-white">Política de privacidad</a>
          <span class="opacity-30">–</span>
          <a href="/legal" class="hover:text-white">Aviso Legal</a>
          <span class="opacity-30">–</span>
          <a href="/cookies" class="hover:text-white">Política de Cookies</a>
        </nav>
      </div>
    </div>
  </footer>
</template>

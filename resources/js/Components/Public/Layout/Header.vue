<script setup lang="ts">
import logoBlanco from '@/Assets/Images/Logos/logo-blanco.webp'
import { Icon } from '@iconify/vue'
const emit = defineEmits<{ (e: 'toggle'): void }>()
</script>

<template>
  <!-- 🔹 Navbar transparente superpuesto al carrusel -->
  <header
    class="fixed top-0 left-0 w-full z-50 flex items-center justify-between px-6 sm:px-10 h-20 bg-transparent transition-all"
  >
    <!-- Logo -->
    <a href="/" class="inline-flex items-center gap-2">
      <img
        :src="logoBlanco"
        alt="Dilo Records"
        class="h-24 w-auto object-contain"
      />
      <span class="sr-only">Inicio</span>
    </a>

    <!-- Icono usuario (login) y botón hamburguesa -->
    <div class="flex items-center gap-3">
      <a href="/login" class="inline-flex items-center justify-center h-10 w-10 rounded-md hover:bg-white/5 transition" aria-label="Iniciar sesión">
        <Icon icon="mdi:account" class="h-5 w-5 text-white" />
      </a>
      <button
        @click="emit('toggle')"
        class="group inline-flex items-center justify-center h-10 w-10 focus:outline-none"
        aria-label="Abrir menú"
      >
      <span class="block relative h-4 w-5">
        <span
          class="absolute inset-x-0 top-0 h-0.5 bg-white rounded transition-all group-hover:translate-y-0.5"
        ></span>
        <span
          class="absolute inset-x-0 top-1/2 -translate-y-1/2 h-0.5 bg-white rounded"
        ></span>
        <span
          class="absolute inset-x-0 bottom-0 h-0.5 bg-white rounded transition-all group-hover:-translate-y-0.5"
        ></span>
      </span>
      </button>
    </div>
  </header>
</template>

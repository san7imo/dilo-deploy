<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import Header from '@/Components/Public/Layout/Header.vue'
import NavbarDrawer from '@/Components/Public/Layout/NavbarDrawer.vue'
import Footer from '@/Components/Public/Layout/Footer.vue'
import WhatsAppButton from '@/Components/Public/Layout/WhatsAppButton.vue'

const drawerOpen = ref(false)
const openDrawer = () => (drawerOpen.value = true)
const closeDrawer = () => (drawerOpen.value = false)
const toggleDrawer = () => (drawerOpen.value = !drawerOpen.value)

// Cerrar con tecla ESC
const onKey = (e: KeyboardEvent) => {
  if (e.key === 'Escape') closeDrawer()
}

onMounted(() => window.addEventListener('keydown', onKey))
onUnmounted(() => window.removeEventListener('keydown', onKey))
</script>

<template>
  <div class="relative min-h-dvh bg-black text-white antialiased">
    <!-- 🔹 Header fijo sobre todo -->
    <Header @toggle="toggleDrawer" class="z-50" />

    <!-- 🔹 Contenido principal -->
    <main class="relative z-10">
      <slot />
    </main>

    <!-- 🔹 Footer -->
    <Footer />

    <!-- 🔹 Drawer lateral -->
    <NavbarDrawer :open="drawerOpen" @close="closeDrawer" />

    <!-- 🔹 Botón flotante WhatsApp -->
    <WhatsAppButton phone="+34 608 52 94 93" />
  </div>
</template>

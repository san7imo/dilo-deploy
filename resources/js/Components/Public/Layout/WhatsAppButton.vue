<script setup lang="ts">
import { computed } from 'vue' // ✅ ← falta esta línea

const props = withDefaults(defineProps<{ phone: string; message?: string }>(), {
  message: 'Hola, quiero más información.'
})

const href = computed(() => {
  const q = encodeURIComponent(props.message)
  const clean = props.phone.replace(/\D/g, '')
  return `https://wa.me/${clean}?text=${q}`
})
</script>

<template>
  <a
    :href="href"
    target="_blank"
    rel="noopener"
    class="fixed z-50 bottom-6 right-6 h-14 w-14 rounded-full bg-emerald-500 hover:bg-emerald-600 shadow-lg inline-flex items-center justify-center ring-8 ring-emerald-500/20 transition"
    aria-label="Escribir por WhatsApp"
  >
    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor">
      <path d="M20 3.5A10.5 10.5 0 0 0 2.9 17.6L2 22l4.5-.9A10.5 10.5 0 1 0 20 3.5m-8.1 16a8.4 8.4 0 0 1-4.3-1.2l-.3-.2-2.6.5.5-2.5-.2-.3A8.4 8.4 0 1 1 11.9 19.5M16.6 14c-.2-.1-1.3-.7-1.4-.7s-.3-.1-.5.1-.6.7-.7.8-.3.1-.5 0a6.9 6.9 0 0 1-2-1.2 7.5 7.5 0 0 1-1.4-1.7c-.2-.3 0-.4.1-.6l.3-.4.1-.4c0-.1 0-.3 0-.4s-.5-1.4-.7-1.9-.4-.4-.6-.4h-.5c-.1 0-.4 0-.6.3a2.5 2.5 0 0 0-.8 1.9 4.3 4.3 0 0 0 .9 2.3 9.7 9.7 0 0 0 3.8 3.5 12.7 12.7 0 0 0 1.3.6 3 3 0 0 0 1.4.1c.4-.1 1.3-.5 1.5-1.1s.2-1 .1-1.1-.2-.1-.4-.2"/>
    </svg>
  </a>
</template>

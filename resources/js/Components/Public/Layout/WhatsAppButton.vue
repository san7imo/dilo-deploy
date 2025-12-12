<script setup lang="ts">
import { computed } from 'vue'

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
    rel="noopener noreferrer"
    class="group fixed z-50 bottom-6 right-6 inline-flex items-center justify-center
           h-14 w-14 rounded-full bg-[#25D366]
           shadow-lg shadow-black/15 ring-1 ring-black/5
           transition hover:scale-105 active:scale-95
           focus:outline-none focus-visible:ring-4 focus-visible:ring-[#25D366]/30"
    aria-label="Escribir por WhatsApp"
    title="Escribir por WhatsApp"
  >
    <!-- WhatsApp logo (más limpio) -->
    <svg
      class="h-7 w-7 text-white drop-shadow-sm"
      viewBox="0 0 32 32"
      aria-hidden="true"
      fill="currentColor"
    >
      <path
        d="M19.11 17.79c-.27-.14-1.58-.78-1.82-.87-.25-.09-.43-.14-.61.14-.18.27-.7.87-.86 1.05-.16.18-.32.2-.59.07-.27-.14-1.14-.42-2.17-1.34-.8-.71-1.34-1.59-1.5-1.86-.16-.27-.02-.41.12-.55.12-.12.27-.32.41-.48.14-.16.18-.27.27-.45.09-.18.05-.34-.02-.48-.07-.14-.61-1.48-.84-2.03-.22-.53-.45-.46-.61-.47h-.52c-.18 0-.48.07-.73.34-.25.27-.95.93-.95 2.27 0 1.34.98 2.64 1.11 2.82.14.18 1.93 2.95 4.68 4.13.65.28 1.15.45 1.55.57.65.21 1.24.18 1.71.11.52-.08 1.58-.64 1.8-1.26.22-.62.22-1.15.16-1.26-.07-.11-.25-.18-.52-.32z"
      />
      <path
        d="M16.02 5.33c-5.89 0-10.67 4.78-10.67 10.67 0 1.87.49 3.62 1.34 5.14L5.33 26.67l5.69-1.32c1.46.8 3.13 1.26 4.99 1.26 5.89 0 10.67-4.78 10.67-10.67S21.91 5.33 16.02 5.33zm0 19.53c-1.69 0-3.26-.46-4.62-1.26l-.33-.19-3.32.77.78-3.24-.21-.33c-.86-1.38-1.36-3-1.36-4.74 0-4.95 4.03-8.98 8.98-8.98 4.95 0 8.98 4.03 8.98 8.98 0 4.95-4.03 8.98-8.98 8.98z"
      />
    </svg>

    <!-- Tooltip sencillo -->
    <span
      class="pointer-events-none absolute right-16 whitespace-nowrap rounded-lg bg-black/85 px-3 py-1.5
             text-xs text-white opacity-0 shadow-md transition
             group-hover:opacity-100"
    >
      WhatsApp
    </span>
  </a>
</template>

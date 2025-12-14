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
    <!-- WhatsApp logo -->
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" class="w-6 h-6 fill-white">
      <path
        d="M16 0C7.163 0 0 7.163 0 16c0 2.837.744 5.62 2.156 8.063L0 32l8.25-2.094A15.94 15.94 0 0016 32c8.837 0 16-7.163 16-16S24.837 0 16 0zm0 29.091a13.05 13.05 0 01-6.658-1.833l-.478-.283-4.9 1.244 1.309-4.783-.31-.493A13.032 13.032 0 012.91 16C2.91 8.767 8.767 2.91 16 2.91S29.09 8.767 29.09 16 23.233 29.091 16 29.091zm7.545-9.964c-.41-.205-2.425-1.195-2.8-1.332-.375-.137-.65-.205-.923.205-.273.41-1.06 1.332-1.298 1.606-.24.273-.478.308-.888.102-.41-.205-1.732-.637-3.297-2.03-1.217-1.085-2.04-2.424-2.28-2.833-.24-.41-.025-.63.18-.835.185-.185.41-.478.615-.717.205-.24.273-.41.41-.683.137-.273.068-.512-.034-.717-.102-.205-.923-2.224-1.265-3.04-.334-.8-.673-.692-.923-.705-.24-.012-.512-.015-.785-.015-.273 0-.717.102-1.093.512-.375.41-1.435 1.4-1.435 3.42 0 2.02 1.47 3.97 1.675 4.243.205.273 2.893 4.417 7.013 6.195.98.422 1.745.674 2.342.863.984.312 1.88.268 2.59.163.79-.117 2.425-.995 2.768-1.955.342-.96.342-1.783.24-1.955-.103-.17-.376-.273-.786-.478z" />
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

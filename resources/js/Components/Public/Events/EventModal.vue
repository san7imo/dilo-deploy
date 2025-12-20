<script setup>
import { computed } from 'vue'

const props = defineProps({
  event: { type: Object, required: true },
  isOpen: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

// Formatear la fecha
const formattedDate = computed(() => {
  if (!props.event.event_date) return 'Próximamente'

  return new Intl.DateTimeFormat('es-ES', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(props.event.event_date))
})

// Whatsapp 
const whatsappLink = computed(() => {
  const message = encodeURIComponent(
    `Hola, me interesa el evento "${props.event.title}". ¿Me brindas más información?`
  )

  if (props.event.whatsapp_number) {

    const cleanNumber = props.event.whatsapp_number.replace(/\D/g, '')
    return `https://wa.me/${cleanNumber}?text=${message}`
  }

  return `https://wa.me/?text=${message}`
})

const closeModal = () => emit('close')

const handleBackdropClick = (e) => {
  if (e.target === e.currentTarget) closeModal()
}
</script>

<template>
  <transition name="fade">
    <div v-if="isOpen" @click="handleBackdropClick"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
      <transition name="modal-scale">
        <div
          class="relative w-full max-w-3xl rounded-2xl bg-zinc-950 ring-1 ring-white/10 shadow-2xl max-h-[90vh] flex flex-col overflow-y-auto">

          <!-- Header con poster -->
          <div class="relative h-64 flex-shrink-0">
            <img v-if="event.poster_url" :src="event.poster_url" :alt="event.title"
              class="w-full h-full object-cover" />
            <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-transparent to-transparent" />

            <button @click="closeModal"
              class="absolute top-4 right-4 bg-zinc-900/80 hover:bg-zinc-800 text-white p-2 rounded-full transition">
              ✕
            </button>

            <!-- Título sobre imagen -->
            <div class="absolute bottom-4 left-4 right-4">
              <h2 class="text-3xl font-bold text-white leading-tight">{{ event.title }}</h2>
            </div>
          </div>

          <!-- Contenido -->
          <div class="p-6 space-y-6 flex-1">
            <!-- Ubicación y fecha -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-2">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Ubicación</p>
                <div class="space-y-1">
                  <p v-if="event.city || event.country" class="text-white font-medium">
                    {{ event.city }}{{ event.city && event.country ? ',' : '' }} {{ event.country }}
                  </p>
                  <p v-if="event.location" class="text-gray-300">{{ event.location }}</p>
                  <p v-if="event.venue_address" class="text-gray-400 text-sm">{{ event.venue_address }}</p>
                </div>
              </div>

              <div class="space-y-2">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Fecha y hora</p>
                <p class="text-white font-medium">{{ formattedDate }}</p>
              </div>
            </div>

            <!-- Descripción -->
            <div v-if="event.description" class="space-y-2">
              <p class="text-xs text-gray-500 uppercase tracking-wide">Acerca del evento</p>
              <p class="text-gray-300 leading-relaxed">{{ event.description }}</p>
            </div>

            <!-- Artistas invitados -->
            <div v-if="event.artists && event.artists.length" class="space-y-3">
              <p class="text-xs text-gray-500 uppercase tracking-wide">
                Artista{{ event.artists.length === 1 ? '' : 's' }} participante{{ event.artists.length === 1 ? '' : 's'
                }}
              </p>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                <a v-for="artist in event.artists" :key="artist.id" :href="route('public.artists.show', artist.slug)"
                  class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-3 text-center hover:border-zinc-600 hover:bg-zinc-800 transition block">
                  <p class="text-white font-medium text-sm">{{ artist.name }}</p>
                </a>
              </div>
            </div>

            <!-- CTA -->
            <div class="flex gap-3 pt-4">
              <a :href="whatsappLink" target="_blank"
                class="flex-1 flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white font-semibold py-3 rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" class="w-5 h-5 fill-white">
                  <path
                    d="M16 0C7.163 0 0 7.163 0 16c0 2.837.744 5.62 2.156 8.063L0 32l8.25-2.094A15.94 15.94 0 0016 32c8.837 0 16-7.163 16-16S24.837 0 16 0zm0 29.091a13.05 13.05 0 01-6.658-1.833l-.478-.283-4.9 1.244 1.309-4.783-.31-.493A13.032 13.032 0 012.91 16C2.91 8.767 8.767 2.91 16 2.91S29.09 8.767 29.09 16 23.233 29.091 16 29.091zm7.545-9.964c-.41-.205-2.425-1.195-2.8-1.332-.375-.137-.65-.205-.923.205-.273.41-1.06 1.332-1.298 1.606-.24.273-.478.308-.888.102-.41-.205-1.732-.637-3.297-2.03-1.217-1.085-2.04-2.424-2.28-2.833-.24-.41-.025-.63.18-.835.185-.185.41-.478.615-.717.205-.24.273-.41.41-.683.137-.273.068-.512-.034-.717-.102-.205-.923-2.224-1.265-3.04-.334-.8-.673-.692-.923-.705-.24-.012-.512-.015-.785-.015-.273 0-.717.102-1.093.512-.375.41-1.435 1.4-1.435 3.42 0 2.02 1.47 3.97 1.675 4.243.205.273 2.893 4.417 7.013 6.195.98.422 1.745.674 2.342.863.984.312 1.88.268 2.59.163.79-.117 2.425-.995 2.768-1.955.342-.96.342-1.783.24-1.955-.103-.17-.376-.273-.786-.478z" />
                </svg>
                Contactar por WhatsApp
              </a>

              <button @click="closeModal"
                class="px-4 py-3 bg-zinc-900 hover:bg-zinc-800 text-white rounded-lg transition border border-zinc-800">
                Cerrar
              </button>
            </div>
          </div>

        </div>
      </transition>
    </div>
  </transition>
</template>

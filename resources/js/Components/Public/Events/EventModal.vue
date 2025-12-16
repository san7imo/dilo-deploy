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
          class="relative w-full max-w-2xl rounded-3xl bg-zinc-950 ring-1 ring-white/10 shadow-2xl max-h-[90vh] flex flex-col">

          <!-- Header -->
          <div class="relative h-64">
            <img :src="event.poster_url" class="w-full h-full object-cover" />
            <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 to-transparent" />

            <button @click="closeModal"
              class="absolute top-4 right-4 bg-white/10 hover:bg-white/20 text-white p-2 rounded-full">
              ✕
            </button>
          </div>

          <!-- Content -->
          <div class="p-6">
            <h2 class="text-3xl font-black text-white">
              {{ event.title }}
            </h2>

            <p class="text-zinc-400 mt-2">
              📍 {{ event.location || 'Ubicación no disponible' }}
            </p>

            <p class="text-zinc-300 mt-4">
              {{ formattedDate }}
            </p>

            <p v-if="event.description" class="mt-6 text-zinc-300">
              {{ event.description }}
            </p>

            <!-- artistas invitados -->
            <div v-if="event.artists && event.artists.length" class="mt-8">
              <h3 class="text-white font-bold mb-3">
                Artistas invitados
              </h3>

              <div class="flex flex-wrap gap-3">
                <span v-for="artist in event.artists" :key="artist.id"
                  class="inline-flex items-center px-4 py-2 rounded-full bg-zinc-900 text-white text-sm font-medium ring-1 ring-white/10">
                  {{ artist.name }}
                </span>
              </div>
            </div>


            <!-- CTA -->
            <div class="mt-8 flex gap-4">
              <!-- WhatsApp -->
              <a :href="whatsappLink" target="_blank"
                class="flex-1 flex items-center justify-center gap-3 bg-green-500 hover:bg-green-600 text-white font-semibold py-3 rounded-2xl transition">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" class="w-6 h-6 fill-white">
                  <path
                    d="M16 0C7.163 0 0 7.163 0 16c0 2.837.744 5.62 2.156 8.063L0 32l8.25-2.094A15.94 15.94 0 0016 32c8.837 0 16-7.163 16-16S24.837 0 16 0zm0 29.091a13.05 13.05 0 01-6.658-1.833l-.478-.283-4.9 1.244 1.309-4.783-.31-.493A13.032 13.032 0 012.91 16C2.91 8.767 8.767 2.91 16 2.91S29.09 8.767 29.09 16 23.233 29.091 16 29.091zm7.545-9.964c-.41-.205-2.425-1.195-2.8-1.332-.375-.137-.65-.205-.923.205-.273.41-1.06 1.332-1.298 1.606-.24.273-.478.308-.888.102-.41-.205-1.732-.637-3.297-2.03-1.217-1.085-2.04-2.424-2.28-2.833-.24-.41-.025-.63.18-.835.185-.185.41-.478.615-.717.205-.24.273-.41.41-.683.137-.273.068-.512-.034-.717-.102-.205-.923-2.224-1.265-3.04-.334-.8-.673-.692-.923-.705-.24-.012-.512-.015-.785-.015-.273 0-.717.102-1.093.512-.375.41-1.435 1.4-1.435 3.42 0 2.02 1.47 3.97 1.675 4.243.205.273 2.893 4.417 7.013 6.195.98.422 1.745.674 2.342.863.984.312 1.88.268 2.59.163.79-.117 2.425-.995 2.768-1.955.342-.96.342-1.783.24-1.955-.103-.17-.376-.273-.786-.478z" />
                </svg>
                WhatsApp
              </a>

              <button @click="closeModal" class="bg-zinc-800 hover:bg-zinc-700 text-white px-6 py-3 rounded-2xl">
                Cerrar
              </button>
            </div>
          </div>

        </div>
      </transition>
    </div>
  </transition>
</template>

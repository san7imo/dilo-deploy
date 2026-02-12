<!-- resources/js/Components/Public/Events/ShareEventModal.vue -->
<script setup>
import { Icon } from '@iconify/vue'
import { computed, ref } from 'vue'

const props = defineProps({
  event: { type: Object, required: true },
  shareUrl: { type: String, default: '' },
  isOpen: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

const url = computed(() => {
  if (props.shareUrl) return props.shareUrl
  if (typeof window !== 'undefined') return window.location.href
  if (typeof route === 'function' && props.event?.slug) {
    return route('public.events.show', props.event.slug)
  }
  return ''
})

const title = computed(() => props.event?.title || 'Evento')
const message = computed(() => `Mira este evento: ${title.value}`)
const encodedUrl = computed(() => encodeURIComponent(url.value))
const encodedText = computed(() => encodeURIComponent(message.value))

const shareTargets = computed(() => [
  {
    key: 'whatsapp',
    name: 'WhatsApp',
    icon: 'simple-icons:whatsapp',
    color: 'group-hover:text-green-500',
    url: `https://wa.me/?text=${encodedText.value}%20${encodedUrl.value}`,
  },
  {
    key: 'facebook',
    name: 'Facebook',
    icon: 'simple-icons:facebook',
    color: 'group-hover:text-blue-600',
    url: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl.value}`,
  },
  {
    key: 'x',
    name: 'X',
    icon: 'simple-icons:x',
    color: 'group-hover:text-gray-300',
    url: `https://twitter.com/intent/tweet?text=${encodedText.value}&url=${encodedUrl.value}`,
  },
  {
    key: 'telegram',
    name: 'Telegram',
    icon: 'simple-icons:telegram',
    color: 'group-hover:text-sky-500',
    url: `https://t.me/share/url?url=${encodedUrl.value}&text=${encodedText.value}`,
  },
  {
    key: 'email',
    name: 'Email',
    icon: 'mdi:email-outline',
    color: 'group-hover:text-orange-300',
    url: `mailto:?subject=${encodeURIComponent(title.value)}&body=${encodedText.value}%0A${encodedUrl.value}`,
  },
])

const copied = ref(false)

const copyLink = async () => {
  if (!url.value) return

  try {
    if (navigator?.clipboard?.writeText) {
      await navigator.clipboard.writeText(url.value)
    } else {
      const textarea = document.createElement('textarea')
      textarea.value = url.value
      textarea.setAttribute('readonly', '')
      textarea.style.position = 'absolute'
      textarea.style.left = '-9999px'
      document.body.appendChild(textarea)
      textarea.select()
      document.execCommand('copy')
      document.body.removeChild(textarea)
    }

    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (error) {
    console.error('No se pudo copiar el enlace', error)
  }
}

const closeModal = () => emit('close')

const handleBackdropClick = (e) => {
  if (e.target === e.currentTarget) {
    closeModal()
  }
}
</script>

<template>
  <transition name="fade">
    <div v-if="isOpen" @click="handleBackdropClick"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <transition name="modal-scale">
        <div v-if="isOpen"
          class="relative w-full max-w-md bg-zinc-950 rounded-3xl ring-1 ring-white/10 shadow-2xl p-8">
          <button @click="closeModal"
            class="absolute top-4 right-4 z-10 bg-white/10 hover:bg-white/20 text-white p-2 rounded-full transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>

          <h2 class="text-3xl font-black text-white mb-2">Compartir evento</h2>
          <p class="text-zinc-400 mb-8">{{ title }}</p>

          <div class="grid grid-cols-2 gap-4">
            <a v-for="network in shareTargets" :key="network.key"
              :href="network.url"
              target="_blank"
              rel="noopener noreferrer"
              class="group flex flex-col items-center justify-center p-4 bg-zinc-900 rounded-2xl ring-1 ring-white/10 hover:ring-white/20 transition-all duration-300 hover:scale-105">
              <Icon :icon="network.icon" :class="`w-8 h-8 text-white mb-2 transition-colors ${network.color}`" />
              <span class="text-white font-semibold text-xs">{{ network.name }}</span>
            </a>
          </div>

          <button @click="copyLink"
            class="mt-6 w-full flex items-center justify-center gap-2 rounded-2xl bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-3 ring-1 ring-white/10 hover:ring-white/20 transition">
            <Icon icon="mdi:link-variant" class="text-lg" />
            {{ copied ? 'Enlace copiado' : 'Copiar enlace' }}
          </button>
        </div>
      </transition>
    </div>
  </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.modal-scale-enter-active,
.modal-scale-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-scale-enter-from,
.modal-scale-leave-to {
  transform: scale(0.95);
  opacity: 0;
}
</style>

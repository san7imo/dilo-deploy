<!-- resources/js/Components/Public/Home/ContactSection.vue -->
<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  contact: { type: Object, default: () => ({}) },
  eyebrow: { type: String, default: 'Contacto' },
  title: { type: String, default: 'Hablemos de tu proyecto' },
  description: {
    type: String,
    default: 'Completa el formulario y te responderemos lo antes posible.',
  },
  fullHeight: { type: Boolean, default: false },
})

const form = useForm({
  name: '',
  email: '',
  phone: '',
  subject: '',
  message: '',
})

const successMessage = ref('')

const submit = () => {
  successMessage.value = ''
  form.post(route('public.contact.submit'), {
    preserveScroll: true,
    onSuccess: () => {
      successMessage.value = 'Gracias, tu mensaje fue enviado.'
      form.reset()
    },
  })
}

const socialButtons = computed(() => {
  const social = props.contact?.social || {}
  const defaults = {
    instagram: 'https://www.instagram.com/dilorecords',
    facebook: 'https://www.facebook.com/dilorecords',
    tiktok: 'https://www.tiktok.com/@dilorecords',
    x: 'https://x.com/dilorecords',
  }
  const items = [
    { key: 'instagram', icon: 'simple-icons:instagram', label: 'Instagram', url: social.instagram || defaults.instagram },
    { key: 'facebook', icon: 'simple-icons:facebook', label: 'Facebook', url: social.facebook || defaults.facebook },
    { key: 'tiktok', icon: 'simple-icons:tiktok', label: 'TikTok', url: social.tiktok || defaults.tiktok },
    { key: 'youtube', icon: 'simple-icons:youtube', label: 'YouTube', url: social.youtube },
    { key: 'spotify', icon: 'simple-icons:spotify', label: 'Spotify', url: social.spotify },
    { key: 'x', icon: 'simple-icons:x', label: 'X', url: social.x || defaults.x },
  ]

  return items.filter((item) => !!item.url)
})
</script>

<template>
  <section :class="['bg-black text-white', fullHeight ? 'min-h-screen pt-20 pb-12' : 'py-12']">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mb-6">
        <p class="text-[10px] uppercase tracking-[0.3em] text-zinc-400">{{ eyebrow }}</p>
        <h2 class="text-2xl md:text-4xl font-black text-white mt-2">{{ title }}</h2>
        <p class="text-sm text-zinc-400 mt-2 max-w-2xl">
          {{ description }}
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7 bg-zinc-950/70 ring-1 ring-white/10 rounded-2xl p-6">
          <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="text-sm text-zinc-300">Nombre</label>
                <input v-model="form.name" type="text"
                  class="mt-2 w-full rounded-xl border border-white/10 bg-zinc-900/60 px-4 py-3 text-white placeholder:text-zinc-500 focus:border-[#ffa236] focus:ring-2 focus:ring-[#ffa236]/40"
                  placeholder="Tu nombre" />
                <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
              </div>
              <div>
                <label class="text-sm text-zinc-300">Email</label>
                <input v-model="form.email" type="email"
                  class="mt-2 w-full rounded-xl border border-white/10 bg-zinc-900/60 px-4 py-3 text-white placeholder:text-zinc-500 focus:border-[#ffa236] focus:ring-2 focus:ring-[#ffa236]/40"
                  placeholder="tucorreo@email.com" />
                <p v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="text-sm text-zinc-300">Teléfono (opcional)</label>
                <input v-model="form.phone" type="text"
                  class="mt-2 w-full rounded-xl border border-white/10 bg-zinc-900/60 px-4 py-3 text-white placeholder:text-zinc-500 focus:border-[#ffa236] focus:ring-2 focus:ring-[#ffa236]/40"
                  placeholder="+34 000 000 000" />
                <p v-if="form.errors.phone" class="text-red-400 text-xs mt-1">{{ form.errors.phone }}</p>
              </div>
              <div>
                <label class="text-sm text-zinc-300">Asunto</label>
                <input v-model="form.subject" type="text"
                  class="mt-2 w-full rounded-xl border border-white/10 bg-zinc-900/60 px-4 py-3 text-white placeholder:text-zinc-500 focus:border-[#ffa236] focus:ring-2 focus:ring-[#ffa236]/40"
                  placeholder="¿En qué te ayudamos?" />
                <p v-if="form.errors.subject" class="text-red-400 text-xs mt-1">{{ form.errors.subject }}</p>
              </div>
            </div>

            <div>
              <label class="text-sm text-zinc-300">Mensaje</label>
              <textarea v-model="form.message" rows="5"
                class="mt-2 w-full rounded-xl border border-white/10 bg-zinc-900/60 px-4 py-3 text-white placeholder:text-zinc-500 focus:border-[#ffa236] focus:ring-2 focus:ring-[#ffa236]/40"
                placeholder="Cuéntanos más"></textarea>
              <p v-if="form.errors.message" class="text-red-400 text-xs mt-1">{{ form.errors.message }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
              <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-full bg-[#ffa236] text-black font-semibold px-5 py-2.5 hover:bg-[#ffb54d] transition"
                :disabled="form.processing">
                <Icon icon="mdi:send" class="text-lg" />
                Enviar mensaje
              </button>
              <p v-if="successMessage" class="text-green-400 text-xs">{{ successMessage }}</p>
            </div>
          </form>
        </div>

        <div class="lg:col-span-5 space-y-4">
          <div class="bg-zinc-950/60 ring-1 ring-white/10 rounded-2xl p-5">
            <h3 class="text-lg font-bold">Información directa</h3>
            <div class="mt-3 space-y-2 text-sm text-zinc-300">
              <p v-if="contact.email"><strong>Email:</strong> {{ contact.email }}</p>
              <p v-if="contact.phone"><strong>Teléfono:</strong> {{ contact.phone }}</p>
              <p v-if="contact.address"><strong>Ubicación:</strong> {{ contact.address }}</p>
            </div>
          </div>

          <div class="bg-zinc-950/60 ring-1 ring-white/10 rounded-2xl p-5">
            <h3 class="text-lg font-bold">Síguenos</h3>
            <div class="mt-3 flex flex-wrap gap-2">
              <a v-for="item in socialButtons" :key="item.key" :href="item.url" target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-zinc-900 text-white ring-1 ring-white/10 hover:ring-white/20 hover:scale-105 transition"
                :aria-label="item.label">
                <Icon :icon="item.icon" class="text-base" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<!-- resources/js/Components/Public/Artist/ArtistLinkCard.vue -->
<script setup>
import { Icon } from '@iconify/vue'

defineProps({
  title: { type: String, required: true },
  icon: { type: String, required: true },
  description: { type: String, default: '' },
  isDisabled: { type: Boolean, default: false },
  isEmoji: { type: Boolean, default: false },
})

const emit = defineEmits(['click'])

const handleClick = () => {
  emit('click')
}
</script>

<template>
  <button @click="handleClick" :disabled="isDisabled" :class="[
    'flex flex-col items-center justify-center w-full aspect-square p-3 rounded-2xl ring-1 transition-all duration-300',
    isDisabled
      ? 'bg-zinc-800 ring-white/5 text-zinc-500 cursor-not-allowed'
      : 'bg-zinc-900 ring-white/10 hover:ring-white/20 text-white hover:scale-105 cursor-pointer'
  ]">
    <!-- Icono -->
    <div v-if="isEmoji" class="text-4xl mb-2">
      {{ icon }}
    </div>
    <Icon v-else :icon="icon" class="w-10 h-10 mb-2" />

    <!-- Título -->
    <h3 class="font-bold text-xs md:text-sm">{{ title }}</h3>

    <!-- Descripción (opcional) -->
    <p v-if="description" class="text-xs opacity-75 mt-1">{{ description }}</p>

    <!-- Badge "Proximamente" si está deshabilitado -->
    <div v-if="isDisabled" class="mt-2 text-xs bg-zinc-700/50 px-2 py-1 rounded">
      Proximamente
    </div>
  </button>
</template>

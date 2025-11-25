<!-- resources/js/Pages/Public/Events/Index.vue -->
<script setup>
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import EventBanner from '@/Components/Public/Events/EventBanner.vue'
import EventCarousel from '@/Components/Public/Events/EventCarousel.vue'
import EventModal from '@/Components/Public/Events/EventModal.vue'

// Si tienes un layout público global
import PublicLayout from '@/Layouts/PublicLayout.vue'
defineOptions({ layout: PublicLayout })

const props = defineProps({
  upcomingEvents: { type: Object, required: true }, // próximos eventos
  pastEvents: { type: Object, required: true },     // eventos anteriores
  banner: { type: Object, default: () => ({}) },
})

// Modal state
const showModal = ref(false)
const selectedEvent = ref(null)

// Separar eventos en próximos y pasados
const upcomingList = computed(() => {
  return Array.isArray(props.upcomingEvents) ? props.upcomingEvents : (props.upcomingEvents.data ?? [])
})

const pastList = computed(() => {
  return Array.isArray(props.pastEvents) ? props.pastEvents : (props.pastEvents.data ?? [])
})

// Abrir modal
const openEventModal = (event) => {
  selectedEvent.value = event
  showModal.value = true
}

// Cerrar modal
const closeEventModal = () => {
  showModal.value = false
  selectedEvent.value = null
}
</script>

<template>
  <Head title="Eventos — Dilo Records" />

  <!-- Banner -->
  <EventBanner
    :title="banner.title ?? 'Únete a nuestros'"
    :highlight="banner.highlight ?? 'EVENTOS'"
    :cta="banner.cta ?? 'Descubre más'"
    :image="banner.image ?? '/images/events-banner.webp'"
  />

  <!-- Próximos eventos -->
  <EventCarousel
    :events="upcomingEvents"
    title="Próximos Eventos"
    @select="openEventModal"
  />

  <!-- Eventos anteriores -->
  <EventCarousel
    :events="pastEvents"
    title="Eventos Anteriores"
    @select="openEventModal"
  />

  <!-- Modal de evento -->
  <EventModal
    v-if="selectedEvent"
    :event="selectedEvent"
    :is-open="showModal"
    @close="closeEventModal"
  />
</template>

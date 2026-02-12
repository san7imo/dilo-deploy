<!-- resources/js/Components/Public/Events/EventCarousel.vue -->
<script setup>
import { computed } from 'vue'
import EventCard from './EventCard.vue'
import PaginationLinks from '@/Components/PaginationLinks.vue'

const props = defineProps({
  events: { type: Object, required: true }, // paginator o array
  title: { type: String, default: 'Eventos' },
})

const emit = defineEmits(['select'])

// Soporta paginator { data: [...] } o array
const eventList = computed(() => {
  return Array.isArray(props.events) ? props.events : (props.events.data ?? [])
})
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h2 class="text-white text-2xl md:text-3xl font-bold mb-6">{{ title }}</h2>

    <div v-if="eventList.length === 0" class="text-center text-zinc-400 py-12">
      <p>No hay eventos disponibles en este momento</p>
    </div>

    <div v-else class="relative">
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
      >
        <EventCard
          v-for="event in eventList"
          :key="event.id"
          :event="event"
          @select="emit('select', $event)"
        />
      </div>

      <PaginationLinks
        v-if="events && events.links"
        :links="events.links"
        :meta="events.meta"
        class="justify-center mt-8"
      />
    </div>
  </section>
</template>

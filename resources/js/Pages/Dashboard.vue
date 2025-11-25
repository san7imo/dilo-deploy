<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, onMounted } from "vue";
import axios from "axios";

const stats = ref({
  artists: 0,
  releases: 0,
  tracks: 0,
  genres: 0,
  events: [],
});

onMounted(async () => {
  const { data } = await axios.get("/admin/dashboard/data");
  stats.value = data;
});
</script>

<template>
  <AdminLayout>
    <div class="space-y-8">
      <!-- Título -->
      <div>
        <h1 class="text-2xl font-bold text-[#ffa236] mb-2">
          Panel principal
        </h1>
        <p class="text-gray-400 text-sm">
          Resumen general del catálogo y actividades de Dilo Records.
        </p>
      </div>

      <!-- Estadísticas principales -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
          v-for="(value, key) in {
            'Artistas': stats.artists,
            'Lanzamientos': stats.releases,
            'Tracks': stats.tracks,
            'Géneros': stats.genres
          }"
          :key="key"
          class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-5 text-center hover:border-[#ffa236]/40 transition-colors"
        >
          <p class="text-gray-400 text-sm">{{ key }}</p>
          <h2 class="text-3xl font-bold text-[#ffa236] mt-2">{{ value }}</h2>
        </div>
      </div>

      <!-- Próximos eventos -->
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6">
        <h2 class="text-lg font-semibold text-[#ffa236] mb-4">
          Próximos eventos
        </h2>

        <ul v-if="stats.events.length" class="divide-y divide-[#2a2a2a]">
          <li
            v-for="event in stats.events"
            :key="event.id"
            class="py-3 flex justify-between text-sm text-gray-300"
          >
            <span>{{ event.title }}</span>
            <span class="text-gray-500">
              {{ new Date(event.event_date).toLocaleDateString() }}
            </span>
          </li>
        </ul>

        <p v-else class="text-gray-500 text-sm">No hay eventos próximos.</p>
      </div>

      <!-- Información general -->
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6 text-gray-400 text-sm leading-relaxed">
        <p>
          Desde este panel puedes gestionar toda la información de Dilo Records:
          artistas, lanzamientos, géneros, pistas y eventos.
        </p>
        <p class="mt-3">
          En fases futuras se incluirán reportes de regalías, gestión de documentos,
          estadísticas financieras y carga de archivos por artista.
        </p>
      </div>
    </div>
  </AdminLayout>
</template>

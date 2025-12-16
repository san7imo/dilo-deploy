<script setup>
import ArtistLayout from "@/Layouts/ArtistLayout.vue";
import { ref, onMounted } from "vue";
import axios from "axios";

defineOptions({ layout: ArtistLayout });

const events = ref([]);
const loading = ref(false);

onMounted(async () => {
    loading.value = true;
    const { data } = await axios.get("/artist/events");
    events.value = data.events || [];
    loading.value = false;
});
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-[#ffa236] mb-2">Panel del artista</h1>
            <p class="text-gray-400 text-sm">
                Accede a tu perfil, canciones, lanzamientos y eventos desde la barra lateral.
            </p>
        </div>

        <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6">
            <h2 class="text-lg font-semibold text-[#ffa236] mb-4">Próximos eventos</h2>
            <div v-if="loading" class="text-gray-400 text-sm">Cargando...</div>
            <ul v-else-if="events.length" class="divide-y divide-[#2a2a2a]">
                <li v-for="event in events" :key="event.id" class="py-3 flex justify-between text-sm text-gray-300">
                    <span>{{ event.title }}</span>
                    <span class="text-gray-500">
                        {{ new Date(event.event_date).toLocaleDateString() }}
                    </span>
                </li>
            </ul>
            <p v-else class="text-gray-500 text-sm">No hay eventos próximos.</p>
        </div>
    </div>
</template>

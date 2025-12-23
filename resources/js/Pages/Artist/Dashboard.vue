<script setup>
import ArtistLayout from "@/Layouts/ArtistLayout.vue";
import { onMounted, ref } from "vue";
import axios from "axios";
import { Link } from "@inertiajs/vue3";

defineOptions({ layout: ArtistLayout });

const events = ref([]);
const loading = ref(false);
const error = ref("");

const formatDate = (date) => {
    if (!date) return "-";
    return new Date(date).toLocaleDateString("es-ES", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

onMounted(async () => {
    loading.value = true;
    error.value = "";

    try {
        const { data } = await axios.get("/artist/dashboard/data");
        events.value = data.events || [];
    } catch (err) {
        error.value = "No pudimos cargar tus próximos eventos. Intenta nuevamente.";
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="space-y-6" data-page="artist-dashboard">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#ffa236] mb-2">Dashboard del artista</h1>
                <p class="text-gray-400 text-sm">Visión rápida de tus próximos eventos.</p>
            </div>
            <Link :href="route('artist.finances.index')" class="text-sm px-4 py-2 rounded-md bg-[#ffa236] text-black font-semibold hover:bg-[#ffb54d] transition">
                Ver finanzas completas
            </Link>
        </div>

        <div v-if="error" class="bg-red-900/40 border border-red-800 text-red-200 text-sm p-4 rounded-lg">
            {{ error }}
        </div>

        <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4 flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-xs uppercase">Próximos eventos</p>
                <p class="text-2xl font-bold">{{ events.length }}</p>
            </div>
            <span v-if="loading" class="text-gray-400 text-sm">Cargando...</span>
        </div>

        <!-- Próximos Eventos -->
        <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-[#ffa236]">Tus próximos eventos</h2>
                    <p class="text-gray-400 text-sm">Revisa fechas y estado de cobro rápidamente.</p>
                </div>
                <span v-if="loading" class="text-gray-400 text-sm">Cargando...</span>
            </div>

            <div v-if="!loading && (!events || events.length === 0)" class="text-gray-500 text-sm">
                No hay eventos próximos.
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="event in events" :key="event.id"
                    class="bg-[#111111] border border-[#2a2a2a] rounded-lg p-4 flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">
                                {{ formatDate(event.event_date) }}
                            </p>
                            <h3 class="text-white font-semibold text-lg">{{ event.title }}</h3>
                            <p v-if="event.location" class="text-gray-500 text-sm">{{ event.location }}</p>
                        </div>
                        <span :class="[
                            'px-3 py-1 text-xs font-semibold rounded-full capitalize',
                            event.status === 'pagado'
                                ? 'bg-green-500/20 text-green-300 border border-green-500/40'
                                : 'bg-yellow-500/20 text-yellow-300 border border-yellow-500/40'
                        ]">
                            {{ event.status === 'pagado' ? 'Pagado' : 'Pendiente' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-md p-3 col-span-2">
                            <p class="text-gray-400 text-xs">Fecha</p>
                            <p class="text-white font-semibold">{{ formatDate(event.event_date) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

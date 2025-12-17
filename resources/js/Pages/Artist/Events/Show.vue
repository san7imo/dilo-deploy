<script setup>
import ArtistLayout from '@/Layouts/ArtistLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    event: { type: Object, required: true },
    finance: { type: Object, default: () => ({}) },
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <ArtistLayout>
        <div class="max-w-4xl mx-auto">
            <!-- Botón volver -->
            <Link :href="route('artist.events.index')"
                class="inline-flex items-center gap-2 text-[#ffa236] hover:text-[#ffb54d] mb-6 transition-colors">
                <i class="fa-solid fa-arrow-left"></i>
                Volver a mis eventos
            </Link>

            <!-- Detalles del evento -->
            <div class="bg-[#1d1d1b] rounded-lg border border-[#2a2a2a] overflow-hidden">
                <div class="p-8">
                    <h1 class="text-3xl font-bold text-white mb-6">{{ event.title }}</h1>

                    <div class="space-y-4">
                        <!-- Fecha -->
                        <div class="flex items-start gap-4">
                            <div
                                class="flex-shrink-0 w-12 h-12 bg-[#ffa236] rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-calendar text-black text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-400">Fecha</p>
                                <p class="text-white font-medium">{{ formatDate(event.event_date) }}</p>
                                <p class="text-gray-300">{{ formatTime(event.event_date) }}</p>
                            </div>
                        </div>

                        <!-- Ubicación -->
                        <div v-if="event.location" class="flex items-start gap-4">
                            <div
                                class="flex-shrink-0 w-12 h-12 bg-[#ffa236] rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-location-dot text-black text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-400">Ubicación</p>
                                <p class="text-white font-medium">{{ event.location }}</p>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div v-if="event.description" class="flex items-start gap-4">
                            <div
                                class="flex-shrink-0 w-12 h-12 bg-[#ffa236] rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-align-left text-black text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-400 mb-2">Descripción</p>
                                <p class="text-white whitespace-pre-line">{{ event.description }}</p>
                            </div>
                        </div>

                        <!-- Artistas participantes -->
                        <div v-if="event.artists && event.artists.length > 0" class="flex items-start gap-4">
                            <div
                                class="flex-shrink-0 w-12 h-12 bg-[#ffa236] rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-users text-black text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-400 mb-2">Artistas participantes</p>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="artist in event.artists" :key="artist.id"
                                        class="px-3 py-1 bg-[#2a2a2a] rounded-full text-sm text-white">
                                        {{ artist.name }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Resumen financiero -->
                        <div class="mt-6 bg-[#1d1d1b] rounded-lg border border-[#2a2a2a] p-6">
                            <h2 class="text-xl font-semibold text-white mb-4">Resumen financiero</h2>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="bg-[#141414] rounded-lg p-4 border border-[#2a2a2a]">
                                    <p class="text-gray-400 text-sm">Total pagado</p>
                                    <p class="text-white text-lg font-bold">
                                        € {{ (finance.total_paid_base ?? 0).toFixed(2) }}
                                    </p>
                                </div>

                                <div class="bg-[#141414] rounded-lg p-4 border border-[#2a2a2a]">
                                    <p class="text-gray-400 text-sm">Anticipo pagado</p>
                                    <p class="text-white text-lg font-bold">
                                        € {{ (finance.advance_paid_base ?? 0).toFixed(2) }}
                                    </p>
                                </div>

                                <div class="bg-[#141414] rounded-lg p-4 border border-[#2a2a2a]">
                                    <p class="text-gray-400 text-sm">Tu 70% estimado</p>
                                    <p class="text-white text-lg font-bold">
                                        € {{ (finance.artist_share_estimated_base ?? 0).toFixed(2) }}
                                    </p>
                                    <p class="text-gray-500 text-xs mt-1">(sin descontar gastos aún)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ArtistLayout>
</template>
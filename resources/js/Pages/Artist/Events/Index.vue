<script setup>
import ArtistLayout from '@/Layouts/ArtistLayout.vue';
import { Link } from '@inertiajs/vue3';

// Recibir los eventos directamente desde Inertia
defineProps({
    events: {
        type: Array,
        default: () => []
    }
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<template>
    <ArtistLayout>
        <div class="max-w-7xl mx-auto">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-white mb-2">Mis Eventos</h1>
                <p class="text-gray-400">Eventos en los que participas</p>
            </div>

            <!-- No hay eventos -->
            <div v-if="events.length === 0" class="bg-[#1d1d1b] rounded-lg p-8 text-center">
                <i class="fa-solid fa-calendar-days text-6xl text-gray-600 mb-4"></i>
                <p class="text-gray-400 text-lg">No tienes eventos próximos</p>
            </div>

            <!-- Lista de eventos -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link v-for="event in events" :key="event.id" :href="route('artist.events.show', event.id)"
                    class="bg-[#1d1d1b] rounded-lg p-6 border border-[#2a2a2a] hover:border-[#ffa236] transition-all cursor-pointer">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 bg-[#ffa236] rounded-lg p-3 text-center min-w-[60px]">
                            <div class="text-2xl font-bold text-black">
                                {{ new Date(event.event_date).getDate() }}
                            </div>
                            <div class="text-xs text-black uppercase">
                                {{ new Date(event.event_date).toLocaleDateString('es-ES', { month: 'short' }) }}
                            </div>
                        </div>

                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-white mb-2">{{ event.title }}</h3>
                            <p class="text-sm text-gray-400 mb-1">
                                <i class="fa-solid fa-calendar mr-2"></i>
                                {{ formatDate(event.event_date) }}
                            </p>
                            <p v-if="event.location" class="text-sm text-gray-400">
                                <i class="fa-solid fa-location-dot mr-2"></i>
                                {{ event.location }}
                            </p>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </ArtistLayout>
</template>
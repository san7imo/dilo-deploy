<script setup>
import ArtistLayout from '@/Layouts/ArtistLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    event: { type: Object, required: true },
    finance: { type: Object, default: () => ({}) },
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatCurrency = (value) => {
    return `EUR ${Number(value ?? 0).toFixed(2)}`;
};

const isPaid = () => {
    if (props.event && typeof props.event.is_paid !== "undefined") {
        return !!props.event.is_paid;
    }
    return Number(props.finance?.total_paid_base ?? 0) > 0;
};
</script>

<template>
    <ArtistLayout>
        <div class="max-w-4xl mx-auto">
            <Link
                :href="route('artist.events.index')"
                class="inline-flex items-center gap-2 text-[#ffa236] hover:text-[#ffb54d] mb-6 transition-colors"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Volver a mis eventos
            </Link>

            <div class="bg-[#1d1d1b] rounded-lg border border-[#2a2a2a] overflow-hidden">
                <div class="p-8 space-y-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">
                                {{ formatDate(event.event_date) }}
                            </p>
                            <h1 class="text-3xl font-bold text-white mt-1">{{ event.title }}</h1>
                            <p v-if="event.location" class="text-sm text-gray-400 mt-1">{{ event.location }}</p>
                        </div>
                        <span
                            :class="[
                                'px-3 py-1 text-xs font-semibold rounded-full capitalize',
                                isPaid()
                                    ? 'bg-green-500/20 text-green-300 border border-green-500/40'
                                    : 'bg-yellow-500/20 text-yellow-300 border border-yellow-500/40',
                            ]"
                        >
                            {{ isPaid() ? 'Pagado' : 'Pendiente' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-[#111111] rounded-lg p-4 border border-[#2a2a2a]">
                            <p class="text-gray-400 text-sm">Total pagado</p>
                            <p class="text-white text-xl font-bold">{{ formatCurrency(finance.total_paid_base) }}</p>
                        </div>
                        <div class="bg-[#111111] rounded-lg p-4 border border-[#2a2a2a]">
                            <p class="text-gray-400 text-sm">Anticipo pagado</p>
                            <p class="text-white text-xl font-bold">{{ formatCurrency(finance.advance_paid_base) }}</p>
                        </div>
                        <div class="bg-[#111111] rounded-lg p-4 border border-[#2a2a2a]">
                            <p class="text-gray-400 text-sm">Gastos</p>
                            <p class="text-red-400 text-xl font-bold">{{ formatCurrency(finance.total_expenses_base) }}</p>
                        </div>
                        <div class="bg-[#111111] rounded-lg p-4 border border-[#2a2a2a]">
                            <p class="text-gray-400 text-sm">Resultado neto</p>
                            <p class="text-white text-xl font-bold">{{ formatCurrency(finance.net_base) }}</p>
                        </div>
                        <div class="bg-[#111111] rounded-lg p-4 border border-[#2a2a2a] sm:col-span-3 grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-gray-400 text-sm">30% Dilo</p>
                                <p class="text-gray-100 text-xl font-bold">
                                    {{ formatCurrency(finance.label_share_estimated_base) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">70% Artista</p>
                                <p class="text-[#ffa236] text-xl font-bold">
                                    {{ formatCurrency(finance.artist_share_estimated_base) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-[#ffa236] rounded-lg flex items-center justify-center">
                                    <i class="fa-solid fa-calendar text-black text-lg"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-400">Fecha</p>
                                    <p class="text-white font-medium">{{ formatDate(event.event_date) }}</p>
                                    <p class="text-gray-300">{{ formatTime(event.event_date) }}</p>
                                </div>
                            </div>

                            <div v-if="event.location" class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-[#ffa236] rounded-lg flex items-center justify-center">
                                    <i class="fa-solid fa-location-dot text-black text-lg"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-400">Ubicación</p>
                                    <p class="text-white font-medium">{{ event.location }}</p>
                                </div>
                            </div>

                            <div v-if="event.description" class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-[#ffa236] rounded-lg flex items-center justify-center">
                                    <i class="fa-solid fa-align-left text-black text-lg"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-400 mb-1">Descripción</p>
                                    <p class="text-white whitespace-pre-line">{{ event.description }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="event.artists && event.artists.length" class="bg-[#111111] rounded-lg border border-[#2a2a2a] p-4">
                            <p class="text-sm text-gray-400 mb-3">Artistas participantes</p>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="artist in event.artists"
                                    :key="artist.id"
                                    class="px-3 py-1 bg-[#2a2a2a] rounded-full text-sm text-white"
                                >
                                    {{ artist.name }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ArtistLayout>
</template>

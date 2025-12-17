<script setup>
import ArtistLayout from '@/Layouts/ArtistLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    events: {
        type: Array,
        default: () => [],
    },
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatCurrency = (value) => {
    return `EUR ${Number(value ?? 0).toFixed(2)}`;
};

const hasEvents = computed(() => Array.isArray(props.events) && props.events.length > 0);
</script>

<template>
    <ArtistLayout>
        <div class="max-w-7xl mx-auto">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-white mb-2">Mis Eventos</h1>
                <p class="text-gray-400">Tu estado de pagos y fechas clave</p>
            </div>

            <div v-if="!hasEvents" class="bg-[#1d1d1b] rounded-lg p-8 text-center">
                <i class="fa-solid fa-calendar-days text-6xl text-gray-600 mb-4"></i>
                <p class="text-gray-400 text-lg">No tienes eventos registrados aún</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link
                    v-for="event in props.events"
                    :key="event.id"
                    :href="route('artist.events.show', event.id)"
                    class="bg-[#1d1d1b] rounded-lg p-5 border border-[#2a2a2a] hover:border-[#ffa236] transition-all cursor-pointer flex flex-col gap-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">
                                {{ formatDate(event.event_date) }}
                            </p>
                            <h3 class="text-lg font-semibold text-white">{{ event.title }}</h3>
                            <p v-if="event.location" class="text-sm text-gray-500">{{ event.location }}</p>
                        </div>
                        <span
                            :class="[
                                'px-3 py-1 text-xs font-semibold rounded-full capitalize',
                                event.status === 'pagado'
                                    ? 'bg-green-500/20 text-green-300 border border-green-500/40'
                                    : 'bg-yellow-500/20 text-yellow-300 border border-yellow-500/40',
                            ]"
                        >
                            {{ event.status === 'pagado' ? 'Pagado' : 'Pendiente' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="bg-[#111111] rounded-md p-3 border border-[#2a2a2a]">
                            <p class="text-gray-400 text-xs">Total ingresado</p>
                            <p class="text-white font-semibold">{{ formatCurrency(event.total_paid_base) }}</p>
                        </div>
                        <div class="bg-[#111111] rounded-md p-3 border border-[#2a2a2a]">
                            <p class="text-gray-400 text-xs">Tu 70% estimado</p>
                            <p class="text-[#ffa236] font-semibold">
                                {{ formatCurrency(event.artist_share_estimated_base) }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs text-gray-400">
                        <div class="bg-[#111111] rounded-md p-3 border border-[#2a2a2a]">
                            <p class="text-gray-400 text-[11px]">Gastos</p>
                            <p class="text-red-400 font-semibold">{{ formatCurrency(event.total_expenses_base) }}</p>
                        </div>
                        <div class="bg-[#111111] rounded-md p-3 border border-[#2a2a2a]">
                            <p class="text-gray-400 text-[11px]">Resultado neto</p>
                            <p class="text-white font-semibold">{{ formatCurrency(event.net_base) }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-400">
                        <span>Anticipo: {{ formatCurrency(event.advance_paid_base) }}</span>
                        <span class="text-gray-500">{{ event.is_upcoming ? 'Próximo' : 'Pasado' }}</span>
                    </div>
                </Link>
            </div>
        </div>
    </ArtistLayout>
</template>

<script setup>
import ArtistLayout from '@/Layouts/ArtistLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { formatMoney } from '@/utils/money';
import PaginationLinks from '@/Components/PaginationLinks.vue';

const props = defineProps({
    events: {
        type: Object,
        default: () => ({ data: [] }),
    },
});

const filterType = ref('todos');

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatCurrency = (value) => {
    return formatMoney(value, "USD");
};

const eventList = computed(() => Array.isArray(props.events) ? props.events : (props.events?.data ?? []));

const filteredEvents = computed(() => {
    if (filterType.value === 'todos') {
        return eventList.value;
    } else if (filterType.value === 'proximos') {
        return eventList.value.filter(event => event.is_upcoming);
    } else if (filterType.value === 'pasados') {
        return eventList.value.filter(event => !event.is_upcoming);
    }
    return eventList.value;
});

const hasEvents = computed(() => Array.isArray(filteredEvents.value) && filteredEvents.value.length > 0);
</script>

<template>
    <ArtistLayout>
        <div class="max-w-7xl mx-auto space-y-8">
            <div
                class="rounded-2xl bg-gradient-to-r from-[#1f1f1c] via-[#1a120d] to-[#101010] border border-[#2a2a26] px-6 py-5 shadow-lg">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase text-gray-500 tracking-[0.2em]">Agenda</p>
                        <h1 class="text-3xl font-bold text-white">Mis eventos</h1>
                        <p class="text-gray-400">Ingresos, fechas y detalles en un solo lugar.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            class="px-3 py-1 rounded-full text-xs font-semibold bg-[#ffa236]/10 text-[#ffa236] border border-[#ffa236]/30">
                            {{ filteredEvents.length }} evento{{ filteredEvents.length === 1 ? '' : 's' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 bg-[#111111] rounded-2xl p-2 border border-[#2a2a2a] w-fit">
                <button @click="filterType = 'todos'" :class="[
                    'px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200',
                    filterType === 'todos'
                        ? 'bg-[#ffa236] text-black shadow-lg'
                        : 'text-gray-400 hover:text-white hover:bg-[#1a1a1a]',
                ]">
                    Todos
                </button>
                <button @click="filterType = 'proximos'" :class="[
                    'px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200',
                    filterType === 'proximos'
                        ? 'bg-[#ffa236] text-black shadow-lg'
                        : 'text-gray-400 hover:text-white hover:bg-[#1a1a1a]',
                ]">
                    Próximos
                </button>
                <button @click="filterType = 'pasados'" :class="[
                    'px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200',
                    filterType === 'pasados'
                        ? 'bg-[#ffa236] text-black shadow-lg'
                        : 'text-gray-400 hover:text-white hover:bg-[#1a1a1a]',
                ]">
                    Pasados
                </button>
            </div>

            <div v-if="!hasEvents" class="bg-[#111111] rounded-2xl p-10 border border-[#2a2a2a] text-center">
                <div
                    class="mx-auto mb-4 h-12 w-12 rounded-2xl bg-[#ffa236]/10 border border-[#ffa236]/40 flex items-center justify-center text-[#ffa236] font-bold">
                    •
                </div>
                <p class="text-gray-300 text-lg">No tienes eventos registrados aún.</p>
                <p class="text-gray-500 text-sm">Cuando agendes un evento, verás aquí sus ingresos y fechas.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link v-for="event in filteredEvents" :key="event.id" :href="route('artist.events.show', event.id)"
                    class="group bg-gradient-to-br from-[#141414] via-[#0f0f0f] to-[#0b0b0b] rounded-2xl p-5 border border-[#222222] hover:border-[#ffa236]/60 hover:shadow-xl transition-all duration-200 flex flex-col gap-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <p class="text-[11px] tracking-wide uppercase text-gray-500">{{ formatDate(event.event_date)
                                }}</p>
                            <h3 class="text-lg font-semibold text-white leading-tight group-hover:text-[#ffa236]">{{
                                event.title }}</h3>
                            <div class="space-y-1 text-sm">
                                <p v-if="event.city || event.country" class="text-gray-400">
                                    {{ event.city }}{{ event.city && event.country ? ',' : '' }} {{ event.country }}
                                </p>
                                <p v-if="event.location" class="text-gray-300">{{ event.location }}</p>
                                <p v-if="event.venue_address" class="text-xs text-gray-500">{{ event.venue_address }}
                                </p>
                                <p v-else-if="!event.city && !event.country && event.location" class="text-gray-400">
                                    {{ event.location }}
                                </p>
                                <p v-if="event.event_type" class="text-xs text-gray-500 capitalize">
                                    {{ event.event_type }}
                                </p>
                            </div>
                        </div>
                        <span :class="[
                            'px-3 py-1 text-xs font-semibold rounded-full capitalize border',
                            event.status === 'pagado'
                                ? 'bg-green-500/15 text-green-200 border-green-500/30'
                                : 'bg-amber-500/15 text-amber-200 border-amber-500/30',
                        ]">
                            {{ event.status === 'pagado' ? 'Pagado' : 'Pendiente' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl p-3 border border-[#222222] bg-[#0e0e0e]">
                            <p class="text-gray-500 text-[11px]">Total ingresado</p>
                            <p class="text-white font-semibold">{{ formatCurrency(event.total_paid_base) }}</p>
                        </div>
                        <div class="rounded-xl p-3 border border-[#222222] bg-[#0e0e0e]">
                            <p class="text-gray-500 text-[11px]">Tu parte estimada</p>
                            <p class="text-[#ffa236] font-semibold">{{ formatCurrency(event.artist_share_estimated_base)
                                }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs text-gray-400">
                        <div class="rounded-xl p-3 border border-[#1c1c1c] bg-[#0b0b0b]">
                            <p class="text-[11px] text-gray-500">Gastos</p>
                            <p class="text-red-400 font-semibold">{{ formatCurrency(event.total_expenses_base) }}</p>
                        </div>
                        <div class="rounded-xl p-3 border border-[#1c1c1c] bg-[#0b0b0b]">
                            <p class="text-[11px] text-gray-500">Resultado neto</p>
                            <p class="text-white font-semibold">{{ formatCurrency(event.net_base) }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-400">
                        <span class="flex items-center gap-1">
                            Anticipo: <span class="text-white">{{ formatCurrency(event.advance_paid_base) }}</span>
                        </span>
                        <span class="text-gray-500">
                            {{ event.is_upcoming ? 'Próximo' : 'Pasado' }}
                        </span>
                    </div>
                </Link>
            </div>

            <PaginationLinks
                v-if="props.events && props.events.links"
                :links="props.events.links"
                :meta="props.events.meta"
                class="justify-center mt-8"
            />
        </div>
    </ArtistLayout>
</template>

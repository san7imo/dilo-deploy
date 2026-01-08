<script setup>
import ArtistLayout from "@/Layouts/ArtistLayout.vue";
import { computed, ref } from "vue";
import { Icon } from "@iconify/vue";
import FinanceCharts from "@/Components/Finance/FinanceCharts.vue";
import ArtistExpensesTable from "@/Components/Finance/ArtistExpensesTable.vue";

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({}),
    },
    events: {
        type: Array,
        default: () => [],
    },
});

// Filtros
const filterType = ref("all"); // 'all', 'year', 'month', 'paid', 'pending'
const filterYear = ref(null);
const filterMonth = ref(null);
const filterDateFrom = ref(null);
const filterDateTo = ref(null);

// Años disponibles
const availableYears = computed(() => {
    const years = new Set(
        props.events
            .map((e) => new Date(e.event_date).getFullYear())
            .filter((y) => y > 0)
    );
    return Array.from(years).sort((a, b) => b - a);
});

// Meses disponibles para el año seleccionado
const availableMonths = computed(() => {
    if (!filterYear.value) return [];
    const months = new Set(
        props.events
            .filter((e) => new Date(e.event_date).getFullYear() === filterYear.value)
            .map((e) => new Date(e.event_date).getMonth() + 1)
    );
    return Array.from(months).sort((a, b) => a - b);
});

const monthNames = [
    "Enero",
    "Febrero",
    "Marzo",
    "Abril",
    "Mayo",
    "Junio",
    "Julio",
    "Agosto",
    "Septiembre",
    "Octubre",
    "Noviembre",
    "Diciembre",
];

const pendingEventsCount = computed(() => {
    const total = props.summary.events_count || props.events.length || 0;
    const paid = props.summary.paid_events_count || 0;
    return Math.max(total - paid, 0);
});

const formatCurrency = (value) => {
    const amount = Number(value ?? 0);
    const currency = props.summary.currency || "USD";
    return `${currency} ${amount.toFixed(2)}`;
};

const formatDate = (date) => {
    if (!date) return "-";
    return new Date(date).toLocaleDateString("es-ES", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const totals = computed(() => ({
    paid: Number(props.summary?.total_paid_base ?? 0),
    expenses: Number(props.summary?.total_expenses_base ?? 0),
    artistPersonalExpenses: Number(props.summary?.artist_personal_expenses_base ?? 0),
    net: Number(props.summary?.net_base ?? 0),
    shareLabel: Number(props.summary?.label_share_estimated_base ?? 0),
    shareArtist: Number(props.summary?.artist_share_estimated_base ?? 0),
    shareArtistNet: Number(props.summary?.artist_net_share_base ?? 0),
}));

// Resetear mes cuando cambias año
const handleYearChange = () => {
    filterMonth.value = null;
};
</script>

<template>
    <ArtistLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-2xl font-bold text-[#ffa236] mb-1">Finanzas del artista</h1>
                    <p class="text-gray-400 text-sm">
                        Lo que tienes pagado, tu 70% estimado y el estado de cobro por evento.
                    </p>
                </div>
                <span class="text-sm text-gray-400">Total eventos: {{ summary.events_count || events.length }}</span>
            </div>

            <!-- Filtros -->
            <div
                class="bg-gradient-to-br from-[#1d1d1b] to-[#151512] border border-[#3a3a38] rounded-xl p-6 flex flex-wrap gap-4 shadow-lg">
                <div class="flex-1 min-w-[200px]">
                    <label class="text-[#ffa236] text-sm  mb-2 font-semibold flex items-center gap-2">
                        Filtrar por estado
                    </label>
                    <select v-model="filterType"
                        class="w-full bg-[#0f0f0d] border border-[#3a3a38] rounded-lg px-3 py-2 text-white text-sm hover:border-[#ffa236]/30 focus:border-[#ffa236] transition-colors">
                        <option value="all">Todos los eventos</option>
                        <option value="paid">Solo pagados</option>
                        <option value="pending">Solo pendientes</option>
                    </select>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <label class="text-[#ffa236] text-sm  mb-2 font-semibold flex items-center gap-2">
                        Año
                    </label>
                    <select v-model.number="filterYear" @change="handleYearChange"
                        class="w-full bg-[#0f0f0d] border border-[#3a3a38] rounded-lg px-3 py-2 text-white text-sm hover:border-[#ffa236]/30 focus:border-[#ffa236] transition-colors">
                        <option :value="null">Todos los años</option>
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>

                <div v-if="filterYear" class="flex-1 min-w-[200px]">
                    <label class="text-[#ffa236] text-sm  mb-2 font-semibold flex items-center gap-2">
                        Mes
                    </label>
                    <select v-model.number="filterMonth"
                        class="w-full bg-[#0f0f0d] border border-[#3a3a38] rounded-lg px-3 py-2 text-white text-sm hover:border-[#ffa236]/30 focus:border-[#ffa236] transition-colors">
                        <option :value="null">Todos los meses</option>
                        <option v-for="month in availableMonths" :key="month" :value="month">
                            {{ monthNames[month - 1] }}
                        </option>
                    </select>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <label class="text-[#ffa236] text-sm  mb-2 font-semibold flex items-center gap-2">
                        Desde
                    </label>
                    <input v-model="filterDateFrom" type="date"
                        class="w-full bg-[#0f0f0d] border border-[#3a3a38] rounded-lg px-3 py-2 text-white text-sm hover:border-[#ffa236]/30 focus:border-[#ffa236] transition-colors">
                </div>

                <div class="flex-1 min-w-[200px]">
                    <label class="text-[#ffa236] text-sm  mb-2 font-semibold flex items-center gap-2">
                        Hasta
                    </label>
                    <input v-model="filterDateTo" type="date"
                        class="w-full bg-[#0f0f0d] border border-[#3a3a38] rounded-lg px-3 py-2 text-white text-sm hover:border-[#ffa236]/30 focus:border-[#ffa236] transition-colors">
                </div>
            </div>

            <!-- Gráficas -->
            <FinanceCharts :totals="totals" :events="events" :filter-type="filterType" :filter-year="filterYear"
                :filter-month="filterMonth" :filter-date-from="filterDateFrom" :filter-date-to="filterDateTo"
                currency="$" />

            <!-- Resumen global de gastos personales -->
            <div v-if="totals.artistPersonalExpenses > 0" class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-[#ffa236]">Tus gastos personales</h2>
                        <p class="text-gray-400 text-sm">
                            Total de gastos personales registrados en todos los eventos
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-orange-400 text-2xl font-bold">
                            {{ formatCurrency(totals.artistPersonalExpenses) }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Se descuenta de tu 70%</p>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-r from-[#ffa236]/10 to-transparent border border-[#ffa236]/30 rounded-lg p-5 flex gap-4">
                    <Icon icon="mdi:lightbulb-outline" class="text-[#ffa236] text-2xl flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="text-sm font-semibold text-[#ffa236] mb-2">Nota sobre tus gastos personales</p>
                        <p class="text-sm text-gray-300 leading-relaxed">
                            Estos gastos corresponden a tus decisiones personales (alimentación, transporte personal,
                            recreación, etc.) durante el evento.
                        </p>
                        <p class="text-xs text-gray-400 mt-2 italic">Se descuentan únicamente de tu pago (70%), no
                            afectan la ganancia de la compañía.</p>
                    </div>
                </div>
            </div>

            <!-- Eventos -->
            <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-[#ffa236]">Eventos</h2>
                        <p class="text-gray-400 text-sm">Tu estado de pagos por evento</p>
                    </div>
                </div>

                <div v-if="!events || events.length === 0" class="text-gray-500 text-sm">
                    No hay eventos con finanzas registradas todavia.
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
                                <div class="space-y-1 text-sm">
                                    <p v-if="event.city || event.country" class="text-gray-500">
                                        {{ event.city }}{{ event.city && event.country ? ',' : '' }} {{ event.country
                                        }}
                                    </p>
                                    <p v-if="event.location" class="text-gray-400">{{ event.location }}</p>
                                    <p v-if="event.venue_address" class="text-xs text-gray-500">{{ event.venue_address
                                        }}</p>
                                    <p v-else-if="!event.city && !event.country && event.location"
                                        class="text-gray-500">
                                        {{ event.location }}
                                    </p>
                                </div>
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
                            <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-md p-3">
                                <p class="text-gray-400 text-xs">Total ingresado</p>
                                <p class="text-white font-semibold">{{ formatCurrency(event.total_paid_base) }}</p>
                            </div>
                            <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-md p-3">
                                <p class="text-gray-400 text-xs">Tu 70%</p>
                                <p class="text-[#ffa236] font-semibold">{{
                                    formatCurrency(event.artist_share_estimated_base) }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3 text-xs text-gray-400">
                            <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-md p-3">
                                <p class="text-gray-400 text-[11px]">Gastos evento</p>
                                <p class="text-red-400 font-semibold">{{ formatCurrency(event.total_expenses_base) }}
                                </p>
                            </div>
                            <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-md p-3">
                                <p class="text-gray-400 text-[11px]">Tus gastos</p>
                                <p class="text-orange-400 font-semibold">{{
                                    formatCurrency(event.artist_personal_expenses_base || 0) }}
                                </p>
                            </div>
                            <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-md p-3">
                                <p class="text-gray-400 text-[11px]">Tu pago neto</p>
                                <p class="text-green-400 font-semibold">{{ formatCurrency(event.artist_net_share_base ||
                                    event.artist_share_estimated_base) }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs text-gray-400">
                            <span>Anticipo: {{ formatCurrency(event.advance_paid_base) }}</span>
                            <span class="text-gray-500" v-if="event.is_upcoming">Proximo</span>
                            <span class="text-gray-500" v-else>Pasado</span>
                        </div>

                        <!-- Gastos personales del artista (si existen) -->
                        <div v-if="event.artist_personal_expenses && event.artist_personal_expenses.length > 0"
                            class="border-t border-[#2a2a2a] pt-3 mt-2">
                            <details class="text-sm">
                                <summary
                                    class="cursor-pointer text-gray-400 hover:text-white transition-colors flex items-center justify-between">
                                    <span>Ver tus gastos personales</span>
                                    <span class="text-xs text-orange-400 font-semibold">
                                        {{ formatCurrency(event.artist_personal_expenses_base || 0) }}
                                    </span>
                                </summary>
                                <div class="mt-3 space-y-2">
                                    <div v-for="expense in event.artist_personal_expenses" :key="expense.id"
                                        class="bg-[#0f0f0f] border border-[#2a2a2a] rounded p-2 text-xs">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1">
                                                <p class="text-white font-semibold">{{ expense.name }}</p>
                                                <p v-if="expense.description" class="text-gray-500 text-[11px] mt-1">
                                                    {{ expense.description }}
                                                </p>
                                                <p class="text-gray-400 text-[11px] mt-1">
                                                    {{ formatDate(expense.expense_date) }}
                                                    <span v-if="expense.category" class="ml-2 capitalize">
                                                        • {{ expense.category }}
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-orange-400 font-semibold">
                                                    {{ formatCurrency(expense.amount_base, 'USD') }}
                                                </p>
                                                <div v-if="expense.is_approved"
                                                    class="text-green-400 text-xs mt-1 flex items-center justify-end gap-1">
                                                    <Icon icon="mdi:check-circle" class="text-sm" />
                                                    <span>Aprobado</span>
                                                </div>
                                                <div v-else
                                                    class="text-yellow-400 text-xs mt-1 flex items-center justify-end gap-1">
                                                    <Icon icon="mdi:clock-outline" class="text-sm" />
                                                    <span>Pendiente</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-gray-500 text-[11px] mt-2 italic">
                                        * Estos gastos personales se descuentan de tu 70%
                                    </p>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ArtistLayout>
</template>

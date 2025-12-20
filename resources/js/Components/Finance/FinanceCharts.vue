<script setup>
import { computed } from "vue";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";
import BarChart from "@/Components/Charts/BarChart.vue";

const props = defineProps({
    totals: {
        type: Object,
        default: () => ({
            paid: 0,
            expenses: 0,
            net: 0,
            shareLabel: 0,
            shareArtist: 0,
        }),
    },
    events: {
        type: Array,
        default: () => [],
    },
    currency: {
        type: String,
        default: "€",
    },
    heightClass: {
        type: String,
        default: "h-80",
    },
    filterType: {
        type: String,
        default: "all", // 'all', 'year', 'month', 'paid', 'pending'
    },
    filterYear: {
        type: Number,
        default: null,
    },
    filterMonth: {
        type: Number,
        default: null,
    },
    filterDateFrom: {
        type: String,
        default: null,
    },
    filterDateTo: {
        type: String,
        default: null,
    },
});

// Filtrar eventos según criterios
const filteredEvents = computed(() => {
    if (!props.events || props.events.length === 0) {
        return [];
    }

    return props.events.filter((event) => {
        // Filtro por estado de pago
        if (props.filterType === "paid" && event.status !== "pagado") return false;
        if (props.filterType === "pending" && event.status !== "pendiente") return false;

        // Filtro por año
        if (props.filterYear) {
            const eventYear = new Date(event.event_date).getFullYear();
            if (eventYear !== props.filterYear) return false;
        }

        // Filtro por mes
        if (props.filterMonth) {
            const eventMonth = new Date(event.event_date).getMonth() + 1;
            if (eventMonth !== props.filterMonth) return false;
        }

        // Filtro por rango de fechas
        if (props.filterDateFrom || props.filterDateTo) {
            const eventDate = new Date(event.event_date);
            if (props.filterDateFrom) {
                const dateFrom = new Date(props.filterDateFrom);
                if (eventDate < dateFrom) return false;
            }
            if (props.filterDateTo) {
                const dateTo = new Date(props.filterDateTo);
                dateTo.setHours(23, 59, 59, 999);
                if (eventDate > dateTo) return false;
            }
        }

        return true;
    });
});

// Calcular totales filtrados
const filteredTotals = computed(() => {
    const filtered = filteredEvents.value;
    const paid = filtered.reduce((sum, e) => sum + Number(e.total_paid_base ?? 0), 0);
    const expenses = filtered.reduce((sum, e) => sum + Number(e.total_expenses_base ?? 0), 0);
    const net = paid - expenses;

    return {
        paid: Math.round(paid * 100) / 100,
        expenses: Math.round(expenses * 100) / 100,
        net: Math.round(net * 100) / 100,
        shareLabel: Math.round(net * 0.30 * 100) / 100,
        shareArtist: Math.round(net * 0.70 * 100) / 100,
    };
});

// Años disponibles
const availableYears = computed(() => {
    if (!props.events) return [];
    const years = new Set(
        props.events
            .map((e) => new Date(e.event_date).getFullYear())
            .filter((y) => y > 0)
    );
    return Array.from(years).sort((a, b) => b - a);
});

// Meses disponibles para el año seleccionado
const availableMonths = computed(() => {
    if (!props.filterYear || !props.events) return [];
    const months = new Set(
        props.events
            .filter((e) => new Date(e.event_date).getFullYear() === props.filterYear)
            .map((e) => new Date(e.event_date).getMonth() + 1)
    );
    return Array.from(months).sort((a, b) => a - b);
});

const chartIncomeVsExpense = computed(() => ({
    labels: ["Ingresos", "Gastos", "Resultado neto (>=0)"],
    values: [
        Number(filteredTotals.value.paid ?? 0),
        Number(filteredTotals.value.expenses ?? 0),
        Math.max(Number(filteredTotals.value.net ?? 0), 0),
    ],
    colors: ["#22c55e", "#ef4444", "#ffa236"],
}));

const chartSplit = computed(() => {
    const net = Math.max(Number(filteredTotals.value.net ?? 0), 0);
    const shareLabel = filteredTotals.value.shareLabel ?? net * 0.30;
    const shareArtist = filteredTotals.value.shareArtist ?? net * 0.70;
    return {
        labels: ["30% Dilo", "70% Artista"],
        values: [shareLabel, shareArtist],
        colors: ["#38bdf8", "#ffa236"],
    };
});

// Gráfica de barras por evento - últimos 10 eventos filtrados
const chartEventComparison = computed(() => {
    const toShow = filteredEvents.value.slice(0, 10).reverse();

    if (toShow.length === 0) {
        return {
            labels: [],
            datasets: [],
        };
    }

    return {
        labels: toShow.map((e) => e.title?.substring(0, 15) || "Sin título"),
        datasets: [
            {
                label: "Ingresos",
                data: toShow.map((e) => Number(e.total_paid_base ?? 0)),
                backgroundColor: "#22c55e",
                borderRadius: 4,
            },
            {
                label: "Gastos",
                data: toShow.map((e) => Number(e.total_expenses_base ?? 0)),
                backgroundColor: "#ef4444",
                borderRadius: 4,
            },
            {
                label: "Neto",
                data: toShow.map((e) => Math.max(Number(e.net_base ?? 0), 0)),
                backgroundColor: "#ffa236",
                borderRadius: 4,
            },
        ],
    };
});

const format = (value) => `${props.currency} ${Number(value ?? 0).toFixed(2)}`;

// Computed para mostrar etiqueta del filtro aplicado
const filterLabel = computed(() => {
    const labels = [];

    if (props.filterType !== "all") {
        labels.push(props.filterType === "paid" ? "Solo pagados" : "Solo pendientes");
    }
    if (props.filterYear) {
        labels.push(String(props.filterYear));
    }
    if (props.filterMonth) {
        labels.push(monthNames[props.filterMonth - 1]);
    }
    if (props.filterDateFrom) {
        labels.push(`desde ${props.filterDateFrom}`);
    }
    if (props.filterDateTo) {
        labels.push(`hasta ${props.filterDateTo}`);
    }

    return labels.length > 0 ? labels.join(" | ") : "Sin filtros";
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
</script>

<template>
    <div class="space-y-6">
        <!-- Indicador de filtros activos - Más visual -->
        <div v-if="filterLabel !== 'Sin filtros'"
            class="bg-gradient-to-r from-[#ffa236]/10 via-[#ffa236]/5 to-transparent border border-[#ffa236]/30 rounded-lg p-4 flex items-center gap-3">
            <span class="text-[#ffa236] text-lg">•</span>
            <p class="text-gray-300 text-sm"><span class="text-[#ffa236] font-semibold">Filtros activos:</span> {{
                filterLabel }}</p>
        </div>

        <!-- Grid: 2 Donuts + 1 Barra -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Donut 1: Ingresos vs Gastos -->
            <div
                class="bg-gradient-to-br from-[#1a1a18] via-[#0f0f0d] to-[#0a0a08] border border-[#3a3a38] rounded-xl p-6 shadow-2xl hover:shadow-3xl hover:border-[#ffa236]/30 transition-all duration-300">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-1">
                        Ingresos vs Gastos
                    </h3>
                    <div class="h-0.5 w-12 bg-gradient-to-r from-[#ffa236] to-transparent rounded"></div>
                </div>
                <DoughnutChart :labels="chartIncomeVsExpense.labels" :values="chartIncomeVsExpense.values"
                    :colors="chartIncomeVsExpense.colors" :height-class="heightClass" />
                <div class="mt-5 grid grid-cols-3 gap-3">
                    <div
                        class="bg-[#0f0f0d]/50 border border-green-500/20 rounded-lg p-3 hover:bg-[#0f0f0d]/80 hover:border-green-500/40 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">Ingresos</p>
                        <p class="text-base font-bold text-green-400 mt-1">{{ format(filteredTotals.paid) }}</p>
                    </div>
                    <div
                        class="bg-[#0f0f0d]/50 border border-red-500/20 rounded-lg p-3 hover:bg-[#0f0f0d]/80 hover:border-red-500/40 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">Gastos</p>
                        <p class="text-base font-bold text-red-400 mt-1">{{ format(filteredTotals.expenses) }}</p>
                    </div>
                    <div
                        class="bg-[#0f0f0d]/50 border border-[#ffa236]/20 rounded-lg p-3 hover:bg-[#0f0f0d]/80 hover:border-[#ffa236]/40 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">Neto</p>
                        <p class="text-base font-bold text-[#ffa236] mt-1">{{ format(filteredTotals.net) }}</p>
                    </div>
                </div>
            </div>

            <!-- Donut 2: Reparto 30/70 -->
            <div
                class="bg-gradient-to-br from-[#1a1a18] via-[#0f0f0d] to-[#0a0a08] border border-[#3a3a38] rounded-xl p-6 shadow-2xl hover:shadow-3xl hover:border-[#ffa236]/30 transition-all duration-300">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-1">
                        Distribución (30/70)
                    </h3>
                    <div class="h-0.5 w-12 bg-gradient-to-r from-[#ffa236] to-transparent rounded"></div>
                </div>
                <DoughnutChart :labels="chartSplit.labels" :values="chartSplit.values" :colors="chartSplit.colors"
                    :height-class="heightClass" />
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div
                        class="bg-gradient-to-br from-sky-500/10 to-sky-500/5 border border-sky-500/30 rounded-lg p-3 hover:border-sky-500/60 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">Dilo (30%)</p>
                        <p class="text-base font-bold text-sky-300 mt-1">{{ format(filteredTotals.shareLabel) }}</p>
                    </div>
                    <div
                        class="bg-gradient-to-br from-[#ffa236]/10 to-[#ffa236]/5 border border-[#ffa236]/30 rounded-lg p-3 hover:border-[#ffa236]/60 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">Artista (70%)</p>
                        <p class="text-base font-bold text-[#ffa236] mt-1">{{ format(filteredTotals.shareArtist) }}</p>
                    </div>
                </div>
            </div>

            <!-- Barra: Comparación por evento -->
            <div v-if="filteredEvents && filteredEvents.length > 0"
                class="bg-gradient-to-br from-[#1a1a18] via-[#0f0f0d] to-[#0a0a08] border border-[#3a3a38] rounded-xl p-6 shadow-2xl hover:shadow-3xl hover:border-[#ffa236]/30 transition-all duration-300">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-1">
                        Comparativa por Evento
                    </h3>
                    <div class="h-0.5 w-12 bg-gradient-to-r from-[#ffa236] to-transparent rounded"></div>
                </div>
                <BarChart :labels="chartEventComparison.labels" :datasets="chartEventComparison.datasets"
                    :height-class="heightClass" />
                <div class="mt-5 grid grid-cols-3 gap-3">
                    <div
                        class="bg-[#0f0f0d]/50 border border-green-500/20 rounded-lg p-3 hover:bg-[#0f0f0d]/80 hover:border-green-500/40 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">Ingresos</p>
                        <p class="text-base font-bold text-green-400 mt-1">{{ format(filteredTotals.paid) }}</p>
                    </div>
                    <div
                        class="bg-[#0f0f0d]/50 border border-red-500/20 rounded-lg p-3 hover:bg-[#0f0f0d]/80 hover:border-red-500/40 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">Gastos</p>
                        <p class="text-base font-bold text-red-400 mt-1">{{ format(filteredTotals.expenses) }}</p>
                    </div>
                    <div
                        class="bg-[#0f0f0d]/50 border border-[#ffa236]/20 rounded-lg p-3 hover:bg-[#0f0f0d]/80 hover:border-[#ffa236]/40 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">Neto</p>
                        <p class="text-base font-bold text-[#ffa236] mt-1">{{ format(filteredTotals.net) }}</p>
                    </div>
                </div>
            </div>

            <!-- Mensaje si no hay eventos -->
            <div v-else
                class="bg-gradient-to-br from-[#1a1a18] via-[#0f0f0d] to-[#0a0a08] border border-[#3a3a38] rounded-xl p-6 flex items-center justify-center col-span-1 lg:col-span-3">
                <div class="text-center">
                    <p class="text-gray-500 text-sm">No hay eventos para mostrar con los filtros seleccionados</p>
                </div>
            </div>
        </div>
    </div>
</template>

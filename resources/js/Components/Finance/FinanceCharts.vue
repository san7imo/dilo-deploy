<script setup>
import { computed } from "vue";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";
import BarChart from "@/Components/Charts/BarChart.vue";
import { formatMoneyWithSymbol } from "@/utils/money";

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
        default: "$",
    },
    heightClass: {
        type: String,
        default: "h-80",
    },
});

const filteredEvents = computed(() => (props.events && props.events.length ? props.events : []));

const toNumber = (value) => {
    const parsed = Number(value ?? 0);
    return Number.isFinite(parsed) ? parsed : 0;
};
const getNetBase = (event) => Math.max(toNumber(event?.net_base), 0);
const getArtistPct = (event) => {
    const artistNum = Number(event?.artist_share_percentage);
    if (Number.isFinite(artistNum)) return artistNum;
    const labelNum = Number(event?.label_share_percentage);
    if (Number.isFinite(labelNum)) return Math.max(0, 100 - labelNum);
    return 70;
};
const getLabelPct = (event) => {
    const labelNum = Number(event?.label_share_percentage);
    if (Number.isFinite(labelNum)) return labelNum;
    const artistNum = Number(event?.artist_share_percentage);
    if (Number.isFinite(artistNum)) return Math.max(0, 100 - artistNum);
    return 30;
};
const getArtistShare = (event) => {
    if (event?.artist_share_estimated_base !== undefined && event?.artist_share_estimated_base !== null) {
        return Math.max(toNumber(event.artist_share_estimated_base), 0);
    }
    const netBase = getNetBase(event);
    return Math.max(netBase * (getArtistPct(event) / 100), 0);
};
const getLabelShare = (event) => {
    if (event?.label_share_estimated_base !== undefined && event?.label_share_estimated_base !== null) {
        return Math.max(toNumber(event.label_share_estimated_base), 0);
    }
    const netBase = getNetBase(event);
    return Math.max(netBase * (getLabelPct(event) / 100), 0);
};
const formatPctLabel = (value) => {
    const num = Number(value);
    if (!Number.isFinite(num)) return null;
    const text = Number.isInteger(num) ? String(num) : num.toFixed(2);
    return text.replace(/\.0+$/, "").replace(/(\.\d*[1-9])0+$/, "$1");
};
const singleEvent = computed(() => (filteredEvents.value.length === 1 ? filteredEvents.value[0] : null));
const artistSplitLabel = computed(() => {
    const event = singleEvent.value;
    if (!event) return "Artista";
    const pct = formatPctLabel(getArtistPct(event));
    return pct ? `Artista (${pct}%)` : "Artista";
});
const labelSplitLabel = computed(() => {
    const event = singleEvent.value;
    if (!event) return "Disquera";
    const pct = formatPctLabel(getLabelPct(event));
    return pct ? `Disquera (${pct}%)` : "Disquera";
});
const artistSplitHeading = computed(() => {
    const event = singleEvent.value;
    if (!event) return "Detalle del artista";
    const pct = formatPctLabel(getArtistPct(event));
    return pct ? `Detalle del artista (${pct}%)` : "Detalle del artista";
});

// Calcular totales filtrados
const filteredTotals = computed(() => {
    const filtered = filteredEvents.value;
    const paid = filtered.reduce((sum, e) => sum + Number(e.total_paid_base ?? 0), 0);
    const expenses = filtered.reduce((sum, e) => sum + Number(e.total_expenses_base ?? 0), 0);
    const personalExpenses = filtered.reduce(
        (sum, e) => sum + Number(e.total_personal_expenses_base ?? 0),
        0
    );
    const net = paid - expenses;
    const shareArtist = filtered.reduce((sum, e) => sum + getArtistShare(e), 0);
    const shareLabel = filtered.reduce((sum, e) => sum + getLabelShare(e), 0);
    const explicitAfter = filtered.reduce((sum, e) => {
        if (typeof e.artist_share_after_personal_base === "undefined" || e.artist_share_after_personal_base === null) {
            return sum;
        }
        return sum + Number(e.artist_share_after_personal_base ?? 0);
    }, 0);
    const hasExplicitAfter = filtered.some(
        (e) =>
            typeof e.artist_share_after_personal_base !== "undefined" &&
            e.artist_share_after_personal_base !== null
    );
    const shareArtistAfterPersonal = hasExplicitAfter
        ? explicitAfter
        : Math.max(shareArtist - personalExpenses, 0);

    return {
        paid: Math.round(paid * 100) / 100,
        expenses: Math.round(expenses * 100) / 100,
        personalExpenses: Math.round(personalExpenses * 100) / 100,
        net: Math.round(net * 100) / 100,
        shareLabel: Math.round(shareLabel * 100) / 100,
        shareArtist: Math.round(shareArtist * 100) / 100,
        shareArtistAfterPersonal: Math.round(shareArtistAfterPersonal * 100) / 100,
    };
});

const colors = {
    income: "#fde68a",
    expenses: "#ef4444",
    net: "#22c55e",
    label: "#15803d",
    artist: "#86efac",
    personal: "#f97316",
    remaining: "#bbf7d0",
};

const chartIncomeVsExpense = computed(() => ({
    labels: ["Ingresos", "Gastos", "Resultado neto (>=0)"],
    values: [
        Number(filteredTotals.value.paid ?? 0),
        Number(filteredTotals.value.expenses ?? 0),
        Math.max(Number(filteredTotals.value.net ?? 0), 0),
    ],
    colors: [colors.income, colors.expenses, colors.net],
}));

const chartSplit = computed(() => {
    const shareLabel = Math.max(Number(filteredTotals.value.shareLabel ?? 0), 0);
    const shareArtist = Math.max(Number(filteredTotals.value.shareArtist ?? 0), 0);
    return {
        labels: [labelSplitLabel.value, artistSplitLabel.value],
        values: [shareLabel, shareArtist],
        colors: [colors.label, colors.artist],
    };
});

const chartPersonalImpact = computed(() => {
    const shareArtist = Math.max(Number(filteredTotals.value.shareArtist ?? 0), 0);
    const personalExpenses = Math.max(Number(filteredTotals.value.personalExpenses ?? 0), 0);
    const remaining = Math.max(shareArtist - personalExpenses, 0);

    return {
        labels: ["Pagos al artista", "Restante del artista"],
        values: [Math.min(personalExpenses, shareArtist), remaining],
        colors: [colors.personal, colors.remaining],
    };
});

// Gráfica de barras por evento - últimos 10 eventos
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
                backgroundColor: colors.income,
                borderRadius: 4,
            },
            {
                label: "Gastos",
                data: toShow.map((e) => Number(e.total_expenses_base ?? 0)),
                backgroundColor: colors.expenses,
                borderRadius: 4,
            },
            {
                label: "Neto",
                data: toShow.map((e) => Math.max(Number(e.net_base ?? 0), 0)),
                backgroundColor: colors.net,
                borderRadius: 4,
            },
            {
                label: "Pagos al artista",
                data: toShow.map((e) => Number(e.total_personal_expenses_base ?? 0)),
                backgroundColor: colors.personal,
                borderRadius: 4,
            },
            {
                label: "Disquera",
                data: toShow.map((e) => {
                    return getLabelShare(e);
                }),
                backgroundColor: colors.label,
                borderRadius: 4,
            },
            {
                label: "Artista",
                data: toShow.map((e) => {
                    return getArtistShare(e);
                }),
                backgroundColor: colors.artist,
                borderRadius: 4,
            },
            {
                label: "Restante del artista",
                data: toShow.map((e) => {
                    if (e.artist_share_after_personal_base !== undefined && e.artist_share_after_personal_base !== null) {
                        return Math.max(toNumber(e.artist_share_after_personal_base), 0);
                    }
                    const netBase = getNetBase(e);
                    const shareArtist = getArtistShare(e);
                    const personal = toNumber(e.total_personal_expenses_base);
                    return Math.max(shareArtist - personal, 0);
                }),
                backgroundColor: colors.remaining,
                borderRadius: 4,
            },
        ],
    };
});

const format = (value) => formatMoneyWithSymbol(value, props.currency);
const formatPercent = (value) => `${Number(value ?? 0).toFixed(1)}%`;
const personalExpensePct = computed(() => {
    const shareArtist = Number(filteredTotals.value.shareArtist ?? 0);
    const personalExpenses = Number(filteredTotals.value.personalExpenses ?? 0);
    if (shareArtist <= 0) return 0;
    return Math.min((personalExpenses / shareArtist) * 100, 100);
});
const remainingArtistPct = computed(() => {
    const shareArtist = Number(filteredTotals.value.shareArtist ?? 0);
    const remaining = Math.max(shareArtist - Number(filteredTotals.value.personalExpenses ?? 0), 0);
    if (shareArtist <= 0) return 0;
    return Math.min((remaining / shareArtist) * 100, 100);
});

</script>

<template>
    <div class="space-y-6">
        <!-- Grid: Donuts + Comparativa -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Donut 1: Ingresos vs Gastos -->
            <div
                class="bg-gradient-to-br from-[#1a1a18] via-[#0f0f0d] to-[#0a0a08] border border-[#3a3a38] rounded-xl p-6 shadow-2xl hover:shadow-3xl hover:border-[#22c55e]/30 transition-all duration-300">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-1">
                        Ingresos vs Gastos
                    </h3>
                    <div class="h-0.5 w-12 bg-gradient-to-r from-[#22c55e] to-transparent rounded"></div>
                </div>
                <DoughnutChart :labels="chartIncomeVsExpense.labels" :values="chartIncomeVsExpense.values"
                    :colors="chartIncomeVsExpense.colors" :height-class="heightClass" />
                <div class="mt-5 grid grid-cols-3 gap-3">
                    <div
                        class="bg-[#0f0f0d]/50 border border-amber-400/30 rounded-lg p-3 hover:bg-[#0f0f0d]/80 hover:border-amber-400/50 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">Ingresos</p>
                        <p class="text-base font-bold text-amber-300 mt-1">{{ format(filteredTotals.paid) }}</p>
                    </div>
                    <div
                        class="bg-[#0f0f0d]/50 border border-red-500/30 rounded-lg p-3 hover:bg-[#0f0f0d]/80 hover:border-red-500/50 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">Gastos</p>
                        <p class="text-base font-bold text-red-400 mt-1">{{ format(filteredTotals.expenses) }}</p>
                    </div>
                    <div
                        class="bg-[#0f0f0d]/50 border border-green-500/30 rounded-lg p-3 hover:bg-[#0f0f0d]/80 hover:border-green-500/50 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">Neto</p>
                        <p class="text-base font-bold text-green-400 mt-1">{{ format(filteredTotals.net) }}</p>
                    </div>
                </div>
            </div>

            <!-- Donut 2: Reparto 30/70 -->
            <div
                class="bg-gradient-to-br from-[#1a1a18] via-[#0f0f0d] to-[#0a0a08] border border-[#3a3a38] rounded-xl p-6 shadow-2xl hover:shadow-3xl hover:border-[#22c55e]/30 transition-all duration-300">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-1">
                        Distribución (Artista/Disquera)
                    </h3>
                    <div class="h-0.5 w-12 bg-gradient-to-r from-[#22c55e] to-transparent rounded"></div>
                </div>
                <DoughnutChart :labels="chartSplit.labels" :values="chartSplit.values" :colors="chartSplit.colors"
                    :height-class="heightClass" />
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div
                        class="bg-gradient-to-br from-green-800/20 to-green-700/10 border border-green-700/40 rounded-lg p-3 hover:border-green-600/60 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">{{ labelSplitLabel }}</p>
                        <p class="text-base font-bold text-green-500 mt-1">{{ format(filteredTotals.shareLabel) }}</p>
                    </div>
                    <div
                        class="bg-gradient-to-br from-green-400/10 to-green-300/5 border border-green-300/30 rounded-lg p-3 hover:border-green-300/60 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">{{ artistSplitLabel }}</p>
                        <p class="text-base font-bold text-green-300 mt-1">{{ format(filteredTotals.shareArtist) }}</p>
                    </div>
                </div>
            </div>

            <!-- Donut 3: Impacto pagos al artista -->
            <div
                class="bg-gradient-to-br from-[#1a1a18] via-[#0f0f0d] to-[#0a0a08] border border-[#3a3a38] rounded-xl p-6 shadow-2xl hover:shadow-3xl hover:border-[#22c55e]/30 transition-all duration-300">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-1">
                        Parte del artista (con pagos)
                    </h3>
                    <div class="h-0.5 w-12 bg-gradient-to-r from-[#22c55e] to-transparent rounded"></div>
                </div>
                <DoughnutChart :labels="chartPersonalImpact.labels" :values="chartPersonalImpact.values"
                    :colors="chartPersonalImpact.colors" :height-class="heightClass" />
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div
                        class="bg-[#0f0f0d]/50 border border-orange-500/30 rounded-lg p-3 hover:bg-[#0f0f0d]/80 hover:border-orange-500/50 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">Pagos al artista</p>
                        <p class="text-base font-bold text-orange-400 mt-1">{{ format(filteredTotals.personalExpenses) }}</p>
                    </div>
                    <div
                        class="bg-[#0f0f0d]/50 border border-green-300/30 rounded-lg p-3 hover:bg-[#0f0f0d]/80 hover:border-green-300/50 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">Restante del artista</p>
                        <p class="text-base font-bold text-green-200 mt-1">{{
                            format(filteredTotals.shareArtistAfterPersonal) }}</p>
                    </div>
                </div>
            </div>

            <!-- Barra: Comparación por evento -->
            <div v-if="filteredEvents && filteredEvents.length > 0"
                class="bg-gradient-to-br from-[#1a1a18] via-[#0f0f0d] to-[#0a0a08] border border-[#3a3a38] rounded-xl p-6 shadow-2xl hover:shadow-3xl hover:border-[#22c55e]/30 transition-all duration-300 lg:col-span-3">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-1">
                        Comparativa por Evento
                    </h3>
                    <div class="h-0.5 w-12 bg-gradient-to-r from-[#22c55e] to-transparent rounded"></div>
                </div>
                <BarChart :labels="chartEventComparison.labels" :datasets="chartEventComparison.datasets"
                    :height-class="heightClass" />
                <div class="mt-5 grid grid-cols-3 gap-3">
                    <div
                        class="bg-[#0f0f0d]/50 border border-amber-400/30 rounded-lg p-3 hover:bg-[#0f0f0d]/80 hover:border-amber-400/50 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">Ingresos</p>
                        <p class="text-base font-bold text-amber-300 mt-1">{{ format(filteredTotals.paid) }}</p>
                    </div>
                    <div
                        class="bg-[#0f0f0d]/50 border border-red-500/30 rounded-lg p-3 hover:bg-[#0f0f0d]/80 hover:border-red-500/50 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">Gastos</p>
                        <p class="text-base font-bold text-red-400 mt-1">{{ format(filteredTotals.expenses) }}</p>
                    </div>
                    <div
                        class="bg-[#0f0f0d]/50 border border-green-500/30 rounded-lg p-3 hover:bg-[#0f0f0d]/80 hover:border-green-500/50 transition-all">
                        <p class="uppercase tracking-wider text-[10px] text-gray-400 font-semibold">Neto</p>
                        <p class="text-base font-bold text-green-400 mt-1">{{ format(filteredTotals.net) }}</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div
                class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6 shadow-lg">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-white">Resumen financiero del periodo</h3>
                    <p class="text-xs text-gray-500">Basado en los eventos disponibles.</p>
                </div>
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-gray-500">
                        <tr>
                            <th class="text-left pb-2">Concepto</th>
                            <th class="text-right pb-2">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#2a2a2a]">
                        <tr>
                            <td class="py-2 text-gray-300">Ingresos</td>
                            <td class="py-2 text-right text-amber-300 font-semibold">{{ format(filteredTotals.paid) }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-300">Gastos generales</td>
                            <td class="py-2 text-right text-red-400 font-semibold">{{ format(filteredTotals.expenses) }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-300">Pagos al artista</td>
                            <td class="py-2 text-right text-orange-400 font-semibold">{{ format(filteredTotals.personalExpenses) }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-300">Neto del evento</td>
                            <td class="py-2 text-right text-green-400 font-semibold">{{ format(filteredTotals.net) }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-300">Disquera</td>
                            <td class="py-2 text-right text-green-500 font-semibold">{{ format(filteredTotals.shareLabel) }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-300">Artista</td>
                            <td class="py-2 text-right text-green-300 font-semibold">{{ format(filteredTotals.shareArtist) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6 shadow-lg">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-white">{{ artistSplitHeading }}</h3>
                    <p class="text-xs text-gray-500">Pagos al artista descontados del share del artista.</p>
                </div>
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-gray-500">
                        <tr>
                            <th class="text-left pb-2">Concepto</th>
                            <th class="text-right pb-2">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#2a2a2a]">
                        <tr>
                            <td class="py-2 text-gray-300">Antes de pagos al artista</td>
                            <td class="py-2 text-right text-green-300 font-semibold">{{ format(filteredTotals.shareArtist) }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-300">Pagos al artista</td>
                            <td class="py-2 text-right text-orange-400 font-semibold">
                                {{ format(filteredTotals.personalExpenses) }}
                                <span class="text-xs text-gray-500 ml-2">({{ formatPercent(personalExpensePct) }})</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-300">Restante del artista</td>
                            <td class="py-2 text-right text-green-200 font-semibold">
                                {{ format(filteredTotals.shareArtistAfterPersonal) }}
                                <span class="text-xs text-gray-500 ml-2">({{ formatPercent(remainingArtistPct) }})</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

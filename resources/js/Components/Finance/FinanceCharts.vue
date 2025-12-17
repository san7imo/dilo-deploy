<script setup>
import { computed } from "vue";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";

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
    currency: {
        type: String,
        default: "€",
    },
    heightClass: {
        type: String,
        default: "h-80",
    },
});

const chartIncomeVsExpense = computed(() => ({
    labels: ["Ingresos", "Gastos", "Resultado neto (>=0)"],
    values: [
        Number(props.totals.paid ?? 0),
        Number(props.totals.expenses ?? 0),
        Math.max(Number(props.totals.net ?? 0), 0),
    ],
    colors: ["#22c55e", "#ef4444", "#ffa236"],
}));

const chartSplit = computed(() => {
    const net = Math.max(Number(props.totals.net ?? 0), 0);
    const shareLabel = props.totals.shareLabel ?? net * 0.30;
    const shareArtist = props.totals.shareArtist ?? net * 0.70;
    return {
        labels: ["30% Dilo", "70% Artista"],
        values: [shareLabel, shareArtist],
        colors: ["#38bdf8", "#ffa236"],
    };
});

const format = (value) => `${props.currency} ${Number(value ?? 0).toFixed(2)}`;
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gradient-to-br from-[#111] via-[#171717] to-[#0f0f0f] border border-[#2a2a2a] rounded-xl p-6 shadow-lg">
            <DoughnutChart
                :labels="chartIncomeVsExpense.labels"
                :values="chartIncomeVsExpense.values"
                :colors="chartIncomeVsExpense.colors"
                title="Ingresos vs gastos"
                :height-class="heightClass"
            />
            <div class="mt-4 text-xs text-gray-400 grid grid-cols-3 gap-3">
                <div>
                    <p class="uppercase tracking-wide text-[11px] text-gray-500">Ingresos</p>
                    <p class="text-sm font-semibold text-green-400">{{ format(totals.paid) }}</p>
                </div>
                <div>
                    <p class="uppercase tracking-wide text-[11px] text-gray-500">Gastos</p>
                    <p class="text-sm font-semibold text-red-400">{{ format(totals.expenses) }}</p>
                </div>
                <div>
                    <p class="uppercase tracking-wide text-[11px] text-gray-500">Neto</p>
                    <p class="text-sm font-semibold text-[#ffa236]">{{ format(totals.net) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-[#111] via-[#171717] to-[#0f0f0f] border border-[#2a2a2a] rounded-xl p-6 shadow-lg">
            <DoughnutChart
                :labels="chartSplit.labels"
                :values="chartSplit.values"
                :colors="chartSplit.colors"
                title="Reparto 30/70 (sobre neto)"
                :height-class="heightClass"
            />
            <div class="mt-4 text-xs text-gray-400 grid grid-cols-2 gap-3">
                <div>
                    <p class="uppercase tracking-wide text-[11px] text-gray-500">30% Dilo</p>
                    <p class="text-sm font-semibold text-sky-300">{{ format(totals.shareLabel) }}</p>
                </div>
                <div>
                    <p class="uppercase tracking-wide text-[11px] text-gray-500">70% Artista</p>
                    <p class="text-sm font-semibold text-[#ffa236]">{{ format(totals.shareArtist) }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

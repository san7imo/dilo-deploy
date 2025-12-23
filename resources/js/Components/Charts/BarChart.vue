<script setup>
import { computed } from "vue";
import { Bar } from "vue-chartjs";
import {
    Chart,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
} from "chart.js";

Chart.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({
    labels: { type: Array, default: () => [] },
    datasets: {
        type: Array,
        default: () => [
            {
                label: "Ingresos",
                data: [],
                backgroundColor: "#22c55e",
            },
            {
                label: "Gastos",
                data: [],
                backgroundColor: "#ef4444",
            },
        ],
    },
    title: { type: String, default: "" },
    heightClass: { type: String, default: "h-80" },
    indexAxis: { type: String, default: "x" }, // 'x' para vertical, 'y' para horizontal
});

const chartData = computed(() => ({
    labels: props.labels,
    datasets: props.datasets,
}));

const options = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: props.indexAxis,
    plugins: {
        legend: {
            position: "bottom",
            labels: {
                color: "#d1d5db",
                padding: 15,
                font: { size: 12 },
            },
        },
        title: props.title
            ? {
                display: true,
                text: props.title,
                color: "#f9fafb",
                font: { size: 14, weight: "600" },
                padding: 15,
            }
            : undefined,
    },
    scales: {
        x: {
            stacked: false,
            ticks: {
                color: "#9ca3af",
                font: { size: 11 },
            },
            grid: {
                color: "rgba(255, 255, 255, 0.05)",
            },
        },
        y: {
            stacked: false,
            ticks: {
                color: "#9ca3af",
                font: { size: 11 },
                callback: function (value) {
                    return "€ " + value.toLocaleString();
                },
            },
            grid: {
                color: "rgba(255, 255, 255, 0.05)",
            },
        },
    },
}));
</script>

<template>
    <div class="w-full" :class="heightClass">
        <Bar :data="chartData" :options="options" />
    </div>
</template>

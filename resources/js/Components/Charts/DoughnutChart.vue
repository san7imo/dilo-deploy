<script setup>
import { computed } from "vue";
import { Doughnut } from "vue-chartjs";
import {
    Chart,
    ArcElement,
    Tooltip,
    Legend,
} from "chart.js";

Chart.register(ArcElement, Tooltip, Legend);

const props = defineProps({
    labels: { type: Array, default: () => [] },
    values: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
    title: { type: String, default: "" },
    heightClass: { type: String, default: "h-72" },
});

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            data: props.values,
            backgroundColor: props.colors.length ? props.colors : ["#ffa236", "#3b82f6", "#22c55e", "#f97316"],
            borderWidth: 0,
        },
    ],
}));

const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: "bottom",
            labels: { color: "#d1d5db" },
        },
        title: props.title
            ? {
                  display: true,
                  text: props.title,
                  color: "#f9fafb",
                  font: { size: 14, weight: "600" },
              }
            : undefined,
    },
};
</script>

<template>
    <div class="w-full" :class="heightClass">
        <Doughnut :data="chartData" :options="options" />
    </div>
</template>

<script setup>
import ArtistLayout from "@/Layouts/ArtistLayout.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  track: Object,
  statement: Object,
  summary: Object,
  lines: Object,
});

const formatMoney = (value) => {
  const number = Number(value ?? 0);
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: 2,
    maximumFractionDigits: 6,
  }).format(Number.isNaN(number) ? 0 : number);
};
</script>

<template>
  <ArtistLayout>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-white">
          Detalle · {{ track.title }}
        </h1>
        <p class="text-gray-400 text-sm">
          Periodo: {{ statement.reporting_period || "-" }}
        </p>
      </div>
      <Link :href="route('artist.tracks.royalties.index', track.id)" class="text-gray-400 hover:text-white">
        Volver
      </Link>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
      <div class="bg-[#0f0f0f] border border-[#2a2a2a] rounded-lg p-4">
        <h3 class="text-[#ffa236] font-semibold mb-2">Total Track</h3>
        <div class="text-sm text-gray-300 space-y-2">
          <div><span class="text-gray-500">Units:</span> {{ summary.units_total }}</div>
          <div><span class="text-gray-500">Total USD:</span> {{ formatMoney(summary.total_track_usd) }}</div>
        </div>
      </div>

      <div class="bg-[#0f0f0f] border border-[#2a2a2a] rounded-lg p-4">
        <h3 class="text-[#ffa236] font-semibold mb-2">Mi parte</h3>
        <div class="text-sm text-gray-300 space-y-2">
          <div v-if="summary.my_pct !== null">
            <span class="text-gray-500">Mi %:</span> {{ Number(summary.my_pct).toFixed(2) }}%
          </div>
          <div v-if="summary.my_pct !== null">
            <span class="text-gray-500">Mi USD:</span> {{ formatMoney(summary.my_share_usd) }}
          </div>
          <div v-else class="text-xs text-[#ffa236]">
            Split no definido para ti
          </div>
        </div>
      </div>
    </div>

    <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
      <table class="min-w-full text-sm text-gray-300">
        <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
          <tr>
            <th class="px-4 py-2 text-left">DSP</th>
            <th class="px-4 py-2 text-left">Territorio</th>
            <th class="px-4 py-2 text-left">Periodo</th>
            <th class="px-4 py-2 text-left">Units</th>
            <th class="px-4 py-2 text-left">USD</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="line in lines.data"
            :key="line.id"
            class="border-b border-[#2a2a2a] hover:bg-[#181818]"
          >
            <td class="px-4 py-3">{{ line.channel || "-" }}</td>
            <td class="px-4 py-3">{{ line.country || "-" }}</td>
            <td class="px-4 py-3">{{ line.activity_period_text || "-" }}</td>
            <td class="px-4 py-3">{{ line.units ?? 0 }}</td>
            <td class="px-4 py-3">{{ formatMoney(line.net_total_usd) }}</td>
          </tr>
          <tr v-if="!lines.data?.length">
            <td colspan="5" class="px-4 py-6 text-center text-gray-400">
              No hay líneas para este statement.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationLinks v-if="lines.links" :links="lines.links" :meta="lines.meta" class="justify-end mt-4" />
  </ArtistLayout>
</template>

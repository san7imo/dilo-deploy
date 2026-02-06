<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  statement: Object,
  lines: Object,
  stats: Object,
});

const formatDate = (value) => {
  if (!value) return "-";
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString("es-ES");
};

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
  <AdminLayout title="Statement">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-white">Statement</h1>
        <p class="text-gray-400 text-sm">
          {{ statement.original_filename }}
        </p>
      </div>
      <Link :href="route('admin.royalties.statements.index')" class="text-gray-400 hover:text-white">
        Volver
      </Link>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
      <div class="bg-[#0f0f0f] border border-[#2a2a2a] rounded-lg p-4">
        <h3 class="text-[#ffa236] font-semibold mb-2">Metadata</h3>
        <div class="text-sm text-gray-300 space-y-2">
          <div><span class="text-gray-500">Proveedor:</span> {{ statement.provider }}</div>
          <div><span class="text-gray-500">Label:</span> {{ statement.label || "-" }}</div>
          <div><span class="text-gray-500">Periodo:</span> {{ statement.reporting_period || "-" }}</div>
          <div><span class="text-gray-500">Moneda:</span> {{ statement.currency }}</div>
          <div><span class="text-gray-500">Estado:</span> {{ statement.status }}</div>
          <div><span class="text-gray-500">Subido:</span> {{ formatDate(statement.created_at) }}</div>
        </div>
      </div>

      <div class="bg-[#0f0f0f] border border-[#2a2a2a] rounded-lg p-4">
        <h3 class="text-[#ffa236] font-semibold mb-2">Totales</h3>
        <div class="text-sm text-gray-300 space-y-2">
          <div><span class="text-gray-500">Units:</span> {{ statement.total_units ?? 0 }}</div>
          <div><span class="text-gray-500">Total USD:</span> {{ formatMoney(statement.total_net_usd) }}</div>
        </div>
      </div>

      <div class="bg-[#0f0f0f] border border-[#2a2a2a] rounded-lg p-4">
        <h3 class="text-[#ffa236] font-semibold mb-2">Líneas</h3>
        <div class="text-sm text-gray-300 space-y-2">
          <div><span class="text-gray-500">Líneas:</span> {{ stats.lines_count }}</div>
          <div><span class="text-gray-500">Con match:</span> {{ stats.matched_count }}</div>
          <div><span class="text-gray-500">Sin match:</span> {{ stats.unmatched_count }}</div>
        </div>
      </div>
    </div>

    <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
      <table class="min-w-full text-sm text-gray-300">
        <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
          <tr>
            <th class="px-4 py-2 text-left">Track</th>
            <th class="px-4 py-2 text-left">ISRC</th>
            <th class="px-4 py-2 text-left">UPC</th>
            <th class="px-4 py-2 text-left">DSP</th>
            <th class="px-4 py-2 text-left">Territorio</th>
            <th class="px-4 py-2 text-left">Periodo actividad</th>
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
            <td class="px-4 py-3">
              <div class="text-white">{{ line.track?.title || line.track_title || "-" }}</div>
            </td>
            <td class="px-4 py-3">{{ line.isrc || "-" }}</td>
            <td class="px-4 py-3">{{ line.upc || "-" }}</td>
            <td class="px-4 py-3">{{ line.channel || "-" }}</td>
            <td class="px-4 py-3">{{ line.country || "-" }}</td>
            <td class="px-4 py-3">{{ line.activity_period_text || "-" }}</td>
            <td class="px-4 py-3">{{ line.units ?? 0 }}</td>
            <td class="px-4 py-3">{{ formatMoney(line.net_total_usd) }}</td>
          </tr>
          <tr v-if="!lines.data?.length">
            <td colspan="8" class="px-4 py-6 text-center text-gray-400">
              No hay líneas para este statement.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationLinks
      v-if="lines.links"
      :links="lines.links"
      :meta="lines.meta"
      class="justify-end mt-4"
    />
  </AdminLayout>
</template>

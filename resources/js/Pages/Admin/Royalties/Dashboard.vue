<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";

const props = defineProps({
  kpis: Object,
  tracks: Object,
  monthly: Array,
  filters: Object,
});

const search = ref(props.filters?.q || "");

watch(search, (value) => {
  router.get(
    route("admin.royalties.dashboard"),
    { q: value || undefined },
    { preserveState: true, replace: true }
  );
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
  <AdminLayout title="Royalties">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-white">Royalties Dashboard</h1>
        <p class="text-gray-400 text-sm">
          Totales basados en statements procesados y vigentes.
        </p>
      </div>
      <Link :href="route('admin.royalties.statements.index')" class="text-gray-400 hover:text-white">
        Statements
      </Link>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4">
        <p class="text-gray-400 text-sm">Total USD</p>
        <p class="text-[#ffa236] text-2xl font-semibold">{{ formatMoney(kpis.total_usd) }}</p>
      </div>
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4">
        <p class="text-gray-400 text-sm">Units</p>
        <p class="text-[#ffa236] text-2xl font-semibold">{{ kpis.total_units }}</p>
      </div>
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4">
        <p class="text-gray-400 text-sm">Statements</p>
        <p class="text-[#ffa236] text-2xl font-semibold">{{ kpis.statements_count }}</p>
      </div>
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4">
        <p class="text-gray-400 text-sm">Tracks con regalías</p>
        <p class="text-[#ffa236] text-2xl font-semibold">{{ kpis.tracks_with_royalties }}</p>
      </div>
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4">
        <p class="text-gray-400 text-sm">Líneas sin match</p>
        <p class="text-[#ffa236] text-2xl font-semibold">{{ kpis.unmatched_lines }}</p>
      </div>
    </div>

    <div v-if="monthly?.length" class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4 mb-8">
      <h2 class="text-lg font-semibold text-[#ffa236] mb-3">Totales por mes</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-gray-300">
          <thead class="text-xs uppercase text-gray-400">
            <tr>
              <th class="px-3 py-2 text-left">Mes</th>
              <th class="px-3 py-2 text-left">Units</th>
              <th class="px-3 py-2 text-left">Total USD</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in monthly" :key="row.reporting_month_date" class="border-b border-[#2a2a2a]">
              <td class="px-3 py-2">{{ row.reporting_month_date }}</td>
              <td class="px-3 py-2">{{ row.total_units }}</td>
              <td class="px-3 py-2">{{ formatMoney(row.total_usd) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-white">Royalties por canción</h2>
      <input
        v-model="search"
        type="text"
        placeholder="Buscar por título o ISRC..."
        class="input"
      />
    </div>

    <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
      <table class="min-w-full text-sm text-gray-300">
        <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
          <tr>
            <th class="px-4 py-2 text-left">Track</th>
            <th class="px-4 py-2 text-left">ISRC</th>
            <th class="px-4 py-2 text-left">Release</th>
            <th class="px-4 py-2 text-left">Units</th>
            <th class="px-4 py-2 text-left">Total USD</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="track in tracks.data"
            :key="track.id"
            class="border-b border-[#2a2a2a] hover:bg-[#181818]"
          >
            <td class="px-4 py-3">{{ track.title }}</td>
            <td class="px-4 py-3">{{ track.isrc || "-" }}</td>
            <td class="px-4 py-3">{{ track.release?.title || "-" }}</td>
            <td class="px-4 py-3">{{ track.total_units ?? 0 }}</td>
            <td class="px-4 py-3">{{ formatMoney(track.total_usd) }}</td>
          </tr>
          <tr v-if="!tracks.data?.length">
            <td colspan="5" class="px-4 py-6 text-center text-gray-400">
              No hay tracks con regalías.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationLinks v-if="tracks.links" :links="tracks.links" :meta="tracks.meta" class="justify-end mt-4" />
  </AdminLayout>
</template>

<style scoped>
.input {
  @apply w-full max-w-xs bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}
</style>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link, router } from "@inertiajs/vue3";

const props = defineProps({
  statement: { type: Object, required: true },
  lines: { type: Object, required: true },
  stats: { type: Object, required: true },
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

const formatDate = (value) => {
  if (!value) return "-";
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString("es-ES");
};

const handleProcess = () => {
  router.post(route("admin.royalties.composition-statements.process", props.statement.id));
};
</script>

<template>
  <AdminLayout title="Detalle Statement Composición">
    <div class="flex items-start justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-white">
          Statement composición #{{ statement.id }}
        </h1>
        <p class="text-sm text-gray-400 mt-1">
          {{ statement.reporting_period || "-" }} · {{ statement.source_name || "-" }} ·
          <span class="uppercase">{{ statement.status }}</span>
        </p>
      </div>
      <div class="flex items-center gap-2">
        <Link :href="route('admin.royalties.dashboard')" class="btn-secondary">
          Dashboard
        </Link>
        <Link :href="route('admin.royalties.statements.index')" class="btn-secondary">
          Statements master
        </Link>
        <Link :href="route('admin.royalties.composition-statements.index')" class="btn-secondary">
          Statements composición
        </Link>
        <a :href="route('admin.royalties.composition-statements.download', statement.id)" class="btn-secondary">
          Descargar CSV
        </a>
        <button
          v-if="['uploaded', 'failed'].includes(statement.status)"
          type="button"
          class="btn-secondary"
          @click="handleProcess"
        >
          {{ statement.status === 'failed' ? 'Reprocesar' : 'Procesar' }}
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
      <div class="kpi-card">
        <p class="kpi-label">Líneas</p>
        <p class="kpi-value">{{ stats.lines_total ?? 0 }}</p>
      </div>
      <div class="kpi-card">
        <p class="kpi-label">Matched</p>
        <p class="kpi-value text-emerald-300">{{ stats.matched_count ?? 0 }}</p>
      </div>
      <div class="kpi-card">
        <p class="kpi-label">Unmatched</p>
        <p class="kpi-value text-red-300">{{ stats.unmatched_count ?? 0 }}</p>
      </div>
      <div class="kpi-card">
        <p class="kpi-label">Ambiguous</p>
        <p class="kpi-value text-yellow-300">{{ stats.ambiguous_count ?? 0 }}</p>
      </div>
      <div class="kpi-card">
        <p class="kpi-label">Allocations</p>
        <p class="kpi-value">{{ stats.allocations_count ?? 0 }}</p>
      </div>
      <div class="kpi-card">
        <p class="kpi-label">Alloc total</p>
        <p class="kpi-value">{{ formatMoney(stats.allocations_total_usd) }}</p>
      </div>
    </div>

    <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
      <table class="min-w-full text-sm text-gray-300">
        <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
          <tr>
            <th class="px-4 py-2 text-left">Line</th>
            <th class="px-4 py-2 text-left">Tipo</th>
            <th class="px-4 py-2 text-left">Composición</th>
            <th class="px-4 py-2 text-left">ISWC</th>
            <th class="px-4 py-2 text-left">Periodo actividad</th>
            <th class="px-4 py-2 text-left">Territorio</th>
            <th class="px-4 py-2 text-left">Source</th>
            <th class="px-4 py-2 text-left">Units</th>
            <th class="px-4 py-2 text-left">Amount USD</th>
            <th class="px-4 py-2 text-left">Match</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="line in lines.data"
            :key="line.id"
            class="border-b border-[#2a2a2a] hover:bg-[#181818]"
          >
            <td class="px-4 py-3">{{ line.source_line_id || line.id }}</td>
            <td class="px-4 py-3 uppercase">{{ line.line_type }}</td>
            <td class="px-4 py-3">{{ line.composition?.title || line.composition_title || "-" }}</td>
            <td class="px-4 py-3">{{ line.composition?.iswc || line.composition_iswc || "-" }}</td>
            <td class="px-4 py-3">
              {{ line.activity_period_text || "-" }}
              <span class="text-xs text-gray-500 block">{{ formatDate(line.activity_month_date) }}</span>
            </td>
            <td class="px-4 py-3">{{ line.territory_code || "-" }}</td>
            <td class="px-4 py-3">{{ line.source_name || "-" }}</td>
            <td class="px-4 py-3">{{ line.units ?? 0 }}</td>
            <td class="px-4 py-3">{{ formatMoney(line.amount_usd) }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 rounded bg-[#2a2a2a] text-xs uppercase">
                {{ line.match_status }}
              </span>
            </td>
          </tr>
          <tr v-if="!lines.data?.length">
            <td colspan="10" class="px-4 py-6 text-center text-gray-400">
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

<style scoped>
.btn-secondary {
  @apply bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors;
}
.kpi-card {
  @apply bg-[#0f0f0f] border border-[#2a2a2a] rounded-lg p-3;
}
.kpi-label {
  @apply text-xs text-gray-400 uppercase tracking-wide;
}
.kpi-value {
  @apply text-lg font-semibold text-white mt-1;
}
</style>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link, router } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const props = defineProps({
  master_kpis: { type: Object, default: () => ({}) },
  composition_kpis: { type: Object, default: () => ({}) },
  master_tracks: { type: Object, default: () => ({ data: [] }) },
  composition_rows: { type: Array, default: () => [] },
  master_monthly: { type: Array, default: () => [] },
  composition_monthly: { type: Array, default: () => [] },
  master_participants: { type: Array, default: () => [] },
  composition_participants: { type: Array, default: () => [] },
  master_breakdown: {
    type: Object,
    default: () => ({ source: [], period: [], territory: [] }),
  },
  composition_breakdown: {
    type: Object,
    default: () => ({ source: [], period: [], territory: [] }),
  },
  kpis: Object,
  tracks: Object,
  monthly: Array,
  payout_summary: { type: Object, default: () => ({}) },
  payout_requests: { type: Array, default: () => [] },
  filters: Object,
});

const masterKpis = computed(() => props.master_kpis || props.kpis || {});
const compositionKpis = computed(() => props.composition_kpis || {});
const masterTracks = computed(() => props.master_tracks || props.tracks || { data: [] });
const compositionRows = computed(() => props.composition_rows || []);
const masterMonthly = computed(() => props.master_monthly || props.monthly || []);
const compositionMonthly = computed(() => props.composition_monthly || []);
const masterParticipants = computed(() => props.master_participants || []);
const compositionParticipants = computed(() => props.composition_participants || []);
const masterBreakdown = computed(() => props.master_breakdown || { source: [], period: [], territory: [] });
const compositionBreakdown = computed(() => props.composition_breakdown || { source: [], period: [], territory: [] });

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
        <p class="text-gray-400 text-sm">Royalties por Composición y Royalties por Master.</p>
      </div>
      <div class="flex items-center">
        <BackNavButton :href="route('admin.dashboard')" />
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-8">
      <section class="panel">
        <div class="flex items-start justify-between mb-4">
          <div>
            <h2 class="text-lg font-semibold text-[#ffa236]">Master Royalties</h2>
            <p class="text-xs text-gray-400">Ingresos de grabación (ISRC).</p>
          </div>
          <span class="badge">MASTER</span>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-4">
          <div class="kpi-card">
            <p class="kpi-label">Total USD</p>
            <p class="kpi-value">{{ formatMoney(masterKpis.total_usd) }}</p>
          </div>
          <div class="kpi-card">
            <p class="kpi-label">Units</p>
            <p class="kpi-value">{{ masterKpis.total_units ?? 0 }}</p>
          </div>
          <div class="kpi-card">
            <p class="kpi-label">Statements</p>
            <p class="kpi-value">{{ masterKpis.statements_count ?? 0 }}</p>
          </div>
          <div class="kpi-card">
            <p class="kpi-label">Tracks con regalías</p>
            <p class="kpi-value">{{ masterKpis.tracks_with_royalties ?? 0 }}</p>
          </div>
          <div class="kpi-card col-span-2">
            <p class="kpi-label">Líneas sin match</p>
            <p class="kpi-value text-red-300">{{ masterKpis.unmatched_lines ?? 0 }}</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <a href="#master-details" class="btn-secondary">Ver más detalles</a>
          <Link :href="route('admin.royalties.statements.index')" class="btn-primary">
            Ir a statements master
          </Link>
        </div>
      </section>

      <section class="panel">
        <div class="flex items-start justify-between mb-4">
          <div>
            <h2 class="text-lg font-semibold text-[#ffa236]">Composition Royalties</h2>
            <p class="text-xs text-gray-400">Ingresos editoriales (obra/composición).</p>
          </div>
          <span class="badge">COMPOSITION</span>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-4">
          <div class="kpi-card">
            <p class="kpi-label">Total USD</p>
            <p class="kpi-value">{{ formatMoney(compositionKpis.total_usd) }}</p>
          </div>
          <div class="kpi-card">
            <p class="kpi-label">Units</p>
            <p class="kpi-value">{{ compositionKpis.total_units ?? 0 }}</p>
          </div>
          <div class="kpi-card">
            <p class="kpi-label">Statements</p>
            <p class="kpi-value">{{ compositionKpis.statements_count ?? 0 }}</p>
          </div>
          <div class="kpi-card">
            <p class="kpi-label">Composiciones con regalías</p>
            <p class="kpi-value">{{ compositionKpis.compositions_with_royalties ?? 0 }}</p>
          </div>
          <div class="kpi-card col-span-2">
            <p class="kpi-label">Líneas sin match</p>
            <p class="kpi-value text-red-300">{{ compositionKpis.unmatched_lines ?? 0 }}</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <a href="#composition-details" class="btn-secondary">Ver más detalles</a>
          <Link :href="route('admin.royalties.composition-statements.index')" class="btn-primary">
            Ir a statements composición
          </Link>
        </div>
      </section>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
      <div class="kpi-card">
        <p class="kpi-label">Solicitudes pendientes</p>
        <p class="kpi-value">{{ payout_summary.pending_count ?? 0 }}</p>
      </div>
      <div class="kpi-card">
        <p class="kpi-label">Pendiente de pagar (USD)</p>
        <p class="kpi-value">{{ formatMoney(payout_summary.pending_amount_usd) }}</p>
      </div>
      <div class="kpi-card">
        <p class="kpi-label">Pagado históricamente (USD)</p>
        <p class="kpi-value">{{ formatMoney(payout_summary.paid_amount_usd) }}</p>
      </div>
    </div>

    <div class="panel mb-8">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold text-[#ffa236]">Artistas solicitando regalías</h2>
        <Link :href="route('admin.royalties.payout-requests.index')" class="text-sm text-gray-400 hover:text-white">
          Ver todas
        </Link>
      </div>
      <div v-if="!payout_requests.length" class="text-sm text-gray-400">
        No hay solicitudes abiertas.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm text-gray-300">
          <thead class="text-xs uppercase text-gray-400">
            <tr>
              <th class="px-3 py-2 text-left">Artista</th>
              <th class="px-3 py-2 text-left">Correo</th>
              <th class="px-3 py-2 text-left">Monto USD</th>
              <th class="px-3 py-2 text-left">Estado</th>
              <th class="px-3 py-2 text-left">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="request in payout_requests" :key="request.id" class="border-b border-[#2a2a2a]">
              <td class="px-3 py-2">{{ request.requester_name }}</td>
              <td class="px-3 py-2">{{ request.requester_email }}</td>
              <td class="px-3 py-2">{{ formatMoney(request.requested_amount_usd) }}</td>
              <td class="px-3 py-2 uppercase text-xs">{{ request.status }}</td>
              <td class="px-3 py-2">
                <Link :href="route('admin.royalties.payout-requests.show', request.id)" class="text-[#ffa236] hover:underline">
                  Gestionar
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <section id="master-details" class="space-y-4 mb-10">
      <h2 class="text-xl font-semibold text-white">Detalle Master Royalties</h2>

      <div class="panel">
        <h3 class="text-lg font-semibold text-[#ffa236] mb-3">Participantes (Master)</h3>
        <div v-if="!masterParticipants.length" class="text-sm text-gray-400">
          No hay allocations master para mostrar participantes.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-sm text-gray-300">
            <thead class="text-xs uppercase text-gray-400">
              <tr>
                <th class="px-3 py-2 text-left">Participante</th>
                <th class="px-3 py-2 text-left">Accrued</th>
                <th class="px-3 py-2 text-left">Payable</th>
                <th class="px-3 py-2 text-left">Paid</th>
                <th class="px-3 py-2 text-left">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in masterParticipants" :key="`${row.party_user_id || 'u'}-${row.party_artist_id || 'a'}-${row.party_email || 'e'}`" class="border-b border-[#2a2a2a]">
                <td class="px-3 py-2">{{ row.participant_name }}</td>
                <td class="px-3 py-2">{{ formatMoney(row.accrued_usd) }}</td>
                <td class="px-3 py-2">{{ formatMoney(row.payable_usd) }}</td>
                <td class="px-3 py-2">{{ formatMoney(row.paid_usd) }}</td>
                <td class="px-3 py-2">{{ formatMoney(row.total_usd) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="panel">
          <h3 class="text-base font-semibold text-[#ffa236] mb-3">Master por fuente</h3>
          <div v-if="!masterBreakdown.source?.length" class="text-sm text-gray-400">Sin datos.</div>
          <ul v-else class="space-y-2 text-sm">
            <li v-for="row in masterBreakdown.source" :key="`source-${row.label}`" class="flex items-center justify-between gap-2">
              <span class="text-gray-300 truncate">{{ row.label }}</span>
              <span class="text-white font-medium">{{ formatMoney(row.total_usd) }}</span>
            </li>
          </ul>
        </div>
        <div class="panel">
          <h3 class="text-base font-semibold text-[#ffa236] mb-3">Master por período</h3>
          <div v-if="!masterBreakdown.period?.length" class="text-sm text-gray-400">Sin datos.</div>
          <ul v-else class="space-y-2 text-sm max-h-72 overflow-y-auto pr-1">
            <li v-for="row in masterBreakdown.period" :key="`period-${row.label}`" class="flex items-center justify-between gap-2">
              <span class="text-gray-300">{{ row.label }}</span>
              <span class="text-white font-medium">{{ formatMoney(row.total_usd) }}</span>
            </li>
          </ul>
        </div>
        <div class="panel">
          <h3 class="text-base font-semibold text-[#ffa236] mb-3">Master por territorio</h3>
          <div v-if="!masterBreakdown.territory?.length" class="text-sm text-gray-400">Sin datos.</div>
          <ul v-else class="space-y-2 text-sm max-h-72 overflow-y-auto pr-1">
            <li v-for="row in masterBreakdown.territory" :key="`territory-${row.label}`" class="flex items-center justify-between gap-2">
              <span class="text-gray-300">{{ row.label }}</span>
              <span class="text-white font-medium">{{ formatMoney(row.total_usd) }}</span>
            </li>
          </ul>
        </div>
      </div>

      <div v-if="masterMonthly?.length" class="panel">
        <h3 class="text-lg font-semibold text-[#ffa236] mb-3">Totales por mes (Master)</h3>
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
              <tr v-for="row in masterMonthly" :key="row.reporting_month_date" class="border-b border-[#2a2a2a]">
                <td class="px-3 py-2">{{ row.reporting_month_date }}</td>
                <td class="px-3 py-2">{{ row.total_units }}</td>
                <td class="px-3 py-2">{{ formatMoney(row.total_usd) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-white">Royalties por canción (Master)</h3>
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
              v-for="track in masterTracks.data"
              :key="track.id"
              class="border-b border-[#2a2a2a] hover:bg-[#181818]"
            >
              <td class="px-4 py-3">{{ track.title }}</td>
              <td class="px-4 py-3">{{ track.isrc || "-" }}</td>
              <td class="px-4 py-3">{{ track.release?.title || "-" }}</td>
              <td class="px-4 py-3">{{ track.total_units ?? 0 }}</td>
              <td class="px-4 py-3">{{ formatMoney(track.total_usd) }}</td>
            </tr>
            <tr v-if="!masterTracks.data?.length">
              <td colspan="5" class="px-4 py-6 text-center text-gray-400">
                No hay tracks con regalías master.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <PaginationLinks
        v-if="masterTracks.links"
        :links="masterTracks.links"
        :meta="masterTracks.meta"
        class="justify-end"
      />
    </section>

    <section id="composition-details" class="space-y-4">
      <h2 class="text-xl font-semibold text-white">Detalle Composition Royalties</h2>

      <div class="panel">
        <h3 class="text-lg font-semibold text-[#ffa236] mb-3">Participantes (Composition)</h3>
        <div v-if="!compositionParticipants.length" class="text-sm text-gray-400">
          No hay allocations de composición para mostrar participantes.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-sm text-gray-300">
            <thead class="text-xs uppercase text-gray-400">
              <tr>
                <th class="px-3 py-2 text-left">Participante</th>
                <th class="px-3 py-2 text-left">Accrued</th>
                <th class="px-3 py-2 text-left">Payable</th>
                <th class="px-3 py-2 text-left">Paid</th>
                <th class="px-3 py-2 text-left">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in compositionParticipants" :key="`${row.party_user_id || 'u'}-${row.party_artist_id || 'a'}-${row.party_email || 'e'}`" class="border-b border-[#2a2a2a]">
                <td class="px-3 py-2">{{ row.participant_name }}</td>
                <td class="px-3 py-2">{{ formatMoney(row.accrued_usd) }}</td>
                <td class="px-3 py-2">{{ formatMoney(row.payable_usd) }}</td>
                <td class="px-3 py-2">{{ formatMoney(row.paid_usd) }}</td>
                <td class="px-3 py-2">{{ formatMoney(row.total_usd) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="panel">
          <h3 class="text-base font-semibold text-[#ffa236] mb-3">Composición por fuente</h3>
          <div v-if="!compositionBreakdown.source?.length" class="text-sm text-gray-400">Sin datos.</div>
          <ul v-else class="space-y-2 text-sm">
            <li v-for="row in compositionBreakdown.source" :key="`comp-source-${row.label}`" class="flex items-center justify-between gap-2">
              <span class="text-gray-300 truncate">{{ row.label }}</span>
              <span class="text-white font-medium">{{ formatMoney(row.total_usd) }}</span>
            </li>
          </ul>
        </div>
        <div class="panel">
          <h3 class="text-base font-semibold text-[#ffa236] mb-3">Composición por período</h3>
          <div v-if="!compositionBreakdown.period?.length" class="text-sm text-gray-400">Sin datos.</div>
          <ul v-else class="space-y-2 text-sm max-h-72 overflow-y-auto pr-1">
            <li v-for="row in compositionBreakdown.period" :key="`comp-period-${row.label}`" class="flex items-center justify-between gap-2">
              <span class="text-gray-300">{{ row.label }}</span>
              <span class="text-white font-medium">{{ formatMoney(row.total_usd) }}</span>
            </li>
          </ul>
        </div>
        <div class="panel">
          <h3 class="text-base font-semibold text-[#ffa236] mb-3">Composición por territorio</h3>
          <div v-if="!compositionBreakdown.territory?.length" class="text-sm text-gray-400">Sin datos.</div>
          <ul v-else class="space-y-2 text-sm max-h-72 overflow-y-auto pr-1">
            <li v-for="row in compositionBreakdown.territory" :key="`comp-territory-${row.label}`" class="flex items-center justify-between gap-2">
              <span class="text-gray-300">{{ row.label }}</span>
              <span class="text-white font-medium">{{ formatMoney(row.total_usd) }}</span>
            </li>
          </ul>
        </div>
      </div>

      <div v-if="compositionMonthly?.length" class="panel">
        <h3 class="text-lg font-semibold text-[#ffa236] mb-3">Totales por mes (Composition)</h3>
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
              <tr v-for="row in compositionMonthly" :key="row.reporting_month_date" class="border-b border-[#2a2a2a]">
                <td class="px-3 py-2">{{ row.reporting_month_date }}</td>
                <td class="px-3 py-2">{{ row.total_units }}</td>
                <td class="px-3 py-2">{{ formatMoney(row.total_usd) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
        <table class="min-w-full text-sm text-gray-300">
          <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
            <tr>
              <th class="px-4 py-2 text-left">Composición</th>
              <th class="px-4 py-2 text-left">ISWC</th>
              <th class="px-4 py-2 text-left">Units</th>
              <th class="px-4 py-2 text-left">Total USD</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="composition in compositionRows"
              :key="composition.id"
              class="border-b border-[#2a2a2a] hover:bg-[#181818]"
            >
              <td class="px-4 py-3">{{ composition.title }}</td>
              <td class="px-4 py-3">{{ composition.iswc || "-" }}</td>
              <td class="px-4 py-3">{{ composition.total_units ?? 0 }}</td>
              <td class="px-4 py-3">{{ formatMoney(composition.total_usd) }}</td>
            </tr>
            <tr v-if="!compositionRows.length">
              <td colspan="4" class="px-4 py-6 text-center text-gray-400">
                No hay composiciones con regalías.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </AdminLayout>
</template>

<style scoped>
.panel {
  @apply bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4;
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

.badge {
  @apply text-[10px] font-semibold tracking-wide text-[#ffa236] border border-[#ffa236]/40 rounded-full px-2 py-1;
}

.btn-primary {
  @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-3 py-2 rounded-md transition-colors text-sm;
}

.btn-secondary {
  @apply bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-3 py-2 rounded-md transition-colors text-sm;
}

.input {
  @apply w-full max-w-xs bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}
</style>

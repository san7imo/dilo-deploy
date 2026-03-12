<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
  statement: Object,
  lines: Object,
  stats: Object,
  line_match_options: { type: Object, default: () => ({}) },
  current_filter: { type: String, default: "all" },
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

const matchStatusLabel = (status) => {
  const value = String(status || "unmatched").toLowerCase();
  if (value === "matched") return "Matched";
  if (value === "ambiguous") return "Ambiguo";
  if (value === "duplicate") return "Duplicado";
  if (value === "reference_only") return "Reference only";
  return "Unmatched";
};

const matchStatusClass = (status) => {
  const value = String(status || "unmatched").toLowerCase();
  if (value === "matched") return "bg-green-500/20 text-green-300";
  if (value === "ambiguous") return "bg-amber-500/20 text-amber-300";
  if (value === "duplicate") return "bg-purple-500/20 text-purple-300";
  if (value === "reference_only") return "bg-blue-500/20 text-blue-300";
  return "bg-red-500/20 text-red-300";
};

const selectedTrackByLine = ref({});
const processingLineId = ref(null);

for (const line of props.lines?.data ?? []) {
  selectedTrackByLine.value[line.id] = line.track_id ? String(line.track_id) : "";
}

const getLineOptions = (lineId) => {
  return props.line_match_options?.[lineId] ?? [];
};

const saveLineMatch = (lineId) => {
  if (processingLineId.value) return;

  processingLineId.value = lineId;
  const selected = selectedTrackByLine.value[lineId];
  const payload = {
    track_id: selected === "" ? null : Number(selected),
  };

  router.patch(route("admin.royalties.statements.lines.match", [props.statement.id, lineId]), payload, {
    preserveScroll: true,
    preserveState: true,
    onFinish: () => {
      processingLineId.value = null;
    },
  });
};

const clearLineMatch = (lineId) => {
  if (processingLineId.value) return;
  selectedTrackByLine.value[lineId] = "";
  saveLineMatch(lineId);
};
</script>

<template>
  <AdminLayout title="Statement">
    <div class="flex items-start justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-white">Statement</h1>
        <p class="text-gray-400 text-sm">
          {{ statement.original_filename }}
        </p>
      </div>
      <div class="flex items-center gap-2">
        <Link :href="route('admin.royalties.dashboard')" class="btn-secondary">
          Dashboard
        </Link>
        <Link :href="route('admin.royalties.composition-statements.index')" class="btn-secondary">
          Statements composición
        </Link>
        <Link :href="route('admin.royalties.statements.index')" class="btn-secondary">
          Statements master
        </Link>
        <a :href="route('admin.royalties.statements.download', statement.id)" class="btn-secondary">
          Descargar CSV
        </a>
      </div>
    </div>

    <div class="mb-4 flex items-center gap-2">
      <Link
        :href="route('admin.royalties.statements.show', { statement: statement.id, match: 'all' })"
        class="px-3 py-1.5 rounded text-sm border transition-colors"
        :class="current_filter === 'all' ? 'bg-[#ffa236] text-black border-[#ffa236]' : 'bg-transparent text-gray-300 border-[#2a2a2a] hover:border-[#ffa236]'"
      >
        Todos ({{ stats.lines_count }})
      </Link>
      <Link
        :href="route('admin.royalties.statements.show', { statement: statement.id, match: 'review' })"
        class="px-3 py-1.5 rounded text-sm border transition-colors"
        :class="current_filter === 'review' ? 'bg-[#ffa236] text-black border-[#ffa236]' : 'bg-transparent text-gray-300 border-[#2a2a2a] hover:border-[#ffa236]'"
      >
        Revisión manual ({{ (stats.unmatched_count || 0) + (stats.ambiguous_count || 0) + (stats.duplicate_count || 0) }})
      </Link>
      <Link
        :href="route('admin.royalties.statements.show', { statement: statement.id, match: 'matched' })"
        class="px-3 py-1.5 rounded text-sm border transition-colors"
        :class="current_filter === 'matched' ? 'bg-[#ffa236] text-black border-[#ffa236]' : 'bg-transparent text-gray-300 border-[#2a2a2a] hover:border-[#ffa236]'"
      >
        Matched ({{ stats.matched_count }})
      </Link>
      <Link
        :href="route('admin.royalties.statements.show', { statement: statement.id, match: 'unmatched' })"
        class="px-3 py-1.5 rounded text-sm border transition-colors"
        :class="current_filter === 'unmatched' ? 'bg-[#ffa236] text-black border-[#ffa236]' : 'bg-transparent text-gray-300 border-[#2a2a2a] hover:border-[#ffa236]'"
      >
        Unmatched ({{ stats.unmatched_count }})
      </Link>
      <Link
        :href="route('admin.royalties.statements.show', { statement: statement.id, match: 'ambiguous' })"
        class="px-3 py-1.5 rounded text-sm border transition-colors"
        :class="current_filter === 'ambiguous' ? 'bg-[#ffa236] text-black border-[#ffa236]' : 'bg-transparent text-gray-300 border-[#2a2a2a] hover:border-[#ffa236]'"
      >
        Ambiguo ({{ stats.ambiguous_count || 0 }})
      </Link>
      <Link
        :href="route('admin.royalties.statements.show', { statement: statement.id, match: 'duplicate' })"
        class="px-3 py-1.5 rounded text-sm border transition-colors"
        :class="current_filter === 'duplicate' ? 'bg-[#ffa236] text-black border-[#ffa236]' : 'bg-transparent text-gray-300 border-[#2a2a2a] hover:border-[#ffa236]'"
      >
        Duplicado ({{ stats.duplicate_count || 0 }})
      </Link>
      <Link
        :href="route('admin.royalties.statements.show', { statement: statement.id, match: 'reference_only' })"
        class="px-3 py-1.5 rounded text-sm border transition-colors"
        :class="current_filter === 'reference_only' ? 'bg-[#ffa236] text-black border-[#ffa236]' : 'bg-transparent text-gray-300 border-[#2a2a2a] hover:border-[#ffa236]'"
      >
        Reference only ({{ stats.reference_only_count || 0 }})
      </Link>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
      <div class="bg-[#0f0f0f] border border-[#2a2a2a] rounded-lg p-4">
        <h3 class="text-[#ffa236] font-semibold mb-2">Metadata</h3>
        <div class="text-sm text-gray-300 space-y-2">
          <div><span class="text-gray-500">Proveedor:</span> {{ statement.provider }}</div>
          <div><span class="text-gray-500">Label:</span> {{ statement.label || "-" }}</div>
          <div><span class="text-gray-500">Periodo:</span> {{ statement.reporting_period || "-" }}</div>
          <div><span class="text-gray-500">Versión:</span> v{{ statement.version ?? 1 }}</div>
          <div><span class="text-gray-500">Current:</span> {{ statement.is_current ? "Sí" : "No" }}</div>
          <div><span class="text-gray-500">Reference only:</span> {{ statement.is_reference_only ? "Sí" : "No" }}</div>
          <div v-if="statement.duplicate_of_statement_id">
            <span class="text-gray-500">Duplicado de:</span>
            #{{ statement.duplicate_of_statement_id }}
            <span v-if="statement.duplicate_of?.version">
              (v{{ statement.duplicate_of.version }})
            </span>
          </div>
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
          <div><span class="text-gray-500">Matched:</span> {{ stats.matched_count }}</div>
          <div><span class="text-gray-500">Unmatched:</span> {{ stats.unmatched_count }}</div>
          <div><span class="text-gray-500">Ambiguos:</span> {{ stats.ambiguous_count || 0 }}</div>
          <div><span class="text-gray-500">Duplicados:</span> {{ stats.duplicate_count || 0 }}</div>
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
            <th class="px-4 py-2 text-left">Estado match</th>
            <th class="px-4 py-2 text-left">Match manual</th>
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
            <td class="px-4 py-3">
              <span
                class="px-2 py-1 rounded text-xs uppercase"
                :class="matchStatusClass(line.match_status)"
              >
                {{ matchStatusLabel(line.match_status) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div
                v-if="statement.status === 'processing'"
                class="text-xs text-gray-500"
              >
                Bloqueado durante procesamiento
              </div>

              <div
                v-else-if="statement.is_reference_only || line.match_status === 'reference_only'"
                class="text-xs text-blue-300"
              >
                Statement reference_only: no editable.
              </div>

              <div v-else-if="line.track_id" class="space-y-2">
                <div class="text-xs text-green-300">Asignado</div>
                <div v-if="line.match_status === 'duplicate'" class="text-xs text-purple-300">
                  Línea deduplicada en el import.
                </div>
                <button
                  type="button"
                  class="text-xs text-red-300 hover:underline"
                  :disabled="processingLineId !== null"
                  @click="clearLineMatch(line.id)"
                >
                  Quitar match
                </button>
              </div>

              <div v-else class="space-y-2">
                <select
                  v-model="selectedTrackByLine[line.id]"
                  class="w-full bg-[#151515] border border-[#2a2a2a] rounded px-2 py-1 text-xs text-white"
                >
                  <option value="">Selecciona track</option>
                  <option
                    v-for="candidate in getLineOptions(line.id)"
                    :key="candidate.id"
                    :value="String(candidate.id)"
                  >
                    {{ candidate.title }} · ISRC {{ candidate.isrc || "-" }} · UPC {{ candidate.release_upc || "-" }} · {{ candidate.match_reason }}
                  </option>
                </select>
                <div v-if="!getLineOptions(line.id).length" class="text-xs text-amber-300">
                  Sin sugerencias automáticas.
                </div>
                <button
                  type="button"
                  class="text-xs text-[#ffa236] hover:underline disabled:text-gray-500"
                  :disabled="!selectedTrackByLine[line.id] || processingLineId !== null"
                  @click="saveLineMatch(line.id)"
                >
                  {{ processingLineId === line.id ? "Guardando..." : "Guardar match" }}
                </button>
              </div>
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
</style>

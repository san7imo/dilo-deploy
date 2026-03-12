<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import { Link } from "@inertiajs/vue3";

defineProps({
  composition: Object,
  agreements: Array,
  back_url: {
    type: String,
    default: "",
  },
});

const formatDate = (value) => {
  if (!value) return "-";
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString("es-ES");
};
</script>

<template>
  <AdminLayout title="Splits de composición">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-white">Splits · {{ composition.title }}</h1>
        <p class="text-gray-400 text-sm">
          ISWC: {{ composition.iswc || "-" }}
        </p>
      </div>
      <div class="flex items-center gap-3">
        <BackNavButton :href="back_url || route('admin.tracks.index')" />
        <Link
          :href="route('admin.compositions.splits.create', { composition: composition.id, back: back_url || undefined })"
          class="btn-primary"
        >
          Nuevo split
        </Link>
      </div>
    </div>

    <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
      <table class="min-w-full text-sm text-gray-300">
        <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
          <tr>
            <th class="px-4 py-2 text-left">Versión</th>
            <th class="px-4 py-2 text-left">Estado</th>
            <th class="px-4 py-2 text-left">Participantes</th>
            <th class="px-4 py-2 text-left">Pools</th>
            <th class="px-4 py-2 text-left">Contrato</th>
            <th class="px-4 py-2 text-left">Vigencia</th>
            <th class="px-4 py-2 text-left">Creado</th>
            <th class="px-4 py-2 text-left">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="agreement in agreements"
            :key="agreement.id"
            class="border-b border-[#2a2a2a] hover:bg-[#181818]"
          >
            <td class="px-4 py-3">v{{ agreement.version ?? 1 }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 rounded bg-[#2a2a2a] text-xs uppercase">
                {{ agreement.status }}
              </span>
            </td>
            <td class="px-4 py-3">{{ agreement.participants_count ?? 0 }}</td>
            <td class="px-4 py-3 text-xs text-gray-400">
              W: {{ agreement.writers_count ?? 0 }} ·
              P: {{ agreement.publishers_count ?? 0 }} ·
              M: {{ agreement.mechanical_payees_count ?? 0 }}
            </td>
            <td class="px-4 py-3">{{ agreement.contract_original_filename }}</td>
            <td class="px-4 py-3">
              {{ agreement.effective_from ? formatDate(agreement.effective_from) : "-" }}
              →
              {{ agreement.effective_to ? formatDate(agreement.effective_to) : "-" }}
            </td>
            <td class="px-4 py-3">{{ formatDate(agreement.created_at) }}</td>
            <td class="px-4 py-3">
              <a
                :href="route('admin.compositions.splits.download', [composition.id, agreement.id])"
                class="text-[#ffa236] hover:underline"
                target="_blank"
                rel="noopener"
              >
                Descargar
              </a>
            </td>
          </tr>
          <tr v-if="!agreements.length">
            <td colspan="8" class="px-4 py-6 text-center text-gray-400">
              No hay splits para esta composición.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>

<style scoped>
.btn-primary {
  @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}
</style>

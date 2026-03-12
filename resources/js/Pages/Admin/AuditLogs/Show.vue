<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link } from "@inertiajs/vue3";

defineProps({
  log: { type: Object, required: true },
  changes: { type: Array, default: () => [] },
});

const formatDateTime = (value) => {
  if (!value) return "-";
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? value
    : date.toLocaleString("es-CO", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
    });
};
</script>

<template>
  <AdminLayout title="Detalle de auditoría">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-white">Detalle de auditoría #{{ log.id }}</h1>
          <p class="text-sm text-gray-400">Trazabilidad completa de cambios por campo.</p>
        </div>
        <Link :href="route('admin.audit-logs.index')" class="btn-secondary">Volver al listado</Link>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-[#121212] border border-[#2a2a2a] rounded-lg p-4 space-y-2 text-sm">
          <p><span class="text-gray-500">Fecha:</span> {{ formatDateTime(log.created_at) }}</p>
          <p>
            <span class="text-gray-500">Acción:</span>
            <span class="ml-2 px-2 py-1 rounded text-xs bg-[#2a2a2a]">{{ log.event_label }}</span>
          </p>
          <p><span class="text-gray-500">Entidad:</span> {{ log.auditable_model }} #{{ log.auditable_id }}</p>
          <p class="break-all"><span class="text-gray-500">Tipo:</span> {{ log.auditable_type }}</p>
        </div>

        <div class="bg-[#121212] border border-[#2a2a2a] rounded-lg p-4 space-y-2 text-sm">
          <p><span class="text-gray-500">Usuario:</span> {{ log.user?.name || "Sistema" }}</p>
          <p><span class="text-gray-500">Email:</span> {{ log.user?.email || "-" }}</p>
          <p><span class="text-gray-500">Método:</span> {{ log.method || "-" }}</p>
          <p><span class="text-gray-500">IP:</span> {{ log.ip_address || "-" }}</p>
          <p class="break-all"><span class="text-gray-500">URL:</span> {{ log.url || "-" }}</p>
          <p class="break-all"><span class="text-gray-500">User-Agent:</span> {{ log.user_agent || "-" }}</p>
        </div>
      </div>

      <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
        <table class="min-w-full text-sm text-gray-300">
          <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
            <tr>
              <th class="px-4 py-2 text-left">Campo</th>
              <th class="px-4 py-2 text-left">Valor anterior</th>
              <th class="px-4 py-2 text-left">Valor nuevo</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in changes"
              :key="row.field"
              class="border-b border-[#2a2a2a] align-top"
              :class="row.changed ? 'bg-transparent' : 'opacity-70'"
            >
              <td class="px-4 py-3 font-medium text-white whitespace-nowrap">{{ row.field }}</td>
              <td class="px-4 py-3">
                <pre class="audit-pre">{{ row.old_value }}</pre>
              </td>
              <td class="px-4 py-3">
                <pre class="audit-pre">{{ row.new_value }}</pre>
              </td>
            </tr>
            <tr v-if="!changes.length">
              <td colspan="3" class="px-4 py-6 text-center text-gray-500">
                Este evento no tiene diferencias de campos para mostrar.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.btn-secondary {
  @apply bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors;
}

.audit-pre {
  @apply whitespace-pre-wrap break-words text-xs leading-5 m-0;
}
</style>

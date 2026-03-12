<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link, router } from "@inertiajs/vue3";
import { reactive } from "vue";

const props = defineProps({
  logs: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  events: { type: Array, default: () => [] },
  models: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] },
});

const form = reactive({
  search: props.filters?.search ?? "",
  event: props.filters?.event ?? "",
  model: props.filters?.model ?? "",
  user_id: props.filters?.user_id ? String(props.filters.user_id) : "",
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

const applyFilters = () => {
  router.get(
    route("admin.audit-logs.index"),
    {
      search: form.search || undefined,
      event: form.event || undefined,
      model: form.model || undefined,
      user_id: form.user_id || undefined,
    },
    { preserveState: true, preserveScroll: true, replace: true }
  );
};

const resetFilters = () => {
  form.search = "";
  form.event = "";
  form.model = "";
  form.user_id = "";
  applyFilters();
};
</script>

<template>
  <AdminLayout title="Auditoría">
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-semibold text-white">Auditoría centralizada</h1>
        <p class="text-sm text-gray-400">
          Historial de creación, edición, eliminación y restauración por usuario y entidad.
        </p>
      </div>

      <form
        class="grid grid-cols-1 md:grid-cols-5 gap-3 bg-[#121212] border border-[#2a2a2a] rounded-lg p-4"
        @submit.prevent="applyFilters"
      >
        <div class="md:col-span-2">
          <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1">Buscar</label>
          <input
            v-model="form.search"
            type="text"
            placeholder="Usuario, email, modelo, ID, URL o IP"
            class="w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-[#ffa236]"
          />
        </div>

        <div>
          <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1">Acción</label>
          <select
            v-model="form.event"
            class="w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-[#ffa236]"
          >
            <option value="">Todas</option>
            <option v-for="event in events" :key="event.value" :value="event.value">
              {{ event.label }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1">Entidad</label>
          <select
            v-model="form.model"
            class="w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-[#ffa236]"
          >
            <option value="">Todas</option>
            <option v-for="model in models" :key="model.value" :value="model.value">
              {{ model.label }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1">Usuario</label>
          <select
            v-model="form.user_id"
            class="w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-[#ffa236]"
          >
            <option value="">Todos</option>
            <option v-for="user in users" :key="user.id" :value="String(user.id)">
              {{ user.name }} ({{ user.email }})
            </option>
          </select>
        </div>

        <div class="md:col-span-5 flex items-center gap-2 justify-end">
          <button type="button" class="btn-secondary" @click="resetFilters">Limpiar</button>
          <button type="submit" class="btn-primary">Filtrar</button>
        </div>
      </form>

      <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
        <table class="min-w-full text-sm text-gray-300">
          <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
            <tr>
              <th class="px-4 py-2 text-left">Fecha</th>
              <th class="px-4 py-2 text-left">Usuario</th>
              <th class="px-4 py-2 text-left">Acción</th>
              <th class="px-4 py-2 text-left">Entidad</th>
              <th class="px-4 py-2 text-left">Campos</th>
              <th class="px-4 py-2 text-left">Contexto</th>
              <th class="px-4 py-2 text-right">Detalle</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="log in logs.data"
              :key="log.id"
              class="border-b border-[#2a2a2a] hover:bg-[#181818]"
            >
              <td class="px-4 py-3 whitespace-nowrap">{{ formatDateTime(log.created_at) }}</td>
              <td class="px-4 py-3">
                <p class="font-medium text-white">{{ log.user?.name || "Sistema" }}</p>
                <p class="text-xs text-gray-400">{{ log.user?.email || "Sin usuario autenticado" }}</p>
              </td>
              <td class="px-4 py-3">
                <span
                  class="px-2 py-1 rounded text-xs font-semibold"
                  :class="{
                    'bg-green-500/20 text-green-300': log.event === 'created' || log.event === 'restored',
                    'bg-yellow-500/20 text-yellow-300': log.event === 'updated',
                    'bg-red-500/20 text-red-300': log.event === 'deleted' || log.event === 'force_deleted',
                    'bg-[#2a2a2a] text-gray-300': !['created', 'updated', 'deleted', 'restored', 'force_deleted'].includes(log.event),
                  }"
                >
                  {{ log.event_label }}
                </span>
              </td>
              <td class="px-4 py-3">
                <p class="font-medium text-white">{{ log.auditable_model }}</p>
                <p class="text-xs text-gray-400">ID: {{ log.auditable_id }}</p>
              </td>
              <td class="px-4 py-3">
                <p v-if="log.changed_fields_total === 0" class="text-gray-500">Sin cambios detectados</p>
                <template v-else>
                  <p class="text-white">{{ log.changed_fields.join(", ") }}</p>
                  <p v-if="log.changed_fields_total > log.changed_fields.length" class="text-xs text-gray-400">
                    +{{ log.changed_fields_total - log.changed_fields.length }} campos más
                  </p>
                </template>
              </td>
              <td class="px-4 py-3 text-xs text-gray-400 space-y-1">
                <p><span class="text-gray-500">Método:</span> {{ log.method || "-" }}</p>
                <p><span class="text-gray-500">IP:</span> {{ log.ip_address || "-" }}</p>
                <p class="max-w-[22rem] truncate" :title="log.url || '-'">
                  <span class="text-gray-500">URL:</span> {{ log.url || "-" }}
                </p>
              </td>
              <td class="px-4 py-3 text-right">
                <Link :href="route('admin.audit-logs.show', log.id)" class="text-[#ffa236] hover:underline">
                  Ver detalle
                </Link>
              </td>
            </tr>
            <tr v-if="!logs.data?.length">
              <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                No hay eventos de auditoría con los filtros actuales.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <PaginationLinks
        v-if="logs.links"
        :links="logs.links"
        :meta="logs.meta"
        class="justify-end mt-4"
      />
    </div>
  </AdminLayout>
</template>

<style scoped>
.btn-primary {
  @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}

.btn-secondary {
  @apply bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors;
}
</style>

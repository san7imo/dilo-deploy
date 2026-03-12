<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import axios from "axios";

const loading = ref(false);
const requestError = ref("");

const stats = ref({
  catalog: {
    artists: 0,
    other_artists: 0,
    releases: 0,
    tracks: 0,
    genres: 0,
    compositions: 0,
  },
  operations: {
    events_total: 0,
    events_upcoming: 0,
    events_this_month: 0,
    countries_count: 0,
    cities_count: 0,
    organizers: 0,
    workers: 0,
  },
  finance: {
    event_income_usd: 0,
    event_expenses_usd: 0,
    event_net_usd: 0,
    payroll_month_usd: 0,
    payroll_year_usd: 0,
    royalties_current_usd: 0,
    payout_pending_count: 0,
    payout_pending_usd: 0,
  },
  health: {
    royalty_statements_current: 0,
    royalty_unmatched_lines: 0,
    pending_external_invitations: 0,
    active_master_splits: 0,
    active_composition_splits: 0,
  },
  top_countries: [],
  top_event_types: [],
  events: [],
});

const formatDate = (value) => {
  if (!value) return "—";
  return new Date(`${value}T00:00:00`).toLocaleDateString("es-CO");
};

const formatUsd = (value) => {
  const amount = Number(value ?? 0);
  if (!Number.isFinite(amount)) return "US$ 0,00";

  return new Intl.NumberFormat("es-CO", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount);
};

const fetchDashboard = async () => {
  loading.value = true;
  requestError.value = "";

  try {
    const { data } = await axios.get("/admin/dashboard/data");
    stats.value = data;
  } catch (error) {
    requestError.value = error?.response?.data?.message || "No se pudo cargar el resumen del dashboard.";
  } finally {
    loading.value = false;
  }
};

onMounted(fetchDashboard);
</script>

<template>
  <AdminLayout>
    <div class="space-y-8">
      <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-[#ffa236] mb-2">Panel principal</h1>
          <p class="text-gray-400 text-sm">
            Resumen ejecutivo del sistema: catálogo, operación, finanzas y alertas.
          </p>
        </div>

        <div class="flex gap-2">
          <Link
            :href="route('admin.events.index')"
            class="inline-flex items-center justify-center rounded-md bg-[#ffa236] px-4 py-2 text-sm font-semibold text-black hover:bg-[#ffb54d]"
          >
            Ver eventos
          </Link>
          <Link
            :href="route('admin.artists.index')"
            class="inline-flex items-center justify-center rounded-md border border-[#ffa236] px-4 py-2 text-sm font-semibold text-[#ffa236] hover:bg-[#ffa236]/10"
          >
            Ver artistas
          </Link>
        </div>
      </div>

      <div v-if="requestError" class="rounded-md border border-red-500/40 bg-red-500/10 p-3 text-sm text-red-200">
        {{ requestError }}
      </div>

      <div v-if="loading" class="rounded-md border border-[#2a2a2a] bg-[#1d1d1b] p-4 text-sm text-gray-400">
        Cargando resumen...
      </div>

      <template v-else>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-5">
            <p class="text-gray-400 text-sm">Artistas Dilo</p>
            <h2 class="text-3xl font-bold text-[#ffa236] mt-2">{{ stats.catalog.artists }}</h2>
          </div>
          <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-5">
            <p class="text-gray-400 text-sm">Artistas externos</p>
            <h2 class="text-3xl font-bold text-[#ffa236] mt-2">{{ stats.catalog.other_artists }}</h2>
          </div>
          <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-5">
            <p class="text-gray-400 text-sm">Canciones</p>
            <h2 class="text-3xl font-bold text-[#ffa236] mt-2">{{ stats.catalog.tracks }}</h2>
          </div>
          <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-5">
            <p class="text-gray-400 text-sm">Lanzamientos</p>
            <h2 class="text-3xl font-bold text-[#ffa236] mt-2">{{ stats.catalog.releases }}</h2>
          </div>
          <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-5">
            <p class="text-gray-400 text-sm">Composiciones</p>
            <h2 class="text-3xl font-bold text-[#ffa236] mt-2">{{ stats.catalog.compositions }}</h2>
          </div>
          <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-5">
            <p class="text-gray-400 text-sm">Eventos totales</p>
            <h2 class="text-3xl font-bold text-[#ffa236] mt-2">{{ stats.operations.events_total }}</h2>
          </div>
          <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-5">
            <p class="text-gray-400 text-sm">Próximos eventos</p>
            <h2 class="text-3xl font-bold text-[#ffa236] mt-2">{{ stats.operations.events_upcoming }}</h2>
          </div>
          <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-5">
            <p class="text-gray-400 text-sm">Eventos este mes</p>
            <h2 class="text-3xl font-bold text-[#ffa236] mt-2">{{ stats.operations.events_this_month }}</h2>
          </div>
        </div>

        <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6">
          <h2 class="text-lg font-semibold text-[#ffa236] mb-4">Resumen financiero global</h2>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-lg border border-[#2a2a2a] bg-[#111111] p-4">
              <p class="text-xs text-gray-500 uppercase">Ingresos de eventos</p>
              <p class="mt-2 text-xl font-semibold text-emerald-400">{{ formatUsd(stats.finance.event_income_usd) }}</p>
            </div>
            <div class="rounded-lg border border-[#2a2a2a] bg-[#111111] p-4">
              <p class="text-xs text-gray-500 uppercase">Gastos de eventos</p>
              <p class="mt-2 text-xl font-semibold text-red-400">{{ formatUsd(stats.finance.event_expenses_usd) }}</p>
            </div>
            <div class="rounded-lg border border-[#2a2a2a] bg-[#111111] p-4">
              <p class="text-xs text-gray-500 uppercase">Resultado neto eventos</p>
              <p
                class="mt-2 text-xl font-semibold"
                :class="Number(stats.finance.event_net_usd) >= 0 ? 'text-[#ffa236]' : 'text-red-400'"
              >
                {{ formatUsd(stats.finance.event_net_usd) }}
              </p>
            </div>
            <div class="rounded-lg border border-[#2a2a2a] bg-[#111111] p-4">
              <p class="text-xs text-gray-500 uppercase">Nómina mensual</p>
              <p class="mt-2 text-xl font-semibold text-white">{{ formatUsd(stats.finance.payroll_month_usd) }}</p>
            </div>
            <div class="rounded-lg border border-[#2a2a2a] bg-[#111111] p-4">
              <p class="text-xs text-gray-500 uppercase">Nómina anual</p>
              <p class="mt-2 text-xl font-semibold text-white">{{ formatUsd(stats.finance.payroll_year_usd) }}</p>
            </div>
            <div class="rounded-lg border border-[#2a2a2a] bg-[#111111] p-4">
              <p class="text-xs text-gray-500 uppercase">Royalties procesadas (current)</p>
              <p class="mt-2 text-xl font-semibold text-[#ffa236]">{{ formatUsd(stats.finance.royalties_current_usd) }}</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6 lg:col-span-2">
            <h2 class="text-lg font-semibold text-[#ffa236] mb-4">Próximos eventos</h2>

            <ul v-if="stats.events.length" class="divide-y divide-[#2a2a2a]">
              <li
                v-for="event in stats.events"
                :key="event.id"
                class="py-3 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between text-sm text-gray-300"
              >
                <span>{{ event.title }}</span>
                <span class="text-gray-500">
                  {{ formatDate(event.event_date) }}
                  <template v-if="event.city || event.country">
                    · {{ event.city }}{{ event.city && event.country ? "," : "" }} {{ event.country }}
                  </template>
                </span>
              </li>
            </ul>

            <p v-else class="text-gray-500 text-sm">No hay próximos eventos registrados.</p>
          </div>

          <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6">
            <h2 class="text-lg font-semibold text-[#ffa236] mb-4">Alertas y control</h2>
            <ul class="space-y-3 text-sm">
              <li class="flex items-center justify-between border-b border-[#2a2a2a] pb-2">
                <span class="text-gray-400">Statements de royalties vigentes</span>
                <span class="font-semibold text-white">{{ stats.health.royalty_statements_current }}</span>
              </li>
              <li class="flex items-center justify-between border-b border-[#2a2a2a] pb-2">
                <span class="text-gray-400">Líneas royalties sin match</span>
                <span class="font-semibold text-red-300">{{ stats.health.royalty_unmatched_lines }}</span>
              </li>
              <li class="flex items-center justify-between border-b border-[#2a2a2a] pb-2">
                <span class="text-gray-400">Invitaciones externas pendientes</span>
                <span class="font-semibold text-white">{{ stats.health.pending_external_invitations }}</span>
              </li>
              <li class="flex items-center justify-between border-b border-[#2a2a2a] pb-2">
                <span class="text-gray-400">Splits master activos</span>
                <span class="font-semibold text-white">{{ stats.health.active_master_splits }}</span>
              </li>
              <li class="flex items-center justify-between">
                <span class="text-gray-400">Splits composición activos</span>
                <span class="font-semibold text-white">{{ stats.health.active_composition_splits }}</span>
              </li>
            </ul>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-5 text-center">
            <p class="text-gray-400 text-sm">Cobertura</p>
            <h2 class="text-3xl font-bold text-[#ffa236] mt-2">
              {{ stats.operations.countries_count }} países · {{ stats.operations.cities_count }} ciudades
            </h2>
          </div>
          <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-5 text-center">
            <p class="text-gray-400 text-sm">Estructura operativa</p>
            <h2 class="text-3xl font-bold text-[#ffa236] mt-2">
              {{ stats.operations.organizers }} empresarios · {{ stats.operations.workers }} trabajadores
            </h2>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6">
            <h2 class="text-lg font-semibold text-[#ffa236] mb-4">Top países por eventos</h2>
            <ul v-if="stats.top_countries.length" class="divide-y divide-[#2a2a2a]">
              <li
                v-for="country in stats.top_countries"
                :key="country.label"
                class="py-2 flex items-center justify-between text-sm text-gray-300"
              >
                <span>{{ country.label }}</span>
                <span class="text-[#ffa236] font-semibold">{{ country.total }}</span>
              </li>
            </ul>
            <p v-else class="text-gray-500 text-sm">Sin datos de países aún.</p>
          </div>

          <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6">
            <h2 class="text-lg font-semibold text-[#ffa236] mb-4">Top formatos de show</h2>
            <ul v-if="stats.top_event_types.length" class="divide-y divide-[#2a2a2a]">
              <li
                v-for="eventType in stats.top_event_types"
                :key="eventType.label"
                class="py-2 flex items-center justify-between text-sm text-gray-300"
              >
                <span>{{ eventType.label }}</span>
                <span class="text-[#ffa236] font-semibold">{{ eventType.total }}</span>
              </li>
            </ul>
            <p v-else class="text-gray-500 text-sm">Sin formatos registrados aún.</p>
          </div>
        </div>

        <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6">
          <h2 class="text-lg font-semibold text-[#ffa236] mb-4">Solicitudes de payout pendientes</h2>
          <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <p class="text-sm text-gray-400">Solicitudes en estado pendiente/aprobado</p>
            <div class="text-right">
              <p class="text-[#ffa236] text-2xl font-bold">{{ stats.finance.payout_pending_count }}</p>
              <p class="text-gray-400 text-sm">{{ formatUsd(stats.finance.payout_pending_usd) }}</p>
            </div>
          </div>
        </div>
      </template>
    </div>
  </AdminLayout>
</template>

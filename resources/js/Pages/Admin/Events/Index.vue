<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DangerConfirmModal from "@/Components/DangerConfirmModal.vue";
import RowActionMenu from "@/Components/RowActionMenu.vue";
import { Link, router } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const props = defineProps({
  events: Object,
  artists: Array,
  canManageEvents: { type: Boolean, default: false },
  canSeeFinance: { type: Boolean, default: false },
  analytics: {
    type: Object,
    default: () => null,
  },
  analyticsFilters: {
    type: Object,
    default: () => ({ date_from: "", date_to: "", artist_id: "todos" }),
  },
});

const selectedArtist = ref(props.analyticsFilters?.artist_id || "todos");
const deleteModalOpen = ref(false);
const deleteProcessing = ref(false);
const pendingEventId = ref(null);
const analyticsDateFrom = ref(props.analyticsFilters?.date_from || "");
const analyticsDateTo = ref(props.analyticsFilters?.date_to || "");

const eventsList = computed(() => props.events?.data ?? props.events ?? []);

const analyticsData = computed(() => {
  if (props.analytics) return props.analytics;

  return {
    events_count: 0,
    total_income_usd: 0,
    country_summary: [],
    city_summary: [],
    event_type_summary: [],
    organizer_ranking: [],
    period_summaries: {
      monthly: { from: "", to: "", events_count: 0, total_income_usd: 0 },
      quarterly: { from: "", to: "", events_count: 0, total_income_usd: 0 },
      yearly: { from: "", to: "", events_count: 0, total_income_usd: 0 },
    },
  };
});

const formatMoney = (value, currency = "USD") => {
  const n = Number(value ?? 0);
  if (Number.isNaN(n)) return `${currency} 0,00`;
  try {
    return new Intl.NumberFormat("es-ES", { style: "currency", currency }).format(n);
  } catch (e) {
    return `${currency} ${n.toFixed(2)}`;
  }
};

const formatDate = (value) => {
  if (!value) return "—";
  return new Date(`${value}T00:00:00`).toLocaleDateString("es-CO");
};

const filteredEvents = computed(() => {
  return eventsList.value;
});

const totals = computed(() => {
  let totalPaid = 0;
  let totalExpenses = 0;
  let artistShare = 0;
  let labelShare = 0;

  for (const ev of filteredEvents.value) {
    totalPaid += Number(ev.total_paid_base ?? ev.totalPaid ?? ev.total_paid ?? 0);
    totalExpenses += Number(ev.total_expenses_base ?? ev.totalExpenses ?? ev.total_expenses ?? 0);
    artistShare += Number(ev.artist_share_estimated_base ?? ev.artist_share_estimated ?? ev.artist_share ?? 0);
    labelShare += Number(ev.label_share_estimated_base ?? ev.label_share_estimated ?? ev.label_share ?? 0);
  }

  const net = totalPaid - totalExpenses;

  return {
    totalPaid,
    totalExpenses,
    net,
    artistShare,
    labelShare,
  };
});

const canManageEvents = computed(() => !!props.canManageEvents);

const openDeleteEventModal = (eventId) => {
  pendingEventId.value = eventId;
  deleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  if (deleteProcessing.value) return;
  deleteModalOpen.value = false;
  pendingEventId.value = null;
};

const deleteEvent = () => {
  if (!pendingEventId.value || deleteProcessing.value) return;

  deleteProcessing.value = true;
  router.delete(route("admin.events.destroy", pendingEventId.value), {
    preserveScroll: true,
    onError: () => {
      alert("No fue posible eliminar el evento. Intenta nuevamente.");
    },
    onFinish: () => {
      deleteProcessing.value = false;
      closeDeleteModal();
    },
  });
};

const applyAnalyticsFilters = () => {
  router.get(
    route("admin.events.index"),
    {
      date_from: analyticsDateFrom.value || undefined,
      date_to: analyticsDateTo.value || undefined,
      artist_id: selectedArtist.value !== "todos" ? Number(selectedArtist.value) : undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};

const resetAnalyticsFilters = () => {
  const today = new Date();
  const year = today.getFullYear();
  const todayString = today.toISOString().slice(0, 10);

  analyticsDateFrom.value = `${year}-01-01`;
  analyticsDateTo.value = todayString;
  applyAnalyticsFilters();
};

const clearAllFilters = () => {
  analyticsDateFrom.value = "";
  analyticsDateTo.value = "";

  const changedArtist = selectedArtist.value !== "todos";
  selectedArtist.value = "todos";

  if (!changedArtist) {
    applyAnalyticsFilters();
  }
};

watch(selectedArtist, (value, previousValue) => {
  if (value === previousValue) return;
  applyAnalyticsFilters();
});
</script>

<template>
  <AdminLayout title="Eventos">
    <div class="flex justify-between items-start mb-6 gap-4 flex-wrap">
      <div class="space-y-3">
        <h1 class="text-2xl font-semibold text-white">Eventos</h1>
        <div class="flex items-center gap-3">
          <label class="text-sm text-gray-400">Filtrar dashboard por artista:</label>
          <select
            v-model="selectedArtist"
            class="bg-[#1c1c1c] text-white border border-[#2a2a2a] rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#ffa236]"
          >
            <option value="todos">Todos los artistas</option>
            <option v-for="artist in artists" :key="artist.id" :value="artist.id">
              {{ artist.name }}
            </option>
          </select>
          <button
            type="button"
            class="rounded-md border border-[#2a2a2a] px-3 py-2 text-sm font-semibold text-gray-200 hover:bg-[#2a2a2a]"
            @click="clearAllFilters"
          >
            Limpiar filtros
          </button>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <Link
          v-if="canManageEvents"
          :href="route('admin.events.trash')"
          class="bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors"
        >
          Papelera
        </Link>
        <Link
          v-if="canManageEvents"
          :href="route('admin.events.create')"
          class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors"
        >
          + Nuevo evento
        </Link>
      </div>
    </div>

    <section v-if="canSeeFinance" class="space-y-5 mb-8">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <h2 class="text-lg font-semibold text-[#ffa236]">Dashboard de eventos</h2>
          <p class="text-sm text-gray-400">Analytics por ubicación, formato, organizador e ingresos.</p>
        </div>
        <form class="grid grid-cols-1 sm:grid-cols-3 gap-2" @submit.prevent="applyAnalyticsFilters">
          <label class="text-xs text-gray-400 flex flex-col gap-1">
            Desde
            <input
              v-model="analyticsDateFrom"
              type="date"
              class="rounded-md border border-[#2a2a2a] bg-black px-3 py-2 text-sm text-white"
            />
          </label>
          <label class="text-xs text-gray-400 flex flex-col gap-1">
            Hasta
            <input
              v-model="analyticsDateTo"
              type="date"
              class="rounded-md border border-[#2a2a2a] bg-black px-3 py-2 text-sm text-white"
            />
          </label>
          <div class="flex items-end gap-2">
            <button
              type="submit"
              class="rounded-md bg-[#ffa236] px-4 py-2 text-sm font-semibold text-black hover:bg-[#ffb54d]"
            >
              Aplicar
            </button>
            <button
              type="button"
              class="rounded-md border border-[#2a2a2a] px-4 py-2 text-sm font-semibold text-gray-200 hover:bg-[#2a2a2a]"
              @click="resetAnalyticsFilters"
            >
              Este año
            </button>
          </div>
        </form>
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-[#2a2a2a] bg-[#1d1d1b] p-4">
          <p class="text-xs text-gray-500 uppercase">Eventos en rango</p>
          <p class="mt-2 text-2xl font-semibold text-white">{{ analyticsData.events_count }}</p>
        </div>
        <div class="rounded-xl border border-[#2a2a2a] bg-[#1d1d1b] p-4">
          <p class="text-xs text-gray-500 uppercase">Ingreso total (USD)</p>
          <p class="mt-2 text-2xl font-semibold text-green-300">{{ formatMoney(analyticsData.total_income_usd) }}</p>
        </div>
        <div class="rounded-xl border border-[#2a2a2a] bg-[#1d1d1b] p-4">
          <p class="text-xs text-gray-500 uppercase">Corte mensual</p>
          <p class="mt-2 text-xl font-semibold text-white">
            {{ formatMoney(analyticsData.period_summaries.monthly.total_income_usd) }}
          </p>
          <p class="text-xs text-gray-500 mt-1">{{ analyticsData.period_summaries.monthly.events_count }} eventos</p>
          <p class="text-[11px] text-gray-600 mt-1">
            {{ formatDate(analyticsData.period_summaries.monthly.from) }} - {{ formatDate(analyticsData.period_summaries.monthly.to) }}
          </p>
        </div>
        <div class="rounded-xl border border-[#2a2a2a] bg-[#1d1d1b] p-4">
          <p class="text-xs text-gray-500 uppercase">Corte trimestral</p>
          <p class="mt-2 text-xl font-semibold text-white">
            {{ formatMoney(analyticsData.period_summaries.quarterly.total_income_usd) }}
          </p>
          <p class="text-xs text-gray-500 mt-1">{{ analyticsData.period_summaries.quarterly.events_count }} eventos</p>
          <p class="text-[11px] text-gray-600 mt-1">
            {{ formatDate(analyticsData.period_summaries.quarterly.from) }} - {{ formatDate(analyticsData.period_summaries.quarterly.to) }}
          </p>
        </div>
      </div>

      <div class="rounded-xl border border-[#2a2a2a] bg-[#1d1d1b] p-4">
        <p class="text-xs text-gray-500 uppercase">Corte anual</p>
        <p class="mt-2 text-xl font-semibold text-white">
          {{ formatMoney(analyticsData.period_summaries.yearly.total_income_usd) }}
        </p>
        <p class="text-xs text-gray-500 mt-1">{{ analyticsData.period_summaries.yearly.events_count }} eventos</p>
        <p class="text-[11px] text-gray-600 mt-1">
          {{ formatDate(analyticsData.period_summaries.yearly.from) }} - {{ formatDate(analyticsData.period_summaries.yearly.to) }}
        </p>
      </div>

      <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="rounded-xl border border-[#2a2a2a] bg-[#1d1d1b] p-4">
          <h3 class="text-base font-semibold text-[#ffa236]">Resumen por país</h3>
          <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm text-gray-300">
              <thead class="border-b border-[#2a2a2a] text-left text-gray-400">
                <tr>
                  <th class="px-2 py-2">País</th>
                  <th class="px-2 py-2">Eventos</th>
                  <th class="px-2 py-2">Ciudades</th>
                  <th class="px-2 py-2">Total USD</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in analyticsData.country_summary" :key="row.country" class="border-b border-[#2a2a2a]">
                  <td class="px-2 py-2">{{ row.country }}</td>
                  <td class="px-2 py-2">{{ row.events_count }}</td>
                  <td class="px-2 py-2">{{ row.cities_count }}</td>
                  <td class="px-2 py-2">{{ formatMoney(row.total_income_usd) }}</td>
                </tr>
                <tr v-if="analyticsData.country_summary.length === 0">
                  <td colspan="4" class="px-2 py-4 text-center text-gray-500">Sin datos en este rango.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="rounded-xl border border-[#2a2a2a] bg-[#1d1d1b] p-4">
          <h3 class="text-base font-semibold text-[#ffa236]">Resumen por ciudad</h3>
          <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm text-gray-300">
              <thead class="border-b border-[#2a2a2a] text-left text-gray-400">
                <tr>
                  <th class="px-2 py-2">Ciudad</th>
                  <th class="px-2 py-2">País</th>
                  <th class="px-2 py-2">Eventos</th>
                  <th class="px-2 py-2">Total USD</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in analyticsData.city_summary.slice(0, 12)"
                  :key="`${row.country}-${row.city}`"
                  class="border-b border-[#2a2a2a]"
                >
                  <td class="px-2 py-2">{{ row.city }}</td>
                  <td class="px-2 py-2">{{ row.country }}</td>
                  <td class="px-2 py-2">{{ row.events_count }}</td>
                  <td class="px-2 py-2">{{ formatMoney(row.total_income_usd) }}</td>
                </tr>
                <tr v-if="analyticsData.city_summary.length === 0">
                  <td colspan="4" class="px-2 py-4 text-center text-gray-500">Sin datos en este rango.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="rounded-xl border border-[#2a2a2a] bg-[#1d1d1b] p-4">
          <h3 class="text-base font-semibold text-[#ffa236]">Formato de show</h3>
          <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm text-gray-300">
              <thead class="border-b border-[#2a2a2a] text-left text-gray-400">
                <tr>
                  <th class="px-2 py-2">Formato</th>
                  <th class="px-2 py-2">Eventos</th>
                  <th class="px-2 py-2">Total USD</th>
                  <th class="px-2 py-2">Media USD</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in analyticsData.event_type_summary"
                  :key="row.event_type"
                  class="border-b border-[#2a2a2a]"
                >
                  <td class="px-2 py-2">{{ row.event_type }}</td>
                  <td class="px-2 py-2">{{ row.events_count }}</td>
                  <td class="px-2 py-2">{{ formatMoney(row.total_income_usd) }}</td>
                  <td class="px-2 py-2">{{ formatMoney(row.avg_income_usd) }}</td>
                </tr>
                <tr v-if="analyticsData.event_type_summary.length === 0">
                  <td colspan="4" class="px-2 py-4 text-center text-gray-500">Sin datos en este rango.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="rounded-xl border border-[#2a2a2a] bg-[#1d1d1b] p-4">
          <h3 class="text-base font-semibold text-[#ffa236]">Ranking de organizadores</h3>
          <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm text-gray-300">
              <thead class="border-b border-[#2a2a2a] text-left text-gray-400">
                <tr>
                  <th class="px-2 py-2">Organizador</th>
                  <th class="px-2 py-2">Eventos</th>
                  <th class="px-2 py-2">Total USD</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in analyticsData.organizer_ranking.slice(0, 12)"
                  :key="row.organizer"
                  class="border-b border-[#2a2a2a]"
                >
                  <td class="px-2 py-2">{{ row.organizer }}</td>
                  <td class="px-2 py-2">{{ row.events_count }}</td>
                  <td class="px-2 py-2">{{ formatMoney(row.total_income_usd) }}</td>
                </tr>
                <tr v-if="analyticsData.organizer_ranking.length === 0">
                  <td colspan="3" class="px-2 py-4 text-center text-gray-500">Sin datos en este rango.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </section>

    <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 mb-6">
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Ingresos totales</p>
        <p class="text-xl font-semibold text-white">{{ formatMoney(totals.totalPaid, 'USD') }}</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Gastos totales</p>
        <p class="text-xl font-semibold text-red-400">{{ formatMoney(totals.totalExpenses, 'USD') }}</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Resultado neto</p>
        <p class="text-xl font-semibold text-white">{{ formatMoney(totals.net, 'USD') }}</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Total para artistas</p>
        <p class="text-xl font-semibold text-[#ffa236]">{{ formatMoney(totals.artistShare, 'USD') }}</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Total para disquera</p>
        <p class="text-xl font-semibold text-gray-100">{{ formatMoney(totals.labelShare, 'USD') }}</p>
      </div>
    </div>

    <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
      <table class="min-w-full text-sm text-gray-300">
        <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
          <tr>
            <th class="px-4 py-2 text-left">Título</th>
            <th class="px-4 py-2 text-left">Fecha</th>
            <th class="px-4 py-2 text-left">Ubicación</th>
            <th class="px-4 py-2 text-left">Tipo</th>
            <th class="px-4 py-2 text-left">Estado</th>
            <th v-if="canManageEvents" class="px-4 py-2 text-left">Fee / Moneda</th>
            <th v-if="canManageEvents" class="px-4 py-2 text-left">% Anticipo</th>
            <th class="px-4 py-2 text-left">Pago final</th>
            <th class="px-4 py-2 text-left">Artista Principal</th>
            <th class="px-4 py-2 text-right">{{ canManageEvents ? 'Acciones' : 'Finanzas' }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="event in filteredEvents" :key="event.id" class="border-b border-[#2a2a2a] hover:bg-[#181818]">
            <td class="px-4 py-3 font-medium">{{ event.title }}</td>
            <td class="px-4 py-3">
              {{ event.event_date ? new Date(event.event_date).toLocaleDateString('es-ES') : "-" }}
            </td>
            <td class="px-4 py-3">
              <div class="text-s">
                <p v-if="event.city || event.country">
                  {{ event.city }}{{ event.city && event.country ? ',' : '' }} {{ event.country }}
                </p>
                <p v-if="event.location">{{ event.location }}</p>
                <p v-if="event.venue_address" class="text-gray-500 text-s">Venue: {{ event.venue_address }}</p>
                <p v-if="!event.city && !event.country && !event.location && !event.venue_address">—</p>
              </div>
            </td>
            <td class="px-4 py-3">
              <span v-if="event.event_type" class="bg-[#2a2a2a] px-2 py-1 rounded text-s capitalize">
                {{ event.event_type }}
              </span>
              <span v-else class="text-gray-500">—</span>
            </td>
            <td class="px-4 py-3">
              <span
                :class="[
                  'px-2 py-1 rounded-full text-s font-semibold',
                  event.status === 'pagado'
                    ? 'bg-green-500/20 text-green-300'
                    : event.status === 'confirmado'
                      ? 'bg-blue-500/20 text-blue-300'
                    : event.status === 'pospuesto'
                      ? 'bg-orange-500/20 text-orange-300'
                    : event.status === 'cancelado'
                      ? 'bg-red-500/20 text-red-300'
                      : 'bg-yellow-500/20 text-yellow-300'
                ]"
              >
                {{ event.status || 'Sin estado' }}
              </span>
            </td>
            <td v-if="canManageEvents" class="px-4 py-3">
              <div class="text-xs">
                <p v-if="event.show_fee_total">
                  {{ event.currency || 'USD' }} {{ Number(event.show_fee_total).toFixed(2) }}
                </p>
                <p v-else>—</p>
              </div>
            </td>
            <td v-if="canManageEvents" class="px-4 py-3">
              <span class="text-xs">{{ event.advance_percentage ?? 50 }}%</span>
            </td>
            <td class="px-4 py-3">
              {{ event.full_payment_due_date ? new Date(event.full_payment_due_date).toLocaleDateString('es-ES') : '—' }}
            </td>
            <td class="px-4 py-3">
              {{ event.main_artist?.name || event.mainArtist?.name || "-" }}
            </td>
            <td class="px-4 py-3 text-right">
              <RowActionMenu v-if="canManageEvents" label="Acciones del evento">
                <Link
                  :href="route('admin.events.edit', event.id)"
                  class="block rounded px-3 py-2 text-sm text-gray-200 hover:bg-white/10"
                >
                  Editar
                </Link>
                <Link
                  :href="route('admin.events.finance', event.id)"
                  class="block rounded px-3 py-2 text-sm text-green-300 hover:bg-white/10"
                >
                  Finanzas
                </Link>
                <button
                  type="button"
                  class="block w-full rounded px-3 py-2 text-left text-sm text-red-300 hover:bg-red-500/20"
                  @click="openDeleteEventModal(event.id)"
                >
                  Mover a papelera
                </button>
              </RowActionMenu>
              <Link
                v-else
                :href="route('admin.events.finance', event.id)"
                class="text-green-400 hover:underline text-sm"
              >
                Finanzas
              </Link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <DangerConfirmModal
      :show="deleteModalOpen"
      title="Mover evento a papelera"
      message="El evento se moverá a la papelera para restauración posterior."
      confirm-label="Mover a papelera"
      :processing="deleteProcessing"
      @close="closeDeleteModal"
      @confirm="deleteEvent"
    />
  </AdminLayout>
</template>

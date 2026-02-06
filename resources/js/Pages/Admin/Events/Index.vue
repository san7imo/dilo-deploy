<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link, usePage } from "@inertiajs/vue3";
import axios from 'axios'
import { computed, ref, watch } from 'vue'
import { formatMoney } from "@/utils/money";

const props = defineProps({
  events: Object,
  artists: Array,
  canManageEvents: { type: Boolean, default: false },
  canSeeFinance: { type: Boolean, default: false },
});

const { props: pageProps } = usePage();
const roleNames = computed(() => pageProps.auth?.user?.role_names || []);
const isAdmin = computed(() => roleNames.value.includes("admin"));

const selectedArtist = ref('todos');
const selectedPeriod = ref('todos');
const dateFrom = ref('');
const dateTo = ref('');

const formatCurrency = (value, currency = 'USD') => formatMoney(value, currency);

// Local reactive copy of the events list so we can perform optimistic updates
const localEvents = ref((props.events && props.events.data) ? [...props.events.data] : []);

// Keep localEvents in sync if the props change (e.g., pagination or Inertia reload)
watch(() => props.events, (newVal) => {
  localEvents.value = (newVal && newVal.data) ? [...newVal.data] : (newVal || []);
});

const normalizeDate = (value) => {
  if (!value) return null
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return null
  date.setHours(0, 0, 0, 0)
  return date
}

const getPeriodRange = () => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  if (selectedPeriod.value === 'personalizado') {
    return {
      start: normalizeDate(dateFrom.value),
      end: normalizeDate(dateTo.value),
    }
  }

  const year = today.getFullYear()
  const month = today.getMonth()

  switch (selectedPeriod.value) {
    case 'mes_actual':
      return {
        start: new Date(year, month, 1),
        end: new Date(year, month + 1, 0),
      }
    case 'ultimo_mes':
      return {
        start: new Date(year, month - 1, 1),
        end: new Date(year, month, 0),
      }
    case 'ultimos_3_meses':
      return {
        start: new Date(year, month - 2, 1),
        end: new Date(year, month + 1, 0),
      }
    case 'este_ano':
      return {
        start: new Date(year, 0, 1),
        end: new Date(year, 11, 31),
      }
    default:
      return { start: null, end: null }
  }
}

const isWithinRange = (date, range) => {
  if (!date) return false
  if (range.start && date < range.start) return false
  if (range.end && date > range.end) return false
  return true
}

const isPaidEvent = (event) => {
  const status = String(event.status || '').toLowerCase()
  return status === 'pagado' || !!event.is_paid
}

const isUpcomingEvent = (event) => {
  if (typeof event.is_upcoming === 'boolean') return event.is_upcoming
  const date = normalizeDate(event.event_date)
  if (!date) return false
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return date > today
}

const isPastEvent = (event) => {
  if (typeof event.is_past === 'boolean') return event.is_past
  const date = normalizeDate(event.event_date)
  if (!date) return false
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return date < today
}

const filteredEvents = computed(() => {
  const items = localEvents.value.length
    ? localEvents.value
    : (props.events && props.events.data)
      ? props.events.data
      : (props.events || [])

  const periodRange = getPeriodRange()

  return items.filter(ev => {
    if (selectedArtist.value !== 'todos') {
      const mainArtistId = ev.main_artist?.id || ev.mainArtist?.id || ev.main_artist_id
      if (mainArtistId != selectedArtist.value) return false
    }

    if (periodRange.start || periodRange.end) {
      const eventDate = normalizeDate(ev.event_date)
      if (!isWithinRange(eventDate, periodRange)) return false
    }

    return true
  })
})

const generalTotals = computed(() => {
  const totalEvents = filteredEvents.value.length
  const upcomingEvents = filteredEvents.value.filter(isUpcomingEvent).length
  const pastEvents = filteredEvents.value.filter(isPastEvent).length
  const paidEvents = filteredEvents.value.filter(isPaidEvent).length
  const pendingEvents = Math.max(totalEvents - paidEvents, 0)
  const totalFee = filteredEvents.value.reduce(
    (sum, ev) => sum + Number(ev.show_fee_total ?? 0),
    0
  )

  return {
    totalEvents,
    upcomingEvents,
    pastEvents,
    paidEvents,
    pendingEvents,
    totalFee,
  }
})

const totals = computed(() => {
  let totalPaid = 0
  let totalExpenses = 0
  let artistShare = 0
  let labelShare = 0

  for (const ev of filteredEvents.value) {
    totalPaid += Number(ev.total_paid_base ?? ev.totalPaid ?? ev.total_paid ?? 0)
    totalExpenses += Number(ev.total_expenses_base ?? ev.totalExpenses ?? ev.total_expenses ?? 0)
    artistShare += Number(ev.artist_share_estimated_base ?? ev.artist_share_estimated ?? ev.artist_share ?? 0)
    labelShare += Number(ev.label_share_estimated_base ?? ev.label_share_estimated ?? ev.label_share ?? 0)
  }

  const net = totalPaid - totalExpenses
  const artistPct = net !== 0 ? (artistShare / (net || 1)) * 100 : 0
  const labelPct = net !== 0 ? (labelShare / (net || 1)) * 100 : 0

  return {
    totalPaid,
    totalExpenses,
    net,
    artistShare,
    labelShare,
    artistPct,
    labelPct,
  }
})

const canManageEvents = computed(() => !!props.canManageEvents);
const canSeeFinance = computed(() => !!props.canSeeFinance);

watch(selectedPeriod, (value) => {
  if (value !== 'personalizado') {
    dateFrom.value = '';
    dateTo.value = '';
  }
});

// Keep the paginator object in sync if present (we'll rely on Inertia for full reloads)
const getIndexInLocal = (id) => localEvents.value.findIndex(e => e.id === id);

const deleteEvent = async (eventId) => {
  if (!confirm('¿Eliminar este evento? Esta acción no se puede deshacer.')) return;

  const idx = getIndexInLocal(eventId);
  if (idx === -1) return;

  // Keep a backup to revert in case of failure
  const backup = [...localEvents.value];

  // Optimistic removal from local list
  localEvents.value.splice(idx, 1);

  try {
    // Send DELETE request via axios. Laravel may redirect; we ignore redirect and
    // treat non-2xx as failure.
    const res = await axios.delete(route('admin.events.destroy', eventId));

    // If server returns a redirect (302) the browser won't follow it for XHR, but
    // typically a successful deletion will return a 200/204 or redirect. If the
    // server redirected back to index, we might prefer to force a client reload.
    if (res.status >= 200 && res.status < 300) {
      // Success: optionally show a small alert
      // We rely on server flash messages or you can implement a toast here.
    } else {
      throw new Error('Server returned unexpected status: ' + res.status);
    }
  } catch (err) {
    // Revert optimistic update
    localEvents.value = backup;

    // Show a simple alert on failure. Could be replaced with a nicer toast.
    alert('No fue posible eliminar el evento. Intenta nuevamente.');
    console.error('Error deleting event', err);
  }
}
</script>

<template>
  <AdminLayout title="Eventos">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-white mb-3">🎫 Eventos</h1>
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex items-center gap-3">
            <label class="text-sm text-gray-400">Filtrar por artista:</label>
            <select v-model="selectedArtist"
              class="bg-[#1c1c1c] text-white border border-[#2a2a2a] rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#ffa236]">
              <option value="todos">Todos los artistas</option>
              <option v-for="artist in artists" :key="artist.id" :value="artist.id">
                {{ artist.name }}
              </option>
            </select>
          </div>
          <div class="flex items-center gap-3">
            <label class="text-sm text-gray-400">Periodo:</label>
            <select v-model="selectedPeriod"
              class="bg-[#1c1c1c] text-white border border-[#2a2a2a] rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#ffa236]">
              <option value="todos">Todos</option>
              <option value="mes_actual">Este mes</option>
              <option value="ultimo_mes">Mes pasado</option>
              <option value="ultimos_3_meses">Últimos 3 meses</option>
              <option value="este_ano">Este año</option>
              <option value="personalizado">Personalizado</option>
            </select>
          </div>
          <div v-if="selectedPeriod === 'personalizado'" class="flex items-center gap-2">
            <input v-model="dateFrom" type="date"
              class="bg-[#1c1c1c] text-white border border-[#2a2a2a] rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#ffa236]"
              aria-label="Desde" />
            <span class="text-gray-500 text-sm">—</span>
            <input v-model="dateTo" type="date"
              class="bg-[#1c1c1c] text-white border border-[#2a2a2a] rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#ffa236]"
              aria-label="Hasta" />
          </div>
        </div>
      </div>
      <Link v-if="canManageEvents" :href="route('admin.events.create')"
        class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors">+ Nuevo
        evento</Link>
    </div>

    <div v-if="isAdmin" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Total eventos</p>
        <p class="text-xl font-semibold text-white">{{ generalTotals.totalEvents }}</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Próximos</p>
        <p class="text-xl font-semibold text-emerald-300">{{ generalTotals.upcomingEvents }}</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Pasados</p>
        <p class="text-xl font-semibold text-gray-100">{{ generalTotals.pastEvents }}</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Pagados</p>
        <p class="text-xl font-semibold text-green-300">{{ generalTotals.paidEvents }}</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Pendientes</p>
        <p class="text-xl font-semibold text-yellow-300">{{ generalTotals.pendingEvents }}</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Fee negociado</p>
        <p class="text-xl font-semibold text-white">{{ formatCurrency(generalTotals.totalFee, 'USD') }}</p>
      </div>
    </div>

    <!-- Financial summary -->
    <div v-if="isAdmin" class="grid grid-cols-1 sm:grid-cols-5 gap-4 mb-6">
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Ingresos totales</p>
        <p class="text-xl font-semibold text-white">{{ formatCurrency(totals.totalPaid, 'USD') }}</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Gastos totales</p>
        <p class="text-xl font-semibold text-red-400">{{ formatCurrency(totals.totalExpenses, 'USD') }}</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Resultado neto</p>
        <p class="text-xl font-semibold text-white">{{ formatCurrency(totals.net, 'USD') }}</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">70% Artista</p>
        <p class="text-xl font-semibold text-[#ffa236]">{{ formatCurrency(totals.artistShare, 'USD') }}</p>
        <p class="text-xs text-gray-400">{{ Math.round(totals.artistPct) }}%</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">30% Compañía</p>
        <p class="text-xl font-semibold text-gray-100">{{ formatCurrency(totals.labelShare, 'USD') }}</p>
        <p class="text-xs text-gray-400">{{ Math.round(totals.labelPct) }}%</p>
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
            <th v-if="isAdmin" class="px-4 py-2 text-left">Fee / Moneda</th>
            <th v-if="isAdmin" class="px-4 py-2 text-left">% Anticipo</th>
            <th v-if="isAdmin" class="px-4 py-2 text-left">Pago final</th>
            <th class="px-4 py-2 text-left">Artista Principal</th>
            <th class="px-4 py-2 text-right">
              {{ canManageEvents ? 'Acciones' : canSeeFinance ? 'Finanzas' : '' }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filteredEvents.length === 0">
            <td
              :colspan="isAdmin ? 10 : 7"
              class="px-4 py-6 text-center text-gray-500"
            >
              No hay eventos con los filtros seleccionados.
            </td>
          </tr>
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
              <span :class="[
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
              ]">
                {{ event.status || 'Sin estado' }}
              </span>
            </td>
            <td v-if="isAdmin" class="px-4 py-3">
              <div class="text-xs">
                <p v-if="event.show_fee_total">
                  {{ formatCurrency(event.show_fee_total, event.currency || 'USD') }}
                </p>
                <p v-else>—</p>
              </div>
            </td>
            <td v-if="isAdmin" class="px-4 py-3">
              <span class="text-xs">{{ event.advance_percentage ?? 50 }}%</span>
            </td>
            <td v-if="isAdmin" class="px-4 py-3">
              {{ event.full_payment_due_date ? new Date(event.full_payment_due_date).toLocaleDateString('es-ES') : '—'
              }}
            </td>
            <td class="px-4 py-3">
              {{ event.main_artist?.name || event.mainArtist?.name || "-" }}
            </td>
            <td class="px-4 py-3 text-right space-x-2">
              <Link v-if="canManageEvents" :href="route('admin.events.edit', event.id)" class="text-[#ffa236] hover:underline text-sm">
                Editar
              </Link>

              <Link
                v-if="canSeeFinance"
                :href="route('admin.events.finance', event.id)"
                class="text-green-400 hover:underline text-sm"
              >
                Finanzas
              </Link>

              <button
                v-if="canManageEvents"
                type="button"
                class="text-red-400 hover:underline text-sm"
                @click.prevent="deleteEvent(event.id)"
              >
                Eliminar
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationLinks v-if="props.events && props.events.links" :links="props.events.links" :meta="props.events.meta" class="justify-end mt-4" />
  </AdminLayout>
</template>

<style scoped>
.btn-primary {
  background-color: #ffa236;
  color: #000000;
  font-weight: 600;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  transition: background-color .2s;
}

.btn-primary:hover {
  background-color: #ffb54d;
}
</style>

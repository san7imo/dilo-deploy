<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link } from "@inertiajs/vue3";
import axios from 'axios'
import { computed, ref, watch } from 'vue'

const props = defineProps({
  events: Object,
  artists: Array,
  canManageEvents: { type: Boolean, default: false },
});

const selectedArtist = ref('todos');

const formatMoney = (value, currency = 'USD') => {
  const n = Number(value ?? 0)
  if (Number.isNaN(n)) return `${currency} 0,00`
  try {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency }).format(n)
  } catch (e) {
    return `${currency} ${n.toFixed(2)}`
  }
}

const filteredEvents = computed(() => {
  const items = (props.events && props.events.data) ? props.events.data : (props.events || [])

  if (selectedArtist.value === 'todos') {
    return items
  }

  return items.filter(ev => {
    const mainArtistId = ev.main_artist?.id || ev.mainArtist?.id || ev.main_artist_id
    return mainArtistId == selectedArtist.value
  })
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

// Form helper to perform deletes (pattern used elsewhere in the app)
// Local reactive copy of the events list so we can perform optimistic updates
const localEvents = ref((props.events && props.events.data) ? [...props.events.data] : []);

// Keep localEvents in sync if the props change (e.g., pagination or Inertia reload)
watch(() => props.events, (newVal) => {
  localEvents.value = (newVal && newVal.data) ? [...newVal.data] : (newVal || []);
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
      </div>
      <Link v-if="canManageEvents" :href="route('admin.events.create')"
        class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors">+ Nuevo
        evento</Link>
    </div>

    <!-- Financial summary -->
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
        <p class="text-xs text-gray-400">70% Artista</p>
        <p class="text-xl font-semibold text-[#ffa236]">{{ formatMoney(totals.artistShare, 'USD') }}</p>
        <p class="text-xs text-gray-400">{{ Math.round(totals.artistPct) }}%</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">30% Compañía</p>
        <p class="text-xl font-semibold text-gray-100">{{ formatMoney(totals.labelShare, 'USD') }}</p>
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
            <th v-if="canManageEvents" class="px-4 py-2 text-left">Fee / Moneda</th>
            <th v-if="canManageEvents" class="px-4 py-2 text-left">% Anticipo</th>
            <th class="px-4 py-2 text-left">Pago final</th>
            <th class="px-4 py-2 text-left">Artista Principal</th>
            <th class="px-4 py-2 text-right">{{ canManageEvents ? 'Acciones' : 'Finanzas' }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="event in localEvents" :key="event.id" class="border-b border-[#2a2a2a] hover:bg-[#181818]">
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

              <Link :href="route('admin.events.finance', event.id)" class="text-green-400 hover:underline text-sm">
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

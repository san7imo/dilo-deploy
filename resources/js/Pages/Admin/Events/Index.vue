<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link } from "@inertiajs/vue3";
import { computed, ref } from 'vue'

const props = defineProps({
  events: Object,
  artists: Array
});

const selectedArtist = ref('todos');

const formatMoney = (value, currency = 'EUR') => {
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
      <Link :href="route('admin.events.create')"
        class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors">+ Nuevo
        evento</Link>
    </div>

    <!-- Financial summary -->
    <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 mb-6">
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Ingresos totales</p>
        <p class="text-xl font-semibold text-white">{{ formatMoney(totals.totalPaid, 'EUR') }}</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Gastos totales</p>
        <p class="text-xl font-semibold text-red-400">{{ formatMoney(totals.totalExpenses, 'EUR') }}</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">Resultado neto</p>
        <p class="text-xl font-semibold text-white">{{ formatMoney(totals.net, 'EUR') }}</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">70% Artista</p>
        <p class="text-xl font-semibold text-[#ffa236]">{{ formatMoney(totals.artistShare, 'EUR') }}</p>
        <p class="text-xs text-gray-400">{{ Math.round(totals.artistPct) }}%</p>
      </div>
      <div class="rounded-lg p-4 bg-[#0f0f0f] border border-[#232323]">
        <p class="text-xs text-gray-400">30% Compañía</p>
        <p class="text-xl font-semibold text-gray-100">{{ formatMoney(totals.labelShare, 'EUR') }}</p>
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
            <th class="px-4 py-2 text-left">Fee / Moneda</th>
            <th class="px-4 py-2 text-left">% Anticipo</th>
            <th class="px-4 py-2 text-left">Pago final</th>
            <th class="px-4 py-2 text-left">Artista Principal</th>
            <th class="px-4 py-2 text-right">Acciones</th>
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
              <span :class="[
                'px-2 py-1 rounded-full text-s font-semibold',
                event.status === 'pagado'
                  ? 'bg-green-500/20 text-green-300'
                  : event.status === 'confirmado'
                    ? 'bg-blue-500/20 text-blue-300'
                    : event.status === 'cancelado'
                      ? 'bg-red-500/20 text-red-300'
                      : 'bg-yellow-500/20 text-yellow-300'
              ]">
                {{ event.status || 'Sin estado' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="text-xs">
                <p v-if="event.show_fee_total">
                  {{ event.currency || 'EUR' }} {{ Number(event.show_fee_total).toFixed(2) }}
                </p>
                <p v-else>—</p>
              </div>
            </td>
            <td class="px-4 py-3">
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
              <Link :href="route('admin.events.edit', event.id)" class="text-[#ffa236] hover:underline text-sm">
                Editar
              </Link>

              <Link :href="route('admin.events.finance', event.id)" class="text-green-400 hover:underline text-sm">
                Finanzas
              </Link>
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

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link } from "@inertiajs/vue3";

defineProps({ events: Object });
</script>

<template>
  <AdminLayout title="Eventos">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold text-white">🎫 Eventos</h1>
      <Link :href="route('admin.events.create')" class="btn-primary">+ Nuevo evento</Link>

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
          <tr v-for="event in events.data" :key="event.id" class="border-b border-[#2a2a2a] hover:bg-[#181818]">
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
  @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}
</style>

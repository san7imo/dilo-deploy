<script setup>
import ArtistLayout from "@/Layouts/ArtistLayout.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link, router } from "@inertiajs/vue3";
import { ref } from "vue";

defineProps({
  tracks: Object,
  royalty_overview: { type: Object, default: () => ({}) },
});

const requestingPayment = ref(false);

const formatMoney = (value) => {
  const number = Number(value ?? 0);
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: 2,
    maximumFractionDigits: 6,
  }).format(Number.isNaN(number) ? 0 : number);
};

const requestRoyaltyPayout = () => {
  if (requestingPayment.value) return;

  requestingPayment.value = true;
  router.post(route("artist.royalties.payout-requests.store"), {}, {
    preserveScroll: true,
    onFinish: () => {
      requestingPayment.value = false;
    },
  });
};
</script>

<template>
  <ArtistLayout>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold text-white">Mis canciones</h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
      <div class="bg-[#111] border border-[#2a2a2a] rounded-lg p-4">
        <p class="text-gray-400 text-sm">Total regalías</p>
        <p class="text-[#ffa236] text-2xl font-semibold">{{ formatMoney(royalty_overview.total_accrued_usd) }}</p>
      </div>
      <div class="bg-[#111] border border-[#2a2a2a] rounded-lg p-4">
        <p class="text-gray-400 text-sm">Total pagado</p>
        <p class="text-[#ffa236] text-2xl font-semibold">{{ formatMoney(royalty_overview.total_paid_usd) }}</p>
      </div>
      <div class="bg-[#111] border border-[#2a2a2a] rounded-lg p-4">
        <p class="text-gray-400 text-sm">Pendiente en solicitudes</p>
        <p class="text-[#ffa236] text-2xl font-semibold">{{ formatMoney(royalty_overview.pending_requested_usd) }}</p>
      </div>
      <div class="bg-[#111] border border-[#2a2a2a] rounded-lg p-4">
        <p class="text-gray-400 text-sm">Disponible para solicitar</p>
        <p class="text-[#ffa236] text-2xl font-semibold">{{ formatMoney(royalty_overview.available_to_request_usd) }}</p>
      </div>
    </div>

    <div class="bg-[#111] border border-[#2a2a2a] rounded-lg p-4 mb-6">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-gray-300 text-sm">
            Mínimo para solicitar pago: <strong>USD {{ Number(royalty_overview.minimum_threshold_usd ?? 50).toFixed(2) }}</strong>
          </p>
          <p v-if="royalty_overview.last_request" class="text-xs text-gray-500 mt-1">
            Última solicitud: {{ royalty_overview.last_request.status }} · {{ formatMoney(royalty_overview.last_request.requested_amount_usd) }}
          </p>
        </div>
        <button
          type="button"
          class="bg-[#ffa236] text-black font-semibold px-4 py-2 rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!royalty_overview.can_request_payment || requestingPayment"
          @click="requestRoyaltyPayout"
        >
          {{ requestingPayment ? "Enviando..." : "Solicitar pago" }}
        </button>
      </div>
      <p v-if="!royalty_overview.can_request_payment" class="text-xs text-amber-300 mt-2">
        Necesitas al menos USD 50.00 disponibles para habilitar la solicitud.
      </p>
    </div>

    <div v-if="royalty_overview.period_totals?.length" class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow mb-6">
      <table class="min-w-full text-sm text-gray-300">
        <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
          <tr>
            <th class="px-4 py-2 text-left">Periodo</th>
            <th class="px-4 py-2 text-left">Total USD</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="period in royalty_overview.period_totals"
            :key="`${period.reporting_month_date}-${period.reporting_period}`"
            class="border-b border-[#2a2a2a] hover:bg-[#181818]"
          >
            <td class="px-4 py-3">{{ period.reporting_period || period.reporting_month_date || "-" }}</td>
            <td class="px-4 py-3">{{ formatMoney(period.total_usd) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
      <table class="min-w-full text-sm text-gray-300">
        <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
          <tr>
            <th class="px-4 py-2 text-left">Track</th>
            <th class="px-4 py-2 text-left">ISRC</th>
            <th class="px-4 py-2 text-left">Release</th>
            <th class="px-4 py-2 text-left">Units</th>
            <th class="px-4 py-2 text-left">Mi total USD</th>
            <th class="px-4 py-2 text-left">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="track in tracks.data"
            :key="track.id"
            class="border-b border-[#2a2a2a] hover:bg-[#181818]"
          >
            <td class="px-4 py-3">{{ track.title }}</td>
            <td class="px-4 py-3">{{ track.isrc || "-" }}</td>
            <td class="px-4 py-3">{{ track.release?.title || "-" }}</td>
            <td class="px-4 py-3">{{ track.total_units ?? 0 }}</td>
            <td class="px-4 py-3">{{ formatMoney(track.total_royalties_usd) }}</td>
            <td class="px-4 py-3">
              <Link
                :href="route('artist.tracks.royalties.index', track.id)"
                class="text-[#ffa236] hover:underline"
              >
                Ver regalías
              </Link>
            </td>
          </tr>
          <tr v-if="!tracks.data?.length">
            <td colspan="6" class="px-4 py-6 text-center text-gray-400">
              No tienes canciones disponibles.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationLinks v-if="tracks.links" :links="tracks.links" :meta="tracks.meta" class="justify-end mt-4" />
  </ArtistLayout>
</template>

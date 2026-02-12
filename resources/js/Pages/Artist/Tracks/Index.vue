<script setup>
import ArtistLayout from "@/Layouts/ArtistLayout.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link } from "@inertiajs/vue3";

defineProps({
  tracks: Object,
});

const formatMoney = (value) => {
  const number = Number(value ?? 0);
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: 2,
    maximumFractionDigits: 6,
  }).format(Number.isNaN(number) ? 0 : number);
};
</script>

<template>
  <ArtistLayout>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold text-white">Mis canciones</h1>
    </div>

    <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
      <table class="min-w-full text-sm text-gray-300">
        <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
          <tr>
            <th class="px-4 py-2 text-left">Track</th>
            <th class="px-4 py-2 text-left">ISRC</th>
            <th class="px-4 py-2 text-left">Release</th>
            <th class="px-4 py-2 text-left">Units</th>
            <th class="px-4 py-2 text-left">Total USD</th>
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

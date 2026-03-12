<script setup>
import ArtistLayout from "@/Layouts/ArtistLayout.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link } from "@inertiajs/vue3";

defineProps({
  compositions: Object,
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
      <h1 class="text-2xl font-semibold text-white">Mis composiciones</h1>
    </div>

    <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
      <table class="min-w-full text-sm text-gray-300">
        <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
          <tr>
            <th class="px-4 py-2 text-left">Composición</th>
            <th class="px-4 py-2 text-left">ISWC</th>
            <th class="px-4 py-2 text-left">Tracks</th>
            <th class="px-4 py-2 text-left">Statements</th>
            <th class="px-4 py-2 text-left">Mi total (USD)</th>
            <th class="px-4 py-2 text-left">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="composition in compositions.data"
            :key="composition.id"
            class="border-b border-[#2a2a2a] hover:bg-[#181818]"
          >
            <td class="px-4 py-3">{{ composition.title }}</td>
            <td class="px-4 py-3">{{ composition.iswc || "-" }}</td>
            <td class="px-4 py-3">{{ composition.tracks_count ?? 0 }}</td>
            <td class="px-4 py-3">{{ composition.statements_count ?? 0 }}</td>
            <td class="px-4 py-3">{{ formatMoney(composition.total_my_share_usd) }}</td>
            <td class="px-4 py-3">
              <Link
                :href="route('artist.compositions.royalties.index', composition.id)"
                class="text-[#ffa236] hover:underline"
              >
                Ver regalías
              </Link>
            </td>
          </tr>
          <tr v-if="!compositions.data?.length">
            <td colspan="6" class="px-4 py-6 text-center text-gray-400">
              No tienes composiciones disponibles.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationLinks v-if="compositions.links" :links="compositions.links" :meta="compositions.meta" class="justify-end mt-4" />
  </ArtistLayout>
</template>

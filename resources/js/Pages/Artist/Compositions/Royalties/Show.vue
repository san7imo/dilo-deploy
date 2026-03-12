<script setup>
import ArtistLayout from "@/Layouts/ArtistLayout.vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  composition: Object,
  cards: Array,
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
      <div>
        <h1 class="text-2xl font-semibold text-white">Regalías de composición · {{ composition.title }}</h1>
        <p class="text-gray-400 text-sm">
          ISWC: {{ composition.iswc || "-" }} · Tracks: {{ composition.tracks?.length ?? 0 }}
        </p>
      </div>
      <Link :href="route('artist.compositions.index')" class="text-gray-400 hover:text-white">
        Volver
      </Link>
    </div>

    <div v-if="!cards.length" class="bg-[#0f0f0f] border border-[#2a2a2a] rounded-lg p-6 text-gray-300">
      No hay statements procesados para esta composición.
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div
        v-for="card in cards"
        :key="card.statement_id"
        class="bg-[#0f0f0f] border border-[#2a2a2a] rounded-lg p-5"
      >
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-lg font-semibold text-white">
            {{ card.reporting_period || "Sin periodo" }}
          </h3>
          <Link
            :href="route('artist.compositions.royalties.detail', [composition.id, card.statement_id])"
            class="text-[#ffa236] hover:underline text-sm"
          >
            Ver detalles
          </Link>
        </div>

        <div class="space-y-2 text-sm text-gray-300">
          <div class="flex justify-between">
            <span class="text-gray-500">Total composición (USD)</span>
            <span class="text-white font-semibold">{{ formatMoney(card.total_composition_usd) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Units</span>
            <span>{{ card.units_total }}</span>
          </div>
          <div v-if="card.my_pct !== null" class="flex justify-between">
            <span class="text-gray-500">Mi %</span>
            <span>{{ Number(card.my_pct).toFixed(2) }}%</span>
          </div>
          <div v-else-if="card.my_pct_variable" class="flex justify-between">
            <span class="text-gray-500">Mi %</span>
            <span>Variable por vigencia</span>
          </div>
          <div v-if="card.my_share_usd !== null" class="flex justify-between">
            <span class="text-gray-500">Mi parte (USD)</span>
            <span class="text-white font-semibold">{{ formatMoney(card.my_share_usd) }}</span>
          </div>
          <div v-else class="text-xs text-[#ffa236] mt-3">
            Split no definido para ti
          </div>
        </div>
      </div>
    </div>
  </ArtistLayout>
</template>

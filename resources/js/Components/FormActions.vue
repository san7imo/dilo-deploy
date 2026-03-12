<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
  cancelHref: {
    type: String,
    default: "",
  },
  cancelLabel: {
    type: String,
    default: "Cancelar",
  },
  submitLabel: {
    type: String,
    default: "Guardar",
  },
  processingLabel: {
    type: String,
    default: "Guardando...",
  },
  submitType: {
    type: String,
    default: "submit",
  },
  processing: {
    type: Boolean,
    default: false,
  },
});
</script>

<template>
  <div class="flex flex-wrap items-center justify-end gap-3">
    <Link
      v-if="cancelHref"
      :href="cancelHref"
      class="bg-transparent hover:bg-white/5 text-gray-200 font-semibold px-4 py-2 rounded-md transition-colors border border-[#2a2a2a]"
    >
      {{ cancelLabel }}
    </Link>

    <button
      :type="submitType"
      class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
      :disabled="processing"
    >
      {{ processing ? processingLabel : submitLabel }}
    </button>
  </div>
</template>

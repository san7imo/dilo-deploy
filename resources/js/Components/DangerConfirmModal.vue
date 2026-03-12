<script setup>
import { computed, ref, watch } from "vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: "Confirmar eliminación" },
  message: { type: String, default: "Esta acción enviará el registro a la papelera." },
  confirmLabel: { type: String, default: "Eliminar" },
  confirmKeyword: { type: String, default: "ELIMINAR" },
  requireKeyword: { type: Boolean, default: true },
  processing: { type: Boolean, default: false },
});

const emit = defineEmits(["close", "confirm"]);

const keyword = ref("");

watch(
  () => props.show,
  (value) => {
    if (value) {
      keyword.value = "";
    }
  }
);

const canConfirm = computed(() => {
  if (props.processing) return false;
  if (!props.requireKeyword) return true;

  return keyword.value.trim().toUpperCase() === props.confirmKeyword.trim().toUpperCase();
});
</script>

<template>
  <Modal :show="show" max-width="lg" @close="emit('close')">
    <div class="space-y-4 text-white">
      <div class="flex items-start gap-3">
        <div class="mt-1 text-red-400">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
          <h3 class="text-lg font-semibold">{{ title }}</h3>
          <p class="text-sm text-gray-300 mt-1">{{ message }}</p>
        </div>
      </div>

      <div v-if="requireKeyword" class="space-y-2">
        <p class="text-xs text-gray-400">
          Para continuar escribe <span class="font-semibold text-white">{{ confirmKeyword }}</span>.
        </p>
        <input
          v-model="keyword"
          type="text"
          class="w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236]"
          :placeholder="confirmKeyword"
        />
      </div>

      <div class="flex items-center justify-end gap-3 pt-2">
        <button
          type="button"
          class="bg-transparent hover:bg-white/5 text-gray-200 font-semibold px-4 py-2 rounded-md transition-colors border border-[#2a2a2a]"
          :disabled="processing"
          @click="emit('close')"
        >
          Cancelar
        </button>
        <button
          type="button"
          class="bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-500/30 font-semibold px-4 py-2 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!canConfirm"
          @click="emit('confirm')"
        >
          {{ processing ? "Procesando..." : confirmLabel }}
        </button>
      </div>
    </div>
  </Modal>
</template>

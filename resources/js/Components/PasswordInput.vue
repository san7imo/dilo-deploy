<script setup>
import { computed, ref } from "vue";

const props = defineProps({
  id: { type: String, default: "" },
  modelValue: { type: String, default: "" },
  placeholder: { type: String, default: "" },
  autocomplete: { type: String, default: "current-password" },
  required: { type: Boolean, default: false },
  inputClass: { type: String, default: "" },
});

const emit = defineEmits(["update:modelValue"]);
const show = ref(false);

const type = computed(() => (show.value ? "text" : "password"));
const buttonLabel = computed(() => (show.value ? "Ocultar contraseña" : "Mostrar contraseña"));
</script>

<template>
  <div class="relative">
    <input
      :id="id || undefined"
      :value="modelValue"
      :type="type"
      :placeholder="placeholder"
      :autocomplete="autocomplete"
      :required="required"
      :class="[
        'w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236] pr-11',
        inputClass,
      ]"
      @input="emit('update:modelValue', $event.target.value)"
    />

    <button
      type="button"
      class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-white transition-colors"
      :aria-label="buttonLabel"
      @click="show = !show"
    >
      <i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
    </button>
  </div>
</template>

<style scoped>
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0 1000px #0f0f0f inset !important;
  box-shadow: 0 0 0 1000px #0f0f0f inset !important;
  -webkit-text-fill-color: #ffffff !important;
  caret-color: #ffffff;
}
</style>

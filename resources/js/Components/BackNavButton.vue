<script setup>
import { router } from "@inertiajs/vue3";

const props = defineProps({
  href: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    default: "Volver",
  },
  useHistory: {
    type: Boolean,
    default: true,
  },
});

const handleBack = () => {
  if (
    props.useHistory &&
    typeof window !== "undefined" &&
    window.history.length > 1
  ) {
    window.history.back();
    return;
  }

  router.visit(props.href);
};
</script>

<template>
  <button
    type="button"
    class="inline-flex items-center gap-2 text-sm font-medium text-gray-300 hover:text-white transition-colors"
    @click="handleBack"
  >
    <span aria-hidden="true">←</span>
    <span>{{ label }}</span>
  </button>
</template>

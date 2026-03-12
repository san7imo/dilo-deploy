<script setup>
import { useForm } from "@inertiajs/vue3";
import FormActions from "@/Components/FormActions.vue";

const props = defineProps({
  genre: {
    type: Object,
    default: () => ({}),
  },
  mode: {
    type: String,
    default: "create",
  },
  cancelHref: {
    type: String,
    default: "",
  },
});

const form = useForm({
  name: props.genre.name || "",
});

const handleSubmit = () => {
  if (props.mode === "edit") {
    form.put(route("admin.genres.update", props.genre.id));
  } else {
    form.post(route("admin.genres.store"));
  }
};
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <div>
      <label class="block text-sm font-medium text-gray-300 mb-1"
        >Nombre del género</label
      >
      <input
        v-model="form.name"
        type="text"
        class="w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236]"
        placeholder="Ejemplo: Pop, Rock, Reggaetón..."
      />
      <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">
        {{ form.errors.name }}
      </p>
    </div>

    <FormActions
      :cancel-href="props.cancelHref"
      :submit-label="props.mode === 'edit' ? 'Actualizar' : 'Guardar'"
      :processing="form.processing"
    />
  </form>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
  roadManager: { type: Object, default: () => ({}) },
  mode: { type: String, default: "create" },
});

const defaultVerified =
  props.mode === "edit"
    ? !!props.roadManager.email_verified_at
    : true;

const form = useForm({
  name: props.roadManager.name || "",
  email: props.roadManager.email || "",
  password: "",
  email_verified: defaultVerified,
});

const handleSubmit = () => {
  if (props.mode === "edit") {
    form.put(route("admin.roadmanagers.update", props.roadManager.id));
  } else {
    form.post(route("admin.roadmanagers.store"));
  }
};
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1">Nombre</label>
        <input v-model="form.name" type="text" class="input" placeholder="Nombre completo" />
        <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">
          {{ form.errors.name }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1">Correo</label>
        <input v-model="form.email" type="email" class="input" placeholder="correo@empresa.com" />
        <p v-if="form.errors.email" class="text-red-500 text-sm mt-1">
          {{ form.errors.email }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1">Contrasena</label>
        <input v-model="form.password" type="password" class="input" placeholder="Minimo 8 caracteres" />
        <p v-if="form.errors.password" class="text-red-500 text-sm mt-1">
          {{ form.errors.password }}
        </p>
        <p v-if="props.mode === 'edit'" class="text-xs text-gray-500 mt-1">
          Deja en blanco para no cambiarla.
        </p>
      </div>

      <div class="flex items-center gap-2 mt-6">
        <input
          id="email_verified"
          v-model="form.email_verified"
          type="checkbox"
          class="h-4 w-4 rounded border-[#2a2a2a] bg-[#0f0f0f] text-[#ffa236] focus:ring-[#ffa236]"
        />
        <label for="email_verified" class="text-sm text-gray-300">
          Marcar correo como verificado
        </label>
      </div>
    </div>

    <div class="flex justify-end">
      <button
        type="submit"
        class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors"
      >
        {{ props.mode === "edit" ? "Actualizar" : "Guardar" }}
      </button>
    </div>
  </form>
</template>

<style scoped>
.input {
  @apply w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}
</style>

<script setup>
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
  collaborator: { type: Object, default: () => ({}) },
  mode: { type: String, default: "create" },
});

const form = useForm({
  account_holder: props.collaborator.account_holder || "",
  holder_id: props.collaborator.holder_id || "",
  account_type: props.collaborator.account_type || "Ahorros",
  bank: props.collaborator.bank || "",
  address: props.collaborator.address || "",
  phone: props.collaborator.phone || "",
  account_number: props.collaborator.account_number || "",
  country: props.collaborator.country || "",
});

const handleSubmit = () => {
  if (props.mode === "edit") {
    form.put(route("admin.collaborators.update", props.collaborator.id));
  } else {
    form.post(route("admin.collaborators.store"));
  }
};
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1">Titular de la cuenta</label>
        <input v-model="form.account_holder" type="text" class="input" placeholder="Nombre completo" />
        <p v-if="form.errors.account_holder" class="text-red-500 text-sm mt-1">
          {{ form.errors.account_holder }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1">ID del titular</label>
        <input v-model="form.holder_id" type="text" class="input" placeholder="Documento o ID" />
        <p v-if="form.errors.holder_id" class="text-red-500 text-sm mt-1">
          {{ form.errors.holder_id }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1">Tipo de cuenta</label>
        <input v-model="form.account_type" type="text" class="input" placeholder="Ahorros" />
        <p v-if="form.errors.account_type" class="text-red-500 text-sm mt-1">
          {{ form.errors.account_type }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1">Banco</label>
        <input v-model="form.bank" type="text" class="input" placeholder="Bancolombia" />
        <p v-if="form.errors.bank" class="text-red-500 text-sm mt-1">
          {{ form.errors.bank }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1">Dirección</label>
        <input v-model="form.address" type="text" class="input" placeholder="Dirección" />
        <p v-if="form.errors.address" class="text-red-500 text-sm mt-1">
          {{ form.errors.address }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1">Celular</label>
        <input v-model="form.phone" type="tel" class="input" placeholder="Ej: +57 300 123 4567" />
        <p v-if="form.errors.phone" class="text-red-500 text-sm mt-1">
          {{ form.errors.phone }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1">Número de cuenta</label>
        <input v-model="form.account_number" type="text" class="input" placeholder="Número" />
        <p v-if="form.errors.account_number" class="text-red-500 text-sm mt-1">
          {{ form.errors.account_number }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1">País</label>
        <input v-model="form.country" type="text" class="input" placeholder="País" />
        <p v-if="form.errors.country" class="text-red-500 text-sm mt-1">
          {{ form.errors.country }}
        </p>
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

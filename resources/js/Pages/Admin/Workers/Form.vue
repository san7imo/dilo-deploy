<script setup>
import { useForm } from "@inertiajs/vue3";
import FormActions from "@/Components/FormActions.vue";

const props = defineProps({
  worker: {
    type: Object,
    default: () => ({}),
  },
  positionOptions: {
    type: Array,
    default: () => [],
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
  full_name: props.worker.full_name || "",
  document_type: props.worker.document_type || "",
  document_number: props.worker.document_number || "",
  position: props.worker.position || "",
  address: props.worker.address || "",
  bank_name: props.worker.bank_name || "",
  account_number: props.worker.account_number || "",
  whatsapp: props.worker.whatsapp || "",
  email: props.worker.email || "",
  notes: props.worker.notes || "",
});

const handleSubmit = () => {
  if (props.mode === "edit") {
    form.put(route("admin.workers.update", props.worker.id));
    return;
  }

  form.post(route("admin.workers.store"));
};
</script>

<template>
  <form class="space-y-6" @submit.prevent="handleSubmit">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="text-gray-300 text-sm">Nombre completo</label>
        <input v-model="form.full_name" type="text" class="input" placeholder="Nombre del trabajador" />
        <p v-if="form.errors.full_name" class="text-red-500 text-sm mt-1">{{ form.errors.full_name }}</p>
      </div>
      <div>
        <label class="text-gray-300 text-sm">Cargo</label>
        <select v-model="form.position" class="input">
          <option value="">Seleccionar cargo</option>
          <option v-for="option in positionOptions" :key="option" :value="option">{{ option }}</option>
        </select>
        <p v-if="form.errors.position" class="text-red-500 text-sm mt-1">{{ form.errors.position }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="text-gray-300 text-sm">Tipo de documento</label>
        <input v-model="form.document_type" type="text" class="input" placeholder="CC, CE, PAS, NIT..." />
        <p v-if="form.errors.document_type" class="text-red-500 text-sm mt-1">{{ form.errors.document_type }}</p>
      </div>
      <div>
        <label class="text-gray-300 text-sm">Número de documento</label>
        <input v-model="form.document_number" type="text" class="input" placeholder="Documento" />
        <p v-if="form.errors.document_number" class="text-red-500 text-sm mt-1">{{ form.errors.document_number }}</p>
      </div>
    </div>

    <div>
      <label class="text-gray-300 text-sm">Dirección</label>
      <input v-model="form.address" type="text" class="input" placeholder="Dirección" />
      <p v-if="form.errors.address" class="text-red-500 text-sm mt-1">{{ form.errors.address }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="text-gray-300 text-sm">Banco</label>
        <input v-model="form.bank_name" type="text" class="input" placeholder="Nombre del banco" />
        <p v-if="form.errors.bank_name" class="text-red-500 text-sm mt-1">{{ form.errors.bank_name }}</p>
      </div>
      <div>
        <label class="text-gray-300 text-sm">Número de cuenta</label>
        <input v-model="form.account_number" type="text" class="input" placeholder="Cuenta bancaria" />
        <p v-if="form.errors.account_number" class="text-red-500 text-sm mt-1">{{ form.errors.account_number }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="text-gray-300 text-sm">WhatsApp</label>
        <input v-model="form.whatsapp" type="text" class="input" placeholder="+57 300 000 0000" />
        <p v-if="form.errors.whatsapp" class="text-red-500 text-sm mt-1">{{ form.errors.whatsapp }}</p>
      </div>
      <div>
        <label class="text-gray-300 text-sm">Correo</label>
        <input v-model="form.email" type="email" class="input" placeholder="correo@empresa.com" />
        <p v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</p>
      </div>
    </div>

    <div>
      <label class="text-gray-300 text-sm">Notas</label>
      <textarea v-model="form.notes" rows="4" class="input" placeholder="Información adicional del trabajador"></textarea>
      <p v-if="form.errors.notes" class="text-red-500 text-sm mt-1">{{ form.errors.notes }}</p>
    </div>

    <FormActions
      :cancel-href="props.cancelHref"
      :submit-label="props.mode === 'edit' ? 'Actualizar trabajador' : 'Guardar trabajador'"
      :processing="form.processing"
    />
  </form>
</template>

<style scoped>
.input {
  @apply w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}
</style>

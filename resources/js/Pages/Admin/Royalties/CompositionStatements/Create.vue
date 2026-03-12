<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import FormActions from "@/Components/FormActions.vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
  providers: { type: Array, default: () => [] },
});

const form = useForm({
  provider: props.providers?.[0]?.value || "manual_dilo",
  source_name: "manual_dilo_template",
  reporting_period: "",
  file: null,
});

const handleSubmit = () => {
  form.post(route("admin.royalties.composition-statements.store"), {
    forceFormData: true,
  });
};
</script>

<template>
  <AdminLayout title="Upload Composition CSV">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold text-white">Upload CSV de composición</h1>
      <BackNavButton :href="route('admin.royalties.composition-statements.index')" />
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6 max-w-xl">
      <div>
        <label class="text-gray-300 text-sm">Proveedor</label>
        <select v-model="form.provider" class="input">
          <option v-for="provider in providers" :key="provider.value" :value="provider.value">
            {{ provider.label }}
          </option>
        </select>
        <p v-if="form.errors.provider" class="text-red-500 text-sm mt-1">
          {{ form.errors.provider }}
        </p>
      </div>

      <div>
        <label class="text-gray-300 text-sm">Origen / fuente</label>
        <input
          v-model="form.source_name"
          type="text"
          class="input"
          placeholder="ASCAP / MLC / Manual Dilo"
        />
        <p v-if="form.errors.source_name" class="text-red-500 text-sm mt-1">
          {{ form.errors.source_name }}
        </p>
      </div>

      <div>
        <label class="text-gray-300 text-sm">Periodo (ej: SEP-25)</label>
        <input
          v-model="form.reporting_period"
          type="text"
          class="input"
          placeholder="SEP-25"
        />
        <p v-if="form.errors.reporting_period" class="text-red-500 text-sm mt-1">
          {{ form.errors.reporting_period }}
        </p>
      </div>

      <div>
        <label class="text-gray-300 text-sm">Archivo CSV</label>
        <input
          type="file"
          accept=".csv,text/csv,.txt"
          class="input-file"
          @change="(e) => { form.file = e.target.files[0]; }"
        />
        <p class="text-xs text-gray-500 mt-2">
          Usa la plantilla estándar Dilo para evitar columnas inválidas.
        </p>
        <a :href="route('admin.royalties.composition-statements.template')" class="text-xs text-[#ffa236] hover:underline">
          Descargar plantilla
        </a>
        <p v-if="form.errors.file" class="text-red-500 text-sm mt-1">
          {{ form.errors.file }}
        </p>
      </div>

      <FormActions
        :cancel-href="route('admin.royalties.composition-statements.index')"
        submit-label="Subir"
        processing-label="Subiendo..."
        :processing="form.processing"
      />
    </form>
  </AdminLayout>
</template>

<style scoped>
.input {
  @apply w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}
.input-file {
  @apply block w-full text-sm text-gray-400 border border-[#2a2a2a] rounded-md cursor-pointer bg-[#0f0f0f] file:bg-[#ffa236] file:text-black file:px-3 file:py-1;
}
</style>


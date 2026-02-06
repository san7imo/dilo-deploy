<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
  providers: { type: Array, default: () => [] },
});

const form = useForm({
  provider: props.providers?.[0]?.value || "symphonic",
  file: null,
});

const handleSubmit = () => {
  form.post(route("admin.royalties.statements.store"), {
    forceFormData: true,
    onError: () => {},
  });
};
</script>

<template>
  <AdminLayout title="Upload CSV">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold text-white">Upload CSV</h1>
      <Link :href="route('admin.royalties.statements.index')" class="text-gray-400 hover:text-white">
        Volver
      </Link>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6 max-w-xl">
      <div>
        <label class="text-gray-300 text-sm">Proveedor</label>
        <select v-model="form.provider" class="input">
          <option v-for="p in props.providers" :key="p.value" :value="p.value">
            {{ p.label }}
          </option>
        </select>
        <p v-if="form.errors.provider" class="text-red-500 text-sm mt-1">
          {{ form.errors.provider }}
        </p>
      </div>

      <div>
        <label class="text-gray-300 text-sm">Archivo CSV</label>
        <input
          type="file"
          accept=".csv,text/csv"
          class="input-file"
          @change="(e) => { form.file = e.target.files[0]; }"
        />
        <p v-if="form.errors.file" class="text-red-500 text-sm mt-1">
          {{ form.errors.file }}
        </p>
      </div>

      <div class="flex justify-end">
        <button type="submit" class="btn-primary">Subir</button>
      </div>
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
.btn-primary {
  @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}
</style>

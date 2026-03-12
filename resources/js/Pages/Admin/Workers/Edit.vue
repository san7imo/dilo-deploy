<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import Form from "./Form.vue";

defineProps({
  worker: { type: Object, required: true },
  positionOptions: { type: Array, default: () => [] },
});
</script>

<template>
  <AdminLayout title="Editar trabajador">
    <div class="mx-auto max-w-4xl">
      <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-white">Editar trabajador</h1>
        <BackNavButton :href="route('admin.workers.index')" />
      </div>

      <Form
        :worker="worker"
        mode="edit"
        :position-options="positionOptions"
        :cancel-href="route('admin.workers.index')"
      />
    </div>
  </AdminLayout>
</template>

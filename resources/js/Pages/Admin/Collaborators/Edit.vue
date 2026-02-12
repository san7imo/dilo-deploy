<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Form from "./Form.vue";

defineProps({ collaborator: Object });
</script>

<template>
  <AdminLayout title="Editar colaborador">
    <div class="max-w-3xl mx-auto">
      <h1 class="text-2xl font-semibold text-white mb-6">Editar colaborador</h1>
      <Form :collaborator="collaborator" mode="edit" />
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Form from "./Form.vue";
</script>

<template>
  <AdminLayout title="Nuevo colaborador">
    <div class="max-w-3xl mx-auto">
      <h1 class="text-2xl font-semibold text-white mb-6">Nuevo colaborador</h1>
      <Form mode="create" />
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Form from "./Form.vue";

defineProps({ contentManager: Object });
</script>

<template>
  <AdminLayout title="Editar gestor de contenido">
    <div class="max-w-3xl mx-auto">
      <h1 class="text-2xl font-semibold text-white mb-6">Editar gestor de contenido</h1>
      <Form :content-manager="contentManager" mode="edit" />
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import Form from "./Form.vue";

defineProps({ contentManager: Object });
</script>

<template>
  <AdminLayout title="Editar gestor de contenido">
    <div class="max-w-3xl mx-auto">
      <div class="flex items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-semibold text-white">Editar gestor de contenido</h1>
        <BackNavButton :href="route('admin.team.index')" />
      </div>
      <Form :content-manager="contentManager" mode="edit" :cancel-href="route('admin.team.index')" />
    </div>
  </AdminLayout>
</template>

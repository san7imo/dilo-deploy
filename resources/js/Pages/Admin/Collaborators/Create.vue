<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import Form from "./Form.vue";
</script>

<template>
  <AdminLayout title="Nuevo colaborador">
    <div class="max-w-3xl mx-auto">
      <div class="flex items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-semibold text-white">Nuevo colaborador</h1>
        <BackNavButton :href="route('admin.team.index')" />
      </div>
      <Form mode="create" :cancel-href="route('admin.team.index')" />
    </div>
  </AdminLayout>
</template>

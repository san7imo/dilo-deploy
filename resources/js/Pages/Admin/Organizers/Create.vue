<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import Form from "./Form.vue";
</script>

<template>
  <AdminLayout title="Nuevo empresario">
    <div class="mx-auto max-w-4xl">
      <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-white">Nuevo empresario</h1>
        <BackNavButton :href="route('admin.organizers.index')" />
      </div>

      <Form
        mode="create"
        :cancel-href="route('admin.organizers.index')"
      />
    </div>
  </AdminLayout>
</template>

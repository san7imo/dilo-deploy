<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Form from "./Form.vue";

defineProps({ artists: Array });
</script>

<template>
  <AdminLayout title="Nuevo evento">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-2xl font-semibold text-white mb-6">🎤 Nuevo evento</h1>
      <Form :artists="artists" mode="create" />
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Form from "./Form.vue";

defineProps({ event: Object, artists: Array });
</script>

<template>
  <AdminLayout title="Editar evento">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-2xl font-semibold text-white mb-6">✏️ Editar evento</h1>
      <Form :event="event" :artists="artists" mode="edit" />
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Form from "./Form.vue";

const props = defineProps({
  artists: { type: Array, default: () => [] },
});

</script>

<template>
  <AdminLayout title="Crear lanzamiento">
    <div class="max-w-5xl mx-auto">
      <h1 class="text-2xl font-semibold mb-6">Nuevo lanzamiento</h1>
      <Form :artists="artists" mode="create" />
    </div>
  </AdminLayout>
</template>

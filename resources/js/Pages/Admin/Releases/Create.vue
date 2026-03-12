<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import Form from "./Form.vue";

const props = defineProps({
  artists: { type: Array, default: () => [] },
});

</script>

<template>
  <AdminLayout title="Crear lanzamiento">
    <div class="max-w-5xl mx-auto">
      <div class="flex items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-semibold">Nuevo lanzamiento</h1>
        <BackNavButton :href="route('admin.releases.index')" />
      </div>
      <Form :artists="artists" mode="create" :cancel-href="route('admin.releases.index')" />
    </div>
  </AdminLayout>
</template>

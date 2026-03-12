<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import Form from "./Form.vue";

defineProps({
  organizer: { type: Object, required: true },
});
</script>

<template>
  <AdminLayout title="Editar empresario">
    <div class="mx-auto max-w-4xl">
      <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-white">Editar empresario</h1>
        <BackNavButton :href="route('admin.organizers.index')" />
      </div>

      <Form
        :organizer="organizer"
        mode="edit"
        :cancel-href="route('admin.organizers.index')"
      />
    </div>
  </AdminLayout>
</template>

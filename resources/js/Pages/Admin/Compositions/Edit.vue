<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import Form from "./Form.vue";

defineProps({
  composition: { type: Object, required: true },
  tracks: { type: Array, default: () => [] },
});
</script>

<template>
  <AdminLayout title="Editar composición">
    <div class="mx-auto max-w-4xl">
      <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-white">Editar composición</h1>
        <BackNavButton :href="route('admin.compositions.index')" />
      </div>

      <Form
        :composition="composition"
        mode="edit"
        :tracks="tracks"
        :cancel-href="route('admin.compositions.index')"
      />
    </div>
  </AdminLayout>
</template>

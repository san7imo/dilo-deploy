<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import Form from "./Form.vue";

defineProps({ event: Object, artists: Array, roadManagers: Array, organizers: Array });
</script>

<template>
  <AdminLayout title="Editar evento">
    <div class="max-w-4xl mx-auto">
      <div class="flex items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-semibold text-white">✏️ Editar evento</h1>
        <BackNavButton :href="route('admin.events.index')" />
      </div>
      <Form
        :event="event"
        :artists="artists"
        :road-managers="roadManagers"
        :organizers="organizers"
        mode="edit"
        :cancel-href="route('admin.events.index')"
      />
    </div>
  </AdminLayout>
</template>

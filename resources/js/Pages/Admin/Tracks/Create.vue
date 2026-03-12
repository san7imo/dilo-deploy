<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import Form from "./Form.vue";

defineProps({ releases: Array, artists: Array });
</script>

<template>
  <AdminLayout title="Nueva pista">
    <div class="max-w-5xl mx-auto">
      <div class="flex items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-semibold text-white">🎵 Nueva pista</h1>
        <BackNavButton :href="route('admin.tracks.index')" />
      </div>
      <Form
        :releases="releases"
        :artists="artists"
        mode="create"
        :cancel-href="route('admin.tracks.index')"
      />
    </div>
  </AdminLayout>
</template>

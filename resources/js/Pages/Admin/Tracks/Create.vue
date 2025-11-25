<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Form from "./Form.vue";

defineProps({ releases: Array, artists: Array });
</script>

<template>
  <AdminLayout title="Nueva pista">
    <div class="max-w-5xl mx-auto">
      <h1 class="text-2xl font-semibold text-white mb-6">🎵 Nueva pista</h1>
      <Form :releases="releases" :artists="artists" mode="create" />
    </div>
  </AdminLayout>
</template>

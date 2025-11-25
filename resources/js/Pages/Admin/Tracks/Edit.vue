<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Form from "./Form.vue";

defineProps({ track: Object, releases: Array, artists: Array });
</script>

<template>
  <AdminLayout title="Editar pista">
    <div class="max-w-5xl mx-auto">
      <h1 class="text-2xl font-semibold text-white mb-6">✏️ Editar pista</h1>
      <Form :track="track" :releases="releases" :artists="artists" mode="edit" />
    </div>
  </AdminLayout>
</template>

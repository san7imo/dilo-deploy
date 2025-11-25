<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Form from "./Form.vue";

const props = defineProps({ genres: Array });
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <h1 class="text-2xl font-bold text-[#ffa236]">Nuevo artista</h1>
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6">
        <Form :genres="genres" />
      </div>
    </div>
  </AdminLayout>
</template>

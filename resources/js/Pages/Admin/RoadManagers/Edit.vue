<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Form from "./Form.vue";

const props = defineProps({
  roadManager: Object,
});
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <h1 class="text-2xl font-bold text-[#ffa236]">Editar road manager</h1>
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6">
        <Form :road-manager="roadManager" mode="edit" />
      </div>
    </div>
  </AdminLayout>
</template>

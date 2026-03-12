<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import Form from "./Form.vue";

const props = defineProps({
  roadManager: Object,
});
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-[#ffa236]">Editar road manager</h1>
        <BackNavButton :href="route('admin.team.index')" />
      </div>
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6">
        <Form :road-manager="roadManager" mode="edit" :cancel-href="route('admin.team.index')" />
      </div>
    </div>
  </AdminLayout>
</template>

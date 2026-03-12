<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import Form from "./Form.vue";

const props = defineProps({
  artist: Object,
  genres: Array,
});
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-[#ffa236]">Editar artista</h1>
        <BackNavButton :href="route('admin.artists.index')" />
      </div>
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6">
        <Form
          :artist="artist"
          :genres="genres"
          mode="edit"
          :cancel-href="route('admin.artists.index')"
        />
      </div>
    </div>
  </AdminLayout>
</template>

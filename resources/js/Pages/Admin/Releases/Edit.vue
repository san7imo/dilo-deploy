<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Form from "./Form.vue";

const props = defineProps({
  release: { type: Object, required: true },
  artists: { type: Array,  default: () => [] },
});

</script>

<template>
  <AdminLayout :title="`Editar: ${release.title}`">
    <div class="max-w-5xl mx-auto">
      <h1 class="text-2xl font-semibold mb-6">Editar lanzamiento</h1>
      <Form :release="release" :artists="artists" mode="edit" />
    </div>
  </AdminLayout>
</template>

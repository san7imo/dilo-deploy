<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import Form from "./Form.vue";

const props = defineProps({
  release: { type: Object, required: true },
  artists: { type: Array,  default: () => [] },
});

</script>

<template>
  <AdminLayout :title="`Editar: ${release.title}`">
    <div class="max-w-5xl mx-auto">
      <div class="flex items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-semibold">Editar lanzamiento</h1>
        <BackNavButton :href="route('admin.releases.index')" />
      </div>
      <Form
        :release="release"
        :artists="artists"
        mode="edit"
        :cancel-href="route('admin.releases.index')"
      />
    </div>
  </AdminLayout>
</template>

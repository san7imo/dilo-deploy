<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link, router } from "@inertiajs/vue3";

const props = defineProps({
  genres: Object,
});

const handleDelete = (id) => {
  if (confirm("¿Seguro que deseas eliminar este género?")) {
    router.delete(route("admin.genres.destroy", id));
  }
};
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-[#ffa236]">Géneros musicales</h1>
        <Link
          :href="route('admin.genres.create')"
          class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors"
        >
          + Nuevo género
        </Link>
      </div>

      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-4">
        <table class="w-full text-sm text-gray-300">
          <thead class="text-[#ffa236] text-left border-b border-[#2a2a2a]">
            <tr>
              <th class="py-3 px-4">#</th>
              <th class="py-3 px-4">Nombre</th>
              <th class="py-3 px-4">Slug</th>
              <th class="py-3 px-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="genre in props.genres.data"
              :key="genre.id"
              class="border-b border-[#2a2a2a] hover:bg-[#2a2a2a]/30"
            >
              <td class="py-3 px-4">{{ genre.id }}</td>
              <td class="py-3 px-4">{{ genre.name }}</td>
              <td class="py-3 px-4">{{ genre.slug }}</td>
              <td class="py-3 px-4 text-right space-x-2">
                <Link
                  :href="route('admin.genres.edit', genre.id)"
                  class="text-[#ffa236] hover:text-[#ffb54d]"
                >
                  <i class="fa-solid fa-pen-to-square"></i>
                </Link>
                <button
                  @click="handleDelete(genre.id)"
                  class="text-red-500 hover:text-red-400"
                >
                  <i class="fa-solid fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <PaginationLinks v-if="props.genres.links" :links="props.genres.links" :meta="props.genres.meta" class="justify-center" />
    </div>
  </AdminLayout>
</template>

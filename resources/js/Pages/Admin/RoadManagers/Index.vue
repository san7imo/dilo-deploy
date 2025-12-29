<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, router } from "@inertiajs/vue3";

const props = defineProps({
  roadManagers: Object,
});

const handleDelete = (id) => {
  if (confirm("Seguro que deseas eliminar este road manager?")) {
    router.delete(route("admin.roadmanagers.destroy", id));
  }
};
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-[#ffa236]">Road managers</h1>
        <Link
          :href="route('admin.roadmanagers.create')"
          class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors"
        >
          + Nuevo road manager
        </Link>
      </div>

      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-4">
        <table class="w-full text-sm text-gray-300">
          <thead class="text-[#ffa236] text-left border-b border-[#2a2a2a]">
            <tr>
              <th class="py-3 px-4">Nombre</th>
              <th class="py-3 px-4">Correo</th>
              <th class="py-3 px-4">Verificado</th>
              <th class="py-3 px-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="manager in props.roadManagers.data"
              :key="manager.id"
              class="border-b border-[#2a2a2a] hover:bg-[#2a2a2a]/30"
            >
              <td class="py-3 px-4">{{ manager.name }}</td>
              <td class="py-3 px-4">{{ manager.email }}</td>
              <td class="py-3 px-4">
                <span
                  :class="manager.email_verified_at ? 'text-green-400' : 'text-yellow-400'"
                >
                  {{ manager.email_verified_at ? 'Si' : 'No' }}
                </span>
              </td>
              <td class="py-3 px-4 text-right space-x-2">
                <Link
                  :href="route('admin.roadmanagers.edit', manager.id)"
                  class="text-[#ffa236] hover:text-[#ffb54d]"
                >
                  <i class="fa-solid fa-pen-to-square"></i>
                </Link>
                <button
                  @click="handleDelete(manager.id)"
                  class="text-red-500 hover:text-red-400"
                >
                  <i class="fa-solid fa-trash"></i>
                </button>
              </td>
            </tr>
            <tr v-if="props.roadManagers.data.length === 0">
              <td colspan="4" class="py-6 text-center text-gray-400">
                No hay road managers registrados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="props.roadManagers.links" class="flex justify-center gap-2">
        <Link
          v-for="link in props.roadManagers.links"
          :key="link.label"
          :href="link.url || '#'"
          :class="[
            'px-3 py-2 rounded text-sm font-medium transition-colors',
            link.active
              ? 'bg-[#ffa236] text-black'
              : link.url
              ? 'bg-[#2a2a2a] text-gray-300 hover:bg-[#3a3a3a]'
              : 'bg-[#1a1a1a] text-gray-500 cursor-not-allowed',
          ]"
          v-html="link.label"
        ></Link>
      </div>
    </div>
  </AdminLayout>
</template>

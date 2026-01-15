<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, router } from "@inertiajs/vue3";

const props = defineProps({
  roadManagers: { type: Array, default: () => [] },
  contentManagers: { type: Array, default: () => [] },
  canManageRoadManagers: { type: Boolean, default: false },
  canManageContentManagers: { type: Boolean, default: false },
});

const handleDeleteRoadManager = (id) => {
  if (!props.canManageRoadManagers) return;
  if (confirm("Seguro que deseas eliminar este road manager?")) {
    router.delete(route("admin.roadmanagers.destroy", id));
  }
};

const handleDeleteContentManager = (id) => {
  if (!props.canManageContentManagers) return;
  if (confirm("Seguro que deseas eliminar este gestor de contenido?")) {
    router.delete(route("admin.content-managers.destroy", id));
  }
};
</script>

<template>
  <AdminLayout title="Equipo de trabajo">
    <div class="space-y-10">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-white">Equipo de trabajo</h1>
          <p class="text-sm text-gray-400">Centraliza road managers y gestores de contenido.</p>
        </div>
      </div>

      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-[#ffa236]">Road managers</h2>
          <Link
            v-if="canManageRoadManagers"
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
                v-for="manager in roadManagers"
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
                    v-if="canManageRoadManagers"
                    :href="route('admin.roadmanagers.edit', manager.id)"
                    class="text-[#ffa236] hover:text-[#ffb54d]"
                    title="Editar"
                  >
                    <i class="fa-solid fa-pen-to-square"></i>
                  </Link>
                  <button
                    v-if="canManageRoadManagers"
                    @click="handleDeleteRoadManager(manager.id)"
                    class="text-red-500 hover:text-red-400"
                    title="Eliminar"
                  >
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="roadManagers.length === 0">
                <td colspan="4" class="py-6 text-center text-gray-400">
                  No hay road managers registrados.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-[#ffa236]">Gestores de contenido</h2>
          <Link
            v-if="canManageContentManagers"
            :href="route('admin.content-managers.create')"
            class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors"
          >
            + Nuevo gestor de contenido
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
                v-for="manager in contentManagers"
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
                    v-if="canManageContentManagers"
                    :href="route('admin.content-managers.edit', manager.id)"
                    class="text-[#ffa236] hover:text-[#ffb54d]"
                    title="Editar"
                  >
                    <i class="fa-solid fa-pen-to-square"></i>
                  </Link>
                  <button
                    v-if="canManageContentManagers"
                    @click="handleDeleteContentManager(manager.id)"
                    class="text-red-500 hover:text-red-400"
                    title="Eliminar"
                  >
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="contentManagers.length === 0">
                <td colspan="4" class="py-6 text-center text-gray-400">
                  No hay gestores de contenido registrados.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </AdminLayout>
</template>

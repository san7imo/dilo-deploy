<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DangerConfirmModal from "@/Components/DangerConfirmModal.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import RowActionMenu from "@/Components/RowActionMenu.vue";
import { Link, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
  organizers: { type: Object, required: true },
});

const deleteModalOpen = ref(false);
const deleteProcessing = ref(false);
const pendingOrganizerId = ref(null);

const openDeleteModal = (organizerId) => {
  pendingOrganizerId.value = organizerId;
  deleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  if (deleteProcessing.value) return;
  deleteModalOpen.value = false;
  pendingOrganizerId.value = null;
};

const handleDelete = () => {
  if (!pendingOrganizerId.value || deleteProcessing.value) return;

  deleteProcessing.value = true;
  router.delete(route("admin.organizers.destroy", pendingOrganizerId.value), {
    preserveScroll: true,
    onFinish: () => {
      deleteProcessing.value = false;
      closeDeleteModal();
    },
  });
};
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-[#ffa236]">Empresarios / Organizadores</h1>
        <div class="flex items-center gap-2">
          <Link
            :href="route('admin.organizers.trash')"
            class="bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors"
          >
            Papelera
          </Link>
          <Link
            :href="route('admin.organizers.create')"
            class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors"
          >
            + Nuevo empresario
          </Link>
        </div>
      </div>

      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-4">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-gray-300">
            <thead class="text-[#ffa236] text-left border-b border-[#2a2a2a]">
              <tr>
                <th class="py-3 px-4">Empresa</th>
                <th class="py-3 px-4">Contacto</th>
                <th class="py-3 px-4">WhatsApp</th>
                <th class="py-3 px-4">Correo</th>
                <th class="py-3 px-4">Eventos</th>
                <th class="py-3 px-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="organizer in organizers.data"
                :key="organizer.id"
                class="border-b border-[#2a2a2a] hover:bg-[#2a2a2a]/30"
              >
                <td class="py-3 px-4">{{ organizer.company_name }}</td>
                <td class="py-3 px-4">{{ organizer.contact_name || "-" }}</td>
                <td class="py-3 px-4">{{ organizer.whatsapp || "-" }}</td>
                <td class="py-3 px-4">{{ organizer.email || "-" }}</td>
                <td class="py-3 px-4">{{ organizer.events_count ?? 0 }}</td>
                <td class="py-3 px-4 text-right">
                  <RowActionMenu label="Acciones de empresario">
                    <Link
                      :href="route('admin.organizers.edit', organizer.id)"
                      class="block rounded px-3 py-2 text-sm text-gray-200 hover:bg-white/10"
                    >
                      Editar
                    </Link>
                    <button
                      type="button"
                      class="block w-full rounded px-3 py-2 text-left text-sm text-red-300 hover:bg-red-500/20"
                      @click="openDeleteModal(organizer.id)"
                    >
                      Mover a papelera
                    </button>
                  </RowActionMenu>
                </td>
              </tr>
              <tr v-if="organizers.data.length === 0">
                <td colspan="6" class="py-6 text-center text-gray-400">No hay empresarios registrados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <PaginationLinks
        v-if="organizers.links"
        :links="organizers.links"
        :meta="organizers.meta"
        class="justify-center"
      />
    </div>

    <DangerConfirmModal
      :show="deleteModalOpen"
      title="Mover empresario a papelera"
      message="El empresario se moverá a la papelera y podrás restaurarlo después."
      confirm-label="Mover a papelera"
      :processing="deleteProcessing"
      @close="closeDeleteModal"
      @confirm="handleDelete"
    />
  </AdminLayout>
</template>

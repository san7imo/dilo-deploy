<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DangerConfirmModal from "@/Components/DangerConfirmModal.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import RowActionMenu from "@/Components/RowActionMenu.vue";
import { Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
  roadManagers: { type: Object, default: () => ({ data: [] }) },
  contentManagers: { type: Object, default: () => ({ data: [] }) },
  collaborators: { type: Object, default: () => ({ data: [] }) },
  canManageRoadManagers: { type: Boolean, default: false },
  canManageContentManagers: { type: Boolean, default: false },
  canManageCollaborators: { type: Boolean, default: false },
});

const deleteModalOpen = ref(false);
const deleteProcessing = ref(false);
const pendingDelete = ref(null);

const deleteTargetLabel = computed(() => pendingDelete.value?.label ?? "este registro");

const openDeleteModal = (config) => {
  pendingDelete.value = config;
  deleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  if (deleteProcessing.value) return;
  deleteModalOpen.value = false;
  pendingDelete.value = null;
};

const confirmDelete = () => {
  if (!pendingDelete.value || deleteProcessing.value) return;

  deleteProcessing.value = true;
  router.delete(route(pendingDelete.value.route, pendingDelete.value.id), {
    preserveScroll: true,
    onFinish: () => {
      deleteProcessing.value = false;
      closeDeleteModal();
    },
  });
};

const handleDeleteRoadManager = (id) => {
  if (!props.canManageRoadManagers) return;
  openDeleteModal({ route: "admin.roadmanagers.destroy", id, label: "este road manager" });
};

const handleDeleteContentManager = (id) => {
  if (!props.canManageContentManagers) return;
  openDeleteModal({ route: "admin.content-managers.destroy", id, label: "este gestor de contenido" });
};

const handleDeleteCollaborator = (id) => {
  if (!props.canManageCollaborators) return;
  openDeleteModal({ route: "admin.collaborators.destroy", id, label: "este colaborador" });
};

const formatUsd = (value) => {
  const amount = Number(value ?? 0);
  if (!Number.isFinite(amount)) return "$0.00";

  return new Intl.NumberFormat("es-CO", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount);
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
          <div class="flex items-center gap-2">
            <Link
              v-if="canManageRoadManagers"
              :href="route('admin.roadmanagers.trash')"
              class="bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors"
            >
              Papelera
            </Link>
            <Link
              v-if="canManageRoadManagers"
              :href="route('admin.roadmanagers.create')"
              class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors"
            >
              + Nuevo road manager
            </Link>
          </div>
        </div>

        <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-4">
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-300">
            <thead class="text-[#ffa236] text-left border-b border-[#2a2a2a]">
              <tr>
                <th class="py-3 px-4">Nombre</th>
                <th class="py-3 px-4">Correo</th>
                <th class="py-3 px-4">Verificado</th>
                <th class="py-3 px-4">Recibido mes</th>
                <th class="py-3 px-4">Recibido 3 meses</th>
                <th class="py-3 px-4">Recibido 6 meses</th>
                <th class="py-3 px-4">Recibido año</th>
                <th class="py-3 px-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="manager in roadManagers.data"
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
                <td class="py-3 px-4 whitespace-nowrap">{{ formatUsd(manager.received_month_usd) }}</td>
                <td class="py-3 px-4 whitespace-nowrap">{{ formatUsd(manager.received_three_months_usd) }}</td>
                <td class="py-3 px-4 whitespace-nowrap">{{ formatUsd(manager.received_six_months_usd) }}</td>
                <td class="py-3 px-4 whitespace-nowrap">{{ formatUsd(manager.received_year_usd) }}</td>
                <td class="py-3 px-4 text-right">
                  <RowActionMenu v-if="canManageRoadManagers" label="Acciones de road manager">
                    <Link
                      :href="route('admin.roadmanagers.edit', manager.id)"
                      class="block rounded px-3 py-2 text-sm text-gray-200 hover:bg-white/10"
                    >
                      Editar
                    </Link>
                    <button
                      type="button"
                      class="block w-full rounded px-3 py-2 text-left text-sm text-red-300 hover:bg-red-500/20"
                      @click="handleDeleteRoadManager(manager.id)"
                    >
                      Mover a papelera
                    </button>
                  </RowActionMenu>
                </td>
              </tr>
              <tr v-if="roadManagers.data.length === 0">
                <td colspan="8" class="py-6 text-center text-gray-400">
                  No hay road managers registrados.
                </td>
              </tr>
            </tbody>
            </table>
          </div>
        </div>

        <PaginationLinks v-if="roadManagers.links" :links="roadManagers.links" :meta="roadManagers.meta" class="justify-center" />
      </section>

      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-[#ffa236]">Gestores de contenido</h2>
          <div class="flex items-center gap-2">
            <Link
              v-if="canManageContentManagers"
              :href="route('admin.content-managers.trash')"
              class="bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors"
            >
              Papelera
            </Link>
            <Link
              v-if="canManageContentManagers"
              :href="route('admin.content-managers.create')"
              class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors"
            >
              + Nuevo gestor de contenido
            </Link>
          </div>
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
                v-for="manager in contentManagers.data"
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
                <td class="py-3 px-4 text-right">
                  <RowActionMenu v-if="canManageContentManagers" label="Acciones de gestor">
                    <Link
                      :href="route('admin.content-managers.edit', manager.id)"
                      class="block rounded px-3 py-2 text-sm text-gray-200 hover:bg-white/10"
                    >
                      Editar
                    </Link>
                    <button
                      type="button"
                      class="block w-full rounded px-3 py-2 text-left text-sm text-red-300 hover:bg-red-500/20"
                      @click="handleDeleteContentManager(manager.id)"
                    >
                      Mover a papelera
                    </button>
                  </RowActionMenu>
                </td>
              </tr>
              <tr v-if="contentManagers.data.length === 0">
                <td colspan="4" class="py-6 text-center text-gray-400">
                  No hay gestores de contenido registrados.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <PaginationLinks v-if="contentManagers.links" :links="contentManagers.links" :meta="contentManagers.meta" class="justify-center" />
      </section>

      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-[#ffa236]">Colaboradores</h2>
          <div class="flex items-center gap-2">
            <Link
              v-if="canManageCollaborators"
              :href="route('admin.collaborators.trash')"
              class="bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors"
            >
              Papelera
            </Link>
            <Link
              v-if="canManageCollaborators"
              :href="route('admin.collaborators.create')"
              class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors"
            >
              + Nuevo colaborador
            </Link>
          </div>
        </div>

        <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-4">
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-300">
            <thead class="text-[#ffa236] text-left border-b border-[#2a2a2a]">
              <tr>
                <th class="py-3 px-4">Titular</th>
                <th class="py-3 px-4">ID</th>
                <th class="py-3 px-4">Tipo de cuenta</th>
                <th class="py-3 px-4">Banco</th>
                <th class="py-3 px-4">Dirección</th>
                <th class="py-3 px-4">Cuenta</th>
                <th class="py-3 px-4">País</th>
                <th class="py-3 px-4">Recibido mes</th>
                <th class="py-3 px-4">Recibido 3 meses</th>
                <th class="py-3 px-4">Recibido 6 meses</th>
                <th class="py-3 px-4">Recibido año</th>
                <th class="py-3 px-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="collaborator in collaborators.data"
                :key="collaborator.id"
                class="border-b border-[#2a2a2a] hover:bg-[#2a2a2a]/30"
              >
                <td class="py-3 px-4">{{ collaborator.account_holder }}</td>
                <td class="py-3 px-4">{{ collaborator.holder_id }}</td>
                <td class="py-3 px-4">{{ collaborator.account_type }}</td>
                <td class="py-3 px-4">{{ collaborator.bank }}</td>
                <td class="py-3 px-4">{{ collaborator.address }}</td>
                <td class="py-3 px-4">{{ collaborator.account_number }}</td>
                <td class="py-3 px-4">{{ collaborator.country }}</td>
                <td class="py-3 px-4 whitespace-nowrap">{{ formatUsd(collaborator.received_month_usd) }}</td>
                <td class="py-3 px-4 whitespace-nowrap">{{ formatUsd(collaborator.received_three_months_usd) }}</td>
                <td class="py-3 px-4 whitespace-nowrap">{{ formatUsd(collaborator.received_six_months_usd) }}</td>
                <td class="py-3 px-4 whitespace-nowrap">{{ formatUsd(collaborator.received_year_usd) }}</td>
                <td class="py-3 px-4 text-right">
                  <RowActionMenu v-if="canManageCollaborators" label="Acciones de colaborador">
                    <Link
                      :href="route('admin.collaborators.edit', collaborator.id)"
                      class="block rounded px-3 py-2 text-sm text-gray-200 hover:bg-white/10"
                    >
                      Editar
                    </Link>
                    <button
                      type="button"
                      class="block w-full rounded px-3 py-2 text-left text-sm text-red-300 hover:bg-red-500/20"
                      @click="handleDeleteCollaborator(collaborator.id)"
                    >
                      Mover a papelera
                    </button>
                  </RowActionMenu>
                </td>
              </tr>
              <tr v-if="collaborators.data.length === 0">
                <td colspan="12" class="py-6 text-center text-gray-400">
                  No hay colaboradores registrados.
                </td>
              </tr>
            </tbody>
            </table>
          </div>
        </div>

        <PaginationLinks
          v-if="collaborators.links"
          :links="collaborators.links"
          :meta="collaborators.meta"
          class="justify-center"
        />
      </section>
    </div>

    <DangerConfirmModal
      :show="deleteModalOpen"
      title="Mover registro a papelera"
      :message="`Esta acción moverá ${deleteTargetLabel} a la papelera. Podrás restaurarlo después.`"
      confirm-label="Mover a papelera"
      :processing="deleteProcessing"
      @close="closeDeleteModal"
      @confirm="confirmDelete"
    />
  </AdminLayout>
</template>

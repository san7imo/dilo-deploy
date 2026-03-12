<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DangerConfirmModal from "@/Components/DangerConfirmModal.vue";
import Modal from "@/Components/Modal.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import RowActionMenu from "@/Components/RowActionMenu.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
  artists: Object,
  externalArtists: {
    type: Array,
    default: () => [],
  },
});

const identificationTypeLabels = {
  passport: "Pasaporte",
  national_id: "ID nacional",
  residence_permit: "Permiso residencia",
  tax_id: "Tax ID",
  driver_license: "Licencia conducción",
  other: "Otro",
  cc: "ID nacional",
  ce: "Permiso residencia",
  ti: "ID nacional",
  nit: "Tax ID",
};

const formatIdentificationType = (value) => identificationTypeLabels[value] || "-";

const deleteModalOpen = ref(false);
const deleteProcessing = ref(false);
const pendingArtistId = ref(null);
const inviteModalOpen = ref(false);

const inviteForm = useForm({
  name: "",
  email: "",
});

const openDeleteModal = (id) => {
  pendingArtistId.value = id;
  deleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  if (deleteProcessing.value) return;
  deleteModalOpen.value = false;
  pendingArtistId.value = null;
};

const handleDelete = () => {
  if (!pendingArtistId.value || deleteProcessing.value) return;

  deleteProcessing.value = true;
  router.delete(route("admin.artists.destroy", pendingArtistId.value), {
    onFinish: () => {
      deleteProcessing.value = false;
      closeDeleteModal();
    },
  });
};

const openInviteModal = () => {
  inviteModalOpen.value = true;
};

const closeInviteModal = () => {
  if (inviteForm.processing) return;
  inviteModalOpen.value = false;
  inviteForm.reset();
  inviteForm.clearErrors();
};

const handleInviteExternalArtist = () => {
  inviteForm.post(route("admin.artists.external-invitations.store"), {
    preserveScroll: true,
    onSuccess: () => {
      closeInviteModal();
    },
  });
};
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-[#ffa236]">Artistas</h1>
        <div class="flex items-center gap-2">
          <Link
            :href="route('admin.artists.trash')"
            class="bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors"
          >
            <i class="fa-solid fa-box-archive mr-2"></i>Papelera
          </Link>
          <Link
            :href="route('admin.artists.create')"
            class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors"
          >
            <i class="fa-solid fa-plus mr-2"></i>Nuevo artista
          </Link>
        </div>
      </div>

      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-white">Artistas Dilo Records</h2>
          <span class="text-xs px-2 py-1 rounded bg-[#2a2a2a] text-gray-200">
            {{ props.artists.total || props.artists.data.length }} internos
          </span>
        </div>

        <div v-if="props.artists.data.length === 0" class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-8 text-center">
          <p class="text-gray-400">
            No hay artistas internos registrados.
            <Link :href="route('admin.artists.create')" class="text-[#ffa236] hover:text-[#ffb54d]">Crear uno</Link>
          </p>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div
            v-for="artist in props.artists.data"
            :key="artist.id"
            class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4 hover:border-[#ffa236]/50 transition-all"
          >
            <div class="flex items-start gap-3">
              <div class="w-16 h-16 rounded-md overflow-hidden bg-[#0f0f0f] border border-[#2a2a2a] shrink-0">
                <img
                  v-if="artist.banner_artist_url || artist.carousel_home_url"
                  :src="artist.banner_artist_url || artist.carousel_home_url"
                  :alt="artist.name"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <i class="fa-solid fa-music text-xl text-[#3a3a3a]"></i>
                </div>
              </div>

              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                  <div>
                    <h3 class="text-lg font-bold text-white truncate">{{ artist.name }}</h3>
                    <p class="text-xs text-gray-500">Nombre legal: {{ artist.user?.name || "-" }}</p>
                  </div>
                  <span class="text-xs text-gray-500">#{{ artist.id }}</span>
                </div>

                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                  <p class="text-gray-300"><span class="text-gray-500">Correo:</span> {{ artist.user?.email || "-" }}</p>
                  <p class="text-gray-300"><span class="text-gray-500">Celular:</span> {{ artist.user?.phone || artist.phone || "-" }}</p>
                  <p class="text-gray-300"><span class="text-gray-500">País:</span> {{ artist.country || "-" }}</p>
                  <p class="text-gray-300"><span class="text-gray-500">Género:</span> {{ artist.genre?.name || "-" }}</p>
                  <p class="text-gray-300">
                    <span class="text-gray-500">Documento:</span>
                    {{ formatIdentificationType(artist.user?.identification_type) }} {{ artist.user?.identification_number || "-" }}
                  </p>
                  <p class="text-[#ffa236]"><span class="text-gray-500">Lanzamientos:</span> {{ artist.releases_count || 0 }}</p>
                </div>

                <p v-if="artist.bio" class="text-gray-400 text-sm line-clamp-2 mt-2">{{ artist.bio }}</p>

                <p v-if="artist.user?.additional_information" class="text-xs text-gray-500 mt-2 line-clamp-2">
                  {{ artist.user.additional_information }}
                </p>

                <div class="flex items-center gap-2 pt-3 mt-3 border-t border-[#2a2a2a]">
                  <Link
                    :href="route('admin.artists.edit', artist.id)"
                    class="flex-1 bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-3 py-2 rounded text-center text-sm transition-colors"
                  >
                    <i class="fa-solid fa-pen-to-square mr-1"></i>Editar
                  </Link>
                  <RowActionMenu label="Acciones de artista">
                    <button
                      type="button"
                      class="block w-full rounded px-3 py-2 text-left text-sm text-red-300 hover:bg-red-500/20"
                      @click="openDeleteModal(artist.id)"
                    >
                      Mover a papelera
                    </button>
                  </RowActionMenu>
                </div>
              </div>
            </div>
          </div>
        </div>

        <PaginationLinks v-if="props.artists.links" :links="props.artists.links" :meta="props.artists.meta" class="justify-center" />
      </section>

      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-white">Artistas externos</h2>
          <div class="flex items-center gap-2">
            <span class="text-xs px-2 py-1 rounded bg-[#2a2a2a] text-gray-200">
              {{ props.externalArtists.length }} externos
            </span>
            <button
              type="button"
              class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-3 py-2 rounded-md text-sm transition-colors"
              @click="openInviteModal"
            >
              <i class="fa-solid fa-envelope mr-1"></i>Invitar artista externo
            </button>
          </div>
        </div>

        <div v-if="props.externalArtists.length === 0" class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-6 text-center">
          <p class="text-gray-400">No hay artistas externos registrados.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <div
            v-for="artist in props.externalArtists"
            :key="artist.id"
            class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4"
          >
            <div class="flex items-center gap-3 mb-3">
              <div class="w-12 h-12 rounded-md bg-[#0f0f0f] border border-[#2a2a2a] flex items-center justify-center">
                <i class="fa-solid fa-user text-[#3a3a3a]"></i>
              </div>
              <div class="min-w-0">
                <h3 class="text-white font-semibold truncate">{{ artist.stage_name || artist.name }}</h3>
                <p class="text-xs text-gray-500 truncate">Legal: {{ artist.name || "-" }}</p>
              </div>
            </div>

            <div class="space-y-1.5 text-sm">
              <p class="text-gray-300"><span class="text-gray-500">Correo:</span> {{ artist.email || "-" }}</p>
              <p class="text-gray-300"><span class="text-gray-500">Celular:</span> {{ artist.phone || "-" }}</p>
              <p class="text-gray-300">
                <span class="text-gray-500">Documento:</span>
                {{ formatIdentificationType(artist.identification_type) }} {{ artist.identification_number || "-" }}
              </p>
              <p v-if="artist.additional_information" class="text-xs text-gray-500 pt-1 line-clamp-2">
                {{ artist.additional_information }}
              </p>
            </div>
          </div>
        </div>
      </section>
    </div>

    <DangerConfirmModal
      :show="deleteModalOpen"
      title="Mover artista a papelera"
      message="El artista se moverá a la papelera y podrás restaurarlo luego."
      confirm-label="Mover a papelera"
      :processing="deleteProcessing"
      @close="closeDeleteModal"
      @confirm="handleDelete"
    />

    <Modal :show="inviteModalOpen" max-width="md" @close="closeInviteModal">
      <form @submit.prevent="handleInviteExternalArtist" class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-white">Invitar artista externo</h3>
          <button
            type="button"
            class="text-gray-400 hover:text-white text-sm"
            @click="closeInviteModal"
          >
            Cerrar
          </button>
        </div>

        <p class="text-sm text-gray-400">
          Envía una invitación para que el artista cree su cuenta y acceda a la plataforma.
        </p>

        <div>
          <label class="text-gray-300 text-sm">Nombre del artista</label>
          <input
            v-model="inviteForm.name"
            type="text"
            class="w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236]"
            placeholder="Nombre artístico o nombre visible"
          />
          <p v-if="inviteForm.errors.name" class="text-red-500 text-sm mt-1">
            {{ inviteForm.errors.name }}
          </p>
        </div>

        <div>
          <label class="text-gray-300 text-sm">Correo electrónico</label>
          <input
            v-model="inviteForm.email"
            type="email"
            class="w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236]"
            placeholder="correo@dominio.com"
          />
          <p v-if="inviteForm.errors.email" class="text-red-500 text-sm mt-1">
            {{ inviteForm.errors.email }}
          </p>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button
            type="button"
            class="px-4 py-2 rounded-md border border-[#2a2a2a] text-gray-300 hover:text-white"
            :disabled="inviteForm.processing"
            @click="closeInviteModal"
          >
            Cancelar
          </button>
          <button
            type="submit"
            class="px-4 py-2 rounded-md bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold disabled:opacity-60"
            :disabled="inviteForm.processing"
          >
            {{ inviteForm.processing ? "Enviando..." : "Enviar invitación" }}
          </button>
        </div>
      </form>
    </Modal>
  </AdminLayout>
</template>

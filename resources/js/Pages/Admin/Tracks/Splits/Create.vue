<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
  track: Object,
  artists: Array,
});

const form = useForm({
  split_type: "master",
  contract: null,
  participants: [
    {
      role: "label",
      percentage: 0,
      artist_id: "",
      name: "Dilo Records",
      payee_email: "",
    },
  ],
});

const totalPercentage = computed(() =>
  form.participants.reduce((sum, p) => sum + Number(p.percentage || 0), 0)
);

const addParticipant = () => {
  form.participants.push({
    role: "artist",
    percentage: 0,
    artist_id: "",
    name: "",
    payee_email: "",
  });
};

const removeParticipant = (index) => {
  form.participants.splice(index, 1);
};

const handleSubmit = () => {
  form.post(route("admin.tracks.splits.store", props.track.id), {
    forceFormData: true,
  });
};
</script>

<template>
  <AdminLayout title="Create Split">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-white">Create Split · {{ track.title }}</h1>
        <p class="text-gray-400 text-sm">
          {{ track.release?.title || "-" }}
        </p>
      </div>
      <Link :href="route('admin.tracks.splits.index', track.id)" class="text-gray-400 hover:text-white">
        Volver
      </Link>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <div class="max-w-xl">
        <label class="text-gray-300 text-sm">Contrato</label>
        <input
          type="file"
          class="input-file"
          @change="(e) => { form.contract = e.target.files[0]; }"
        />
        <p v-if="form.errors.contract" class="text-red-500 text-sm mt-1">
          {{ form.errors.contract }}
        </p>
      </div>

      <div class="bg-[#0f0f0f] border border-[#2a2a2a] rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-[#ffa236] font-semibold">Participantes</h3>
          <button type="button" class="btn-secondary" @click="addParticipant">
            + Agregar
          </button>
        </div>

        <div class="text-sm text-gray-400 mb-4">
          Total: {{ totalPercentage.toFixed(2) }}%
        </div>

        <div
          v-for="(participant, index) in form.participants"
          :key="index"
          class="grid grid-cols-1 lg:grid-cols-6 gap-4 mb-4 border-b border-[#1f1f1f] pb-4"
        >
          <div>
            <label class="text-gray-300 text-xs">Rol</label>
            <select v-model="participant.role" class="input">
              <option value="label">Label</option>
              <option value="artist">Artist</option>
              <option value="producer">Producer</option>
              <option value="composer">Composer</option>
              <option value="publisher">Publisher</option>
              <option value="manager">Manager</option>
            </select>
          </div>

          <div>
            <label class="text-gray-300 text-xs">%</label>
            <input v-model="participant.percentage" type="number" step="0.01" min="0" max="100" class="input" />
          </div>

          <div>
            <label class="text-gray-300 text-xs">Artista</label>
            <select v-model="participant.artist_id" class="input">
              <option value="">--</option>
              <option v-for="a in artists" :key="a.id" :value="a.id">{{ a.name }}</option>
            </select>
          </div>

          <div>
            <label class="text-gray-300 text-xs">Nombre</label>
            <input v-model="participant.name" type="text" class="input" placeholder="Nombre libre" />
          </div>

          <div>
            <label class="text-gray-300 text-xs">Email</label>
            <input v-model="participant.payee_email" type="email" class="input" placeholder="email@dominio.com" />
          </div>

          <div class="flex items-end">
            <button
              type="button"
              class="text-red-400 hover:text-red-300 text-sm"
              @click="removeParticipant(index)"
              :disabled="form.participants.length === 1"
            >
              Eliminar
            </button>
          </div>

          <p v-if="form.errors[`participants.${index}`]" class="text-red-500 text-xs col-span-full">
            {{ form.errors[`participants.${index}`] }}
          </p>
        </div>

        <p v-if="form.errors.participants" class="text-red-500 text-sm">
          {{ form.errors.participants }}
        </p>
      </div>

      <div class="flex justify-end">
        <button type="submit" class="btn-primary">Guardar Split</button>
      </div>
    </form>
  </AdminLayout>
</template>

<style scoped>
.input {
  @apply w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}
.input-file {
  @apply block w-full text-sm text-gray-400 border border-[#2a2a2a] rounded-md cursor-pointer bg-[#0f0f0f] file:bg-[#ffa236] file:text-black file:px-3 file:py-1;
}
.btn-primary {
  @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}
.btn-secondary {
  @apply border border-[#ffa236] text-[#ffa236] px-3 py-1 rounded-md text-sm hover:bg-[#ffa236]/10;
}
</style>

<script setup>
import ArtistLayout from "@/Layouts/ArtistLayout.vue";
import { useForm } from "@inertiajs/vue3";

defineOptions({ layout: ArtistLayout });

const props = defineProps({
  artist: { type: Object, required: true },
});

const form = useForm({
  name: props.artist.name ?? "",
  bio: props.artist.bio ?? "",
  country: props.artist.country ?? "",
  genre_id: props.artist.genre_id ?? "",
});

const submit = () => {
  form.transform((data) => ({ ...data, _method: "put" }))
    .post(route("artist.profile.update"));
};
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-[#ffa236]">Mi perfil</h1>

    <form @submit.prevent="submit" class="space-y-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-gray-300 text-sm">Nombre artístico</label>
          <input v-model="form.name" type="text" class="input" />
          <div v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</div>
        </div>
        <div>
          <label class="text-gray-300 text-sm">País</label>
          <input v-model="form.country" type="text" class="input" />
          <div v-if="form.errors.country" class="text-red-400 text-xs mt-1">{{ form.errors.country }}</div>
        </div>
      </div>

      <div>
        <label class="text-gray-300 text-sm">Biografía</label>
        <textarea v-model="form.bio" rows="4" class="input" />
        <div v-if="form.errors.bio" class="text-red-400 text-xs mt-1">{{ form.errors.bio }}</div>
      </div>

      <div>
        <label class="text-gray-300 text-sm">Género musical</label>
        <select v-model="form.genre_id" class="input">
          <option :value="props.artist.genre?.id || ''">
            {{ props.artist.genre?.name || "Sin género" }}
          </option>
          <!-- TODO: cargar catálogo si quieres permitir cambiar -->
        </select>
        <div v-if="form.errors.genre_id" class="text-red-400 text-xs mt-1">{{ form.errors.genre_id }}</div>
      </div>

      <div class="flex justify-end gap-2">
        <button type="submit" class="btn-primary" :disabled="form.processing">
          Guardar cambios
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.input { @apply w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236]; }
.btn-primary { @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors; }
</style>
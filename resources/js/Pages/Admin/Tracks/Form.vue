<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";

const props = defineProps({
  track: { type: Object, default: () => ({}) },
  releases: { type: Array, default: () => [] },
  artists: { type: Array, default: () => [] },
  mode: { type: String, default: "create" },
});

const form = useForm({
  release_id: props.track.release_id || "",
  title: props.track.title || "",
  isrc: props.track.isrc || "",
  track_number: props.track.track_number || "",
  duration: props.track.duration || "",
  preview_url: props.track.preview_url || "",
  spotify_url: props.track.spotify_url || "",
  youtube_url: props.track.youtube_url || "",
  apple_music_url: props.track.apple_music_url || "",
  deezer_url: props.track.deezer_url || "",
  amazon_music_url: props.track.amazon_music_url || "",
  soundcloud_url: props.track.soundcloud_url || "",
  tidal_url: props.track.tidal_url || "",
  artist_ids: props.track.artists
    ? props.track.artists.map((a) => a.id)
    : [],
  cover_file: null,
});

const handleSubmit = () => {
  console.log("🎵 Enviando formulario...");
  console.log("📦 Datos del form:", form.data());

  const url =
    props.mode === "edit"
      ? route("admin.tracks.update", props.track.id)
      : route("admin.tracks.store");

  const method = props.mode === "edit" ? "post" : "post";

  form
    .transform((data) => ({
      ...data,
      _method: props.mode === "edit" ? "put" : "post",
    }))
    .submit(method, url, {
      forceFormData: true,
      onStart: () => console.log("🚀 Request iniciada:", url),
      onProgress: (progress) => console.log("📤 Progreso:", progress),
      onError: (errors) => {
        console.group("❌ Errores de validación");
        Object.entries(errors).forEach(([key, msg]) =>
          console.log(`- ${key}:`, msg)
        );
        console.groupEnd();
      },
      onSuccess: () => console.log("✅ Pista guardada correctamente"),
      onFinish: () => console.log("🏁 Proceso finalizado"),
    });
};
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Info principal -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div>
        <label class="text-gray-300 text-sm">Título de la pista</label>
        <input v-model="form.title" type="text" class="input" placeholder="Ej: Mi canción" />
        <p v-if="form.errors.title" class="text-red-500 text-sm mt-1">
          {{ form.errors.title }}
        </p>
      </div>

      <div>
        <label class="text-gray-300 text-sm">ISRC (opcional)</label>
        <input v-model="form.isrc" type="text" class="input" placeholder="Ej: USRC17607839" />
        <p v-if="form.errors.isrc" class="text-red-500 text-sm mt-1">
          {{ form.errors.isrc }}
        </p>
      </div>

      <div>
        <label class="text-gray-300 text-sm">Número de pista</label>
        <input v-model="form.track_number" type="number" min="1" class="input" />
      </div>
    </div>

    <!-- Release -->
    <div>
      <label class="text-gray-300 text-sm">Lanzamiento asociado</label>
      <select v-model="form.release_id" class="input">
        <option disabled value="">Selecciona un release</option>
        <option v-for="r in releases" :key="r.id" :value="r.id">{{ r.title }}</option>
      </select>
    </div>

    <!-- Duración y previsualización -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="text-gray-300 text-sm">Duración (mm:ss)</label>
        <input v-model="form.duration" type="text" class="input" placeholder="Ej: 03:25" />
      </div>

      <div>
        <label class="text-gray-300 text-sm">Previsualización (URL)</label>
        <input v-model="form.preview_url" type="url" class="input" placeholder="https://..." />
      </div>
    </div>

    <!-- Subida de portada -->
    <div>
      <label class="text-gray-300 text-sm">Portada de la pista</label>
      <input
        type="file"
        class="input-file"
        @change="(e) => {
          form.cover_file = e.target.files[0];
          console.log('🖼 Archivo seleccionado:', e.target.files[0]);
        }"
      />
      <p v-if="form.errors.cover_file" class="text-red-500 text-sm mt-1">
        {{ form.errors.cover_file }}
      </p>
    </div>

    <!-- Plataformas -->
    <div>
      <h3 class="text-[#ffa236] font-semibold mb-2">Plataformas</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="(label, key) in {
          spotify_url: 'Spotify',
          youtube_url: 'YouTube',
          apple_music_url: 'Apple Music',
          deezer_url: 'Deezer',
          amazon_music_url: 'Amazon Music',
          soundcloud_url: 'SoundCloud',
          tidal_url: 'Tidal',
        }" :key="key">
          <label class="text-gray-300 text-sm">{{ label }}</label>
          <input v-model="form[key]" type="url" class="input" placeholder="https://..." />
        </div>
      </div>
    </div>

    <!-- Artistas -->
    <div>
      <label class="text-gray-300 text-sm">Artistas participantes</label>
      <select v-model="form.artist_ids" class="input" multiple>
        <option v-for="a in artists" :key="a.id" :value="a.id">{{ a.name }}</option>
      </select>
      <p class="text-gray-500 text-xs mt-2">Puedes seleccionar varios artistas (Ctrl/Cmd + click).</p>
    </div>

    <div class="flex justify-end">
      <button type="submit" class="btn-primary">
        {{ props.mode === "edit" ? "Actualizar pista" : "Guardar pista" }}
      </button>
    </div>
  </form>
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
</style>

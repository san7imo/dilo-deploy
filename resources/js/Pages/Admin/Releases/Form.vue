<script setup>
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
  release: { type: Object, default: () => ({}) },
  artists: { type: Array,  default: () => [] },
  mode:    { type: String, default: "create" }, // "create" | "edit"
});

const form = useForm({
  artist_id:       props.release.artist_id       ?? "",
  title:           props.release.title           ?? "",
  upc:             props.release.upc             ?? "",
  type:            props.release.type            ?? "single",
  release_date:    props.release.release_date    ?? "",
  description:     props.release.description     ?? "",

  // plataformas
  spotify_url:      props.release.spotify_url      ?? "",
  youtube_url:      props.release.youtube_url      ?? "",
  apple_music_url:  props.release.apple_music_url  ?? "",
  deezer_url:       props.release.deezer_url       ?? "",
  amazon_music_url: props.release.amazon_music_url ?? "",
  soundcloud_url:   props.release.soundcloud_url   ?? "",
  tidal_url:        props.release.tidal_url        ?? "",

  // archivo portada
  cover_file: null,
});

const handleSubmit = () => {
  console.log("🟡 Enviando formulario Release...");
  console.log("📦 Datos actuales:", form.data());

  const method = "post";
  const url = props.mode === "edit"
    ? route("admin.releases.update", props.release.id)
    : route("admin.releases.store");

  form
    .transform((data) => ({
      ...data,
      _method: props.mode === "edit" ? "put" : "post",
    }))
    .submit(method, url, {
      forceFormData: true,
      onStart:    () => console.log("🚀 POST →", url),
      onProgress: (p) => console.log("📤 Subiendo...", p),
      onError:    (e) => {
        console.group("❌ Errores de validación");
        Object.entries(e).forEach(([k, v]) => console.log(`- ${k}:`, v));
        console.groupEnd();
      },
      onSuccess:  () => console.log("✅ Release creado/actualizado OK"),
      onFinish:   () => console.log("🏁 Proceso finalizado"),
    });
};
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- fila 1 -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="text-gray-300 text-sm">Artista</label>
        <select v-model="form.artist_id" class="input">
          <option disabled value="">Selecciona un artista</option>
          <option v-for="a in artists" :key="a.id" :value="a.id">{{ a.name }}</option>
        </select>
        <p v-if="form.errors.artist_id" class="text-red-500 text-xs mt-1">{{ form.errors.artist_id }}</p>
      </div>

      <div>
        <label class="text-gray-300 text-sm">Título</label>
        <input v-model="form.title" type="text" class="input" placeholder="Ej: Santo Demonio" />
        <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
      </div>
    </div>

    <!-- fila 2 -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div>
        <label class="text-gray-300 text-sm">Tipo</label>
        <select v-model="form.type" class="input">
          <option value="single">Single</option>
          <option value="ep">EP</option>
          <option value="album">Álbum</option>
          <option value="mixtape">Mixtape</option>
          <option value="live">Live</option>
          <option value="compilation">Compilation</option>
        </select>
      </div>

      <div>
        <label class="text-gray-300 text-sm">Fecha de lanzamiento</label>
        <input v-model="form.release_date" type="date" class="input" />
      </div>

      <div>
        <label class="text-gray-300 text-sm">UPC (opcional)</label>
        <input v-model="form.upc" type="text" class="input" placeholder="Ej: 123456789012" />
        <p v-if="form.errors.upc" class="text-red-500 text-xs mt-1">{{ form.errors.upc }}</p>
      </div>
    </div>

    <!-- descripción -->
    <div>
      <label class="text-gray-300 text-sm">Descripción</label>
      <textarea v-model="form.description" rows="4" class="input" placeholder="Descripción del release..."></textarea>
    </div>

    <!-- portada -->
    <div>
      <h3 class="text-[#ffa236] font-semibold mb-2">Portada</h3>
      <input
        type="file"
        name="cover_file"
        class="block w-full text-sm text-gray-400
               border border-[#2a2a2a] rounded-md cursor-pointer
               bg-[#0f0f0f] file:bg-[#ffa236] file:text-black file:px-3 file:py-1"
        @change="(e) => {
          form.cover_file = e.target.files[0];
          console.log('🖼 Archivo portada seleccionado:', form.cover_file);
        }"
      />
      <p v-if="form.errors.cover_file" class="text-red-500 text-xs mt-1">{{ form.errors.cover_file }}</p>

      <div v-if="props.mode === 'edit' && props.release?.cover_url" class="mt-3">
        <img :src="props.release.cover_url + '?tr=w-200,h-200,q-80,fo-auto'" alt="Portada actual" class="w-32 h-32 object-cover rounded" />
        <p class="text-xs text-gray-400 mt-1">Portada actual</p>
      </div>
    </div>

    <!-- plataformas -->
    <div>
      <h3 class="text-[#ffa236] font-semibold mb-2">Plataformas</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="field in [
          ['spotify_url', 'Spotify'],
          ['youtube_url', 'YouTube'],
          ['apple_music_url', 'Apple Music'],
          ['deezer_url', 'Deezer'],
          ['amazon_music_url', 'Amazon Music'],
          ['soundcloud_url', 'SoundCloud'],
          ['tidal_url', 'Tidal'],
        ]" :key="field[0]">
          <label class="text-gray-300 text-sm">{{ field[1] }}</label>
          <input v-model="form[field[0]]" type="url" class="input" placeholder="https://..." />
          <p v-if="form.errors[field[0]]" class="text-red-500 text-xs mt-1">{{ form.errors[field[0]] }}</p>
        </div>
      </div>
    </div>

    <div class="flex justify-end">
      <button type="submit" class="btn-primary">
        {{ props.mode === "edit" ? "Actualizar" : "Guardar" }}
      </button>
    </div>
  </form>
</template>

<style scoped>
.input {
  @apply w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white
  focus:border-[#ffa236] focus:ring-[#ffa236];
}
.btn-primary {
  @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}
</style>

<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import ImageGrid from "@/Components/ImageGrid.vue";

const props = defineProps({
  artist: { type: Object, default: () => ({}) },
  genres: { type: Array, default: () => [] },
  mode: { type: String, default: "create" },
});

const isDeleting = ref(false);

// Normalizar social_links
const prepareSocialLinks = () => {
  const defaults = {
    spotify: "",
    youtube: "",
    instagram: "",
    tiktok: "",
    facebook: "",
    x: "",
    apple: "",
    amazon: "",
  };

  if (
    props.artist.social_links_formatted &&
    typeof props.artist.social_links_formatted === "object"
  ) {
    return { ...defaults, ...props.artist.social_links_formatted };
  }

  if (!props.artist.social_links) {
    return defaults;
  }

  if (
    typeof props.artist.social_links === "object" &&
    !Array.isArray(props.artist.social_links)
  ) {
    return { ...defaults, ...props.artist.social_links };
  }

  if (Array.isArray(props.artist.social_links)) {
    const result = { ...defaults };
    props.artist.social_links.forEach((item) => {
      if (item.platform && item.url) {
        const key = item.platform.toLowerCase();
        result[key] = item.url;
      }
    });
    return result;
  }

  return defaults;
};

const form = useForm({
  name: props.artist.name || "",
  bio: props.artist.bio || "",
  country: props.artist.country || "",
  genre_id: props.artist.genre_id || "",
  social_links: prepareSocialLinks(),

  // credenciales del artista
  email: "",
  password: "",

  // 🔹 Estos son los campos reales que se envían como archivos
  banner_home_file: null,
  banner_artist_file: null,
  carousel_home_file: null,
  carousel_discography_file: null,
});

// Imágenes actuales del artista (lo que viene de la BD / ImageKit)
const currentImages = ref({
  banner_home: {
    url: props.artist.banner_home_url || null,
    id: props.artist.banner_home_id || null,
  },
  banner_artist: {
    url: props.artist.banner_artist_url || null,
    id: props.artist.banner_artist_id || null,
  },
  carousel_home: {
    url: props.artist.carousel_home_url || null,
    id: props.artist.carousel_home_id || null,
  },
  carousel_discography: {
    url: props.artist.carousel_discography_url || null,
    id: props.artist.carousel_discography_id || null,
  },
});

// Previews de archivos seleccionados
const fileInputs = ref({
  banner_home_file: null,
  banner_artist_file: null,
  carousel_home_file: null,
  carousel_discography_file: null,
});

// ✅ CORRECCIÓN IMPORTANTE:
// ImageGrid emite fieldKey = 'banner_home' | 'banner_artist' | ...
// pero los campos reales del form se llaman 'banner_home_file', etc.
const handleFileSelected = ({ fieldKey, file }) => {
  const formField = `${fieldKey}_file`; // ej: 'banner_home_file'

  // Asignar el archivo al form
  form[formField] = file;

  // Crear preview para mostrar en el grid
  const reader = new FileReader();
  reader.onload = (e) => {
    fileInputs.value[formField] = {
      preview: e.target.result,
    };
  };
  reader.readAsDataURL(file);

  console.log(`✅ Archivo ${formField} seleccionado:`, file.name);
};

// Eliminar imagen existente en ImageKit / BD
const handleDeleteImage = async (fieldKey) => {
  if (isDeleting.value) return;
  if (!props.artist?.id) {
    alert("No se encontró el artista.");
    return;
  }

  isDeleting.value = true;

  try {
    const response = await fetch(
      route("admin.artists.deleteImage", props.artist.id),
      {
        method: "DELETE",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": document.querySelector(
            'meta[name="csrf-token"]'
          )?.content,
        },
        body: JSON.stringify({ field: fieldKey }), // ej: 'banner_home'
      }
    );

    console.log("🔎 Status delete:", response.status);

    if (!response.ok) {
      const text = await response.text();
      console.error("❌ Respuesta del servidor:", text);
      alert("No se pudo eliminar la imagen");
      return;
    }

    const data = await response.json();
    console.log("✅ Imagen eliminada correctamente", data);

    const urlKey = `${fieldKey}_url`;
    const idKey = `${fieldKey}_id`;

    currentImages.value[fieldKey] = {
      url: data.artist[urlKey],
      id: data.artist[idKey],
    };

    fileInputs.value[`${fieldKey}_file`] = null;
  } catch (error) {
    console.error("Error:", error);
    alert("Error al eliminar la imagen");
  } finally {
    isDeleting.value = false;
  }
};

const handleSubmit = () => {
  console.log("🟡 Enviando formulario...");
  console.log("📦 Datos actuales del form:", form.data());

  const method = "post";
  const url =
    props.mode === "edit"
      ? route("admin.artists.update", props.artist.id)
      : route("admin.artists.store");

  form
    .transform((data) => ({
      ...data,
      _method: props.mode === "edit" ? "put" : "post",
      social_links: data.social_links,
    }))
    .submit(method, url, {
      forceFormData: true,
      onStart: () => console.log("🚀 Enviando request a:", url),
      onProgress: (progress) => console.log("📤 Subiendo...", progress),
      onError: (errors) => {
        console.group("❌ Errores de validación");
        for (const [field, message] of Object.entries(errors)) {
          console.log(`- ${field}:`, message);
        }
        console.groupEnd();
      },
      onSuccess: () =>
        console.log("✅ Artista creado/actualizado correctamente"),
      onFinish: () => console.log("🏁 Proceso finalizado"),
    });
};
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Datos principales -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="text-gray-300 text-sm">Nombre del artista</label>
        <input
          v-model="form.name"
          type="text"
          class="input"
          placeholder="Nombre artístico"
        />
        <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">
          {{ form.errors.name }}
        </p>
      </div>

      <div v-if="props.mode === 'create'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-gray-300 text-sm">Correo del artista</label>
          <input v-model="form.email" type="email" class="input" placeholder="artista@email.com" />
          <p v-if="form.errors.email" class="text-red-500 text-sm mt-1">
            {{ form.errors.email }}
          </p>
        </div>

        <div>
          <label class="text-gray-300 text-sm">Contraseña</label>
          <input v-model="form.password" type="password" class="input" placeholder="Mínimo 8 caracteres" />
          <p v-if="form.errors.password" class="text-red-500 text-sm mt-1">
            {{ form.errors.password }}
          </p>
        </div>
      </div>
      

      <div>
        <label class="text-gray-300 text-sm">País</label>
        <input
          v-model="form.country"
          type="text"
          class="input"
          placeholder="Ej: Colombia"
        />
      </div>
    </div>

    <!-- Género -->
    <div>
      <label class="text-gray-300 text-sm">Género musical</label>
      <select v-model="form.genre_id" class="input">
        <option disabled value="">Selecciona un género</option>
        <option v-for="g in genres" :key="g.id" :value="g.id">
          {{ g.name }}
        </option>
      </select>
    </div>

    <!-- Biografía -->
    <div>
      <label class="text-gray-300 text-sm">Biografía</label>
      <textarea
        v-model="form.bio"
        rows="4"
        class="input"
        placeholder="Breve descripción del artista..."
      ></textarea>
    </div>

    <!-- Imágenes -->
    <ImageGrid
      :images="currentImages"
      :file-inputs="fileInputs"
      @file-selected="handleFileSelected"
      @delete-image="handleDeleteImage"
    />

    <!-- Redes sociales -->
    <div>
      <h3 class="text-[#ffa236] font-semibold mb-2">
        Redes sociales / Streaming
      </h3>
      <div
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
      >
        <div
          v-for="(label, key) in {
            spotify: 'Spotify',
            youtube: 'YouTube',
            instagram: 'Instagram',
            tiktok: 'TikTok',
            facebook: 'Facebook',
            x: 'X / Twitter',
            apple: 'Apple Music',
            amazon: 'Amazon Music',
          }"
          :key="key"
        >
          <label class="text-gray-300 text-sm">{{ label }}</label>
          <input
            v-model="form.social_links[key]"
            type="url"
            class="input"
            placeholder="https://..."
          />
        </div>
      </div>
      <p class="text-gray-500 text-xs mt-2">
        Solo se guardarán las redes con una URL válida.
      </p>
    </div>

    <!-- Botón -->
    <div class="flex justify-end">
      <button type="submit" class="btn-primary">
        {{ props.mode === "edit" ? "Actualizar artista" : "Guardar artista" }}
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

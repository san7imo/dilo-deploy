<script setup>
const props = defineProps({
  images: {
    type: Object,
    default: () => ({
      banner_home: { url: null, id: null },
      banner_artist: { url: null, id: null },
      carousel_home: { url: null, id: null },
      carousel_discography: { url: null, id: null },
    }),
  },
  fileInputs: {
    type: Object,
    default: () => ({
      banner_home_file: null,
      banner_artist_file: null,
      carousel_home_file: null,
      carousel_discography_file: null,
    }),
  },
});

const emit = defineEmits(["delete-image", "file-selected"]);

const imageLabels = {
  banner_home: {
    label: "Banner Principal (Home)",
    icon: "fa-image",
  },
  banner_artist: {
    label: "Banner del Artista",
    icon: "fa-image",
  },
  carousel_home: {
    label: "Imagen Carrusel Home",
    icon: "fa-images",
  },
  carousel_discography: {
    label: "Imagen Carrusel Discografía",
    icon: "fa-images",
  },
};

const handleDeleteImage = (fieldKey) => {
  if (confirm("¿Eliminar esta imagen? Se eliminará de ImageKit.")) {
    emit("delete-image", fieldKey);
  }
};

const handleFileSelected = (fieldKey, event) => {
  const file = event.target.files[0];
  if (file) {
    emit("file-selected", { fieldKey, file });
    console.log(`📁 Archivo seleccionado para ${fieldKey}:`, file.name);
  }
};

const getImageUrl = (fieldKey) => {
  // 🔹 Busca primero preview local: banner_home_file, etc.
  return (
    props.fileInputs[`${fieldKey}_file`]?.preview ||
    props.images[fieldKey]?.url
  );
};
</script>

<template>
  <div class="space-y-4">
    <h3 class="text-[#ffa236] font-semibold flex items-center gap-2">
      <i class="fa-solid fa-images"></i>Imágenes
    </h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div
        v-for="fieldKey in Object.keys(imageLabels)"
        :key="fieldKey"
        class="group relative"
      >
        <div
          class="relative bg-[#0f0f0f] border-2 border-dashed border-[#2a2a2a] rounded-lg overflow-hidden hover:border-[#ffa236]/50 transition-all aspect-square"
        >
          <!-- Imagen o placeholder -->
          <img
            v-if="getImageUrl(fieldKey)"
            :src="getImageUrl(fieldKey)"
            :alt="imageLabels[fieldKey].label"
            class="w-full h-full object-cover"
          />
          <div
            v-else
            class="w-full h-full flex flex-col items-center justify-center text-gray-500 group-hover:text-[#ffa236] transition-colors"
          >
            <i
              :class="`fa-solid ${imageLabels[fieldKey].icon} text-3xl mb-2`"
            ></i>
            <span class="text-xs text-center">Sin imagen</span>
          </div>

          <!-- Overlay -->
          <div
            class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2"
          >
            <button
              v-if="props.images[fieldKey]?.url"
              @click="handleDeleteImage(fieldKey)"
              type="button"
              class="bg-red-500 hover:bg-red-600 text-white rounded-full w-10 h-10 flex items-center justify-center transition-colors"
              title="Eliminar imagen"
            >
              <i class="fa-solid fa-trash text-sm"></i>
            </button>

            <label
              class="bg-[#ffa236] hover:bg-[#ffb54d] text-black rounded-full w-10 h-10 flex items-center justify-center cursor-pointer transition-colors"
            >
              <i class="fa-solid fa-plus text-sm"></i>
              <input
                type="file"
                :name="`${fieldKey}_file`"
                accept="image/*"
                class="hidden"
                @change="(e) => handleFileSelected(fieldKey, e)"
              />
            </label>
          </div>
        </div>

        <div class="mt-2">
          <label class="text-gray-300 text-sm font-medium block">
            {{ imageLabels[fieldKey].label }}
          </label>
          <p class="text-gray-500 text-xs mt-1">
            {{ props.images[fieldKey]?.url ? "✅ Subida" : "⭕ Vacía" }}
          </p>
        </div>
      </div>
    </div>

    <div
      class="bg-[#0f0f0f] border border-[#2a2a2a] rounded-lg p-3 text-sm text-gray-400"
    >
      <p class="flex items-start gap-2">
        <i
          class="fa-solid fa-circle-info text-[#ffa236] mt-0.5 flex-shrink-0"
        ></i>
        <span
          >Todos los cambios en imágenes se sincronizan automáticamente al
          guardar el artista.</span
        >
      </p>
    </div>
  </div>
</template>

<style scoped>
img {
  transition: transform 0.3s ease;
}

.group:hover img {
  transform: scale(1.05);
}
</style>

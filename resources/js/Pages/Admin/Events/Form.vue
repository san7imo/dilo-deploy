<script setup>
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
  event: { type: Object, default: () => ({}) },
  artists: { type: Array, default: () => [] },
  mode: { type: String, default: "create" },
});

const form = useForm({
  title: props.event.title || "",
  description: props.event.description || "",
  location: props.event.location || "",
  event_date: props.event.event_date || "",
  artist_ids: props.event.artists ? props.event.artists.map(a => a.id) : [],
  poster_file: null,
});

const handleSubmit = () => {
  console.log("🎤 Enviando formulario de evento...", form.data());

  const method = "post";
  const url =
    props.mode === "edit"
      ? route("admin.events.update", props.event.id)
      : route("admin.events.store");

  form
    .transform((data) => ({
      ...data,
      _method: props.mode === "edit" ? "put" : "post",
    }))
    .submit(method, url, {
      forceFormData: true,
      onStart: () => console.log("🚀 Enviando request a:", url),
      onProgress: (p) => console.log("📤 Subiendo...", p),
      onError: (errors) => {
        console.group("❌ Errores de validación");
        for (const [k, v] of Object.entries(errors)) console.log(`${k}:`, v);
        console.groupEnd();
      },
      onSuccess: () => console.log("✅ Evento guardado correctamente"),
      onFinish: () => console.log("🏁 Proceso finalizado"),
    });
};
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Título y fecha -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="text-gray-300 text-sm">Título del evento</label>
        <input v-model="form.title" type="text" class="input" placeholder="Nombre del evento" />
        <p v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</p>
      </div>

      <div>
        <label class="text-gray-300 text-sm">Fecha del evento</label>
        <input v-model="form.event_date" type="date" class="input" />
      </div>
    </div>

    <!-- Ubicación -->
    <div>
      <label class="text-gray-300 text-sm">Ubicación</label>
      <input v-model="form.location" type="text" class="input" placeholder="Ciudad, país o venue" />
    </div>

    <!-- Descripción -->
    <div>
      <label class="text-gray-300 text-sm">Descripción</label>
      <textarea v-model="form.description" rows="4" class="input" placeholder="Detalles del evento..."></textarea>
    </div>

    <!-- Póster -->
    <div>
      <label class="text-gray-300 text-sm">Póster / Afiche</label>
      <input
        type="file"
        class="input-file"
        @change="(e) => {
          form.poster_file = e.target.files[0];
          console.log('🖼 Archivo seleccionado:', e.target.files[0]);
        }"
      />
      <p v-if="form.errors.poster_file" class="text-red-500 text-sm mt-1">
        {{ form.errors.poster_file }}
      </p>
    </div>

    <!-- Artistas -->
    <div>
      <label class="text-gray-300 text-sm">Artistas invitados</label>
      <select v-model="form.artist_ids" multiple class="input">
        <option v-for="a in artists" :key="a.id" :value="a.id">{{ a.name }}</option>
      </select>
      <p class="text-gray-500 text-xs mt-1">
        Puedes seleccionar varios artistas (Ctrl/Cmd + click)
      </p>
    </div>

    <div class="flex justify-end">
      <button type="submit" class="btn-primary">
        {{ props.mode === "edit" ? "Actualizar evento" : "Guardar evento" }}
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

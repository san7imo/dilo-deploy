<script setup>
import { useForm } from "@inertiajs/vue3";
import FormActions from "@/Components/FormActions.vue";

const props = defineProps({
  composition: {
    type: Object,
    default: () => ({}),
  },
  tracks: {
    type: Array,
    default: () => [],
  },
  mode: {
    type: String,
    default: "create",
  },
  cancelHref: {
    type: String,
    default: "",
  },
});

const form = useForm({
  title: props.composition.title || "",
  iswc: props.composition.iswc || "",
  notes: props.composition.notes || "",
  track_ids: props.composition.tracks ? props.composition.tracks.map((track) => track.id) : [],
});

const handleSubmit = () => {
  if (props.mode === "edit") {
    form.put(route("admin.compositions.update", props.composition.id));
    return;
  }

  form.post(route("admin.compositions.store"));
};
</script>

<template>
  <form class="space-y-6" @submit.prevent="handleSubmit">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="text-gray-300 text-sm">Título de la composición</label>
        <input v-model="form.title" type="text" class="input" placeholder="Nombre de la obra" />
        <p v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</p>
      </div>
      <div>
        <label class="text-gray-300 text-sm">ISWC</label>
        <input v-model="form.iswc" type="text" class="input" placeholder="T-123.456.789-0" />
        <p v-if="form.errors.iswc" class="text-red-500 text-sm mt-1">{{ form.errors.iswc }}</p>
      </div>
    </div>

    <div>
      <label class="text-gray-300 text-sm">Tracks asociados</label>
      <select v-model="form.track_ids" multiple class="input min-h-[170px]">
        <option v-for="track in tracks" :key="track.id" :value="track.id">
          {{ track.title }}{{ track.isrc ? ` · ${track.isrc}` : "" }}
        </option>
      </select>
      <p class="text-xs text-gray-500 mt-1">Puedes seleccionar varios tracks (Ctrl/Cmd + click).</p>
      <p v-if="form.errors.track_ids" class="text-red-500 text-sm mt-1">{{ form.errors.track_ids }}</p>
      <p v-if="form.errors['track_ids.0']" class="text-red-500 text-sm mt-1">{{ form.errors['track_ids.0'] }}</p>
    </div>

    <div>
      <label class="text-gray-300 text-sm">Notas</label>
      <textarea v-model="form.notes" rows="4" class="input" placeholder="Información editorial adicional"></textarea>
      <p v-if="form.errors.notes" class="text-red-500 text-sm mt-1">{{ form.errors.notes }}</p>
    </div>

    <div class="rounded-md border border-[#2a2a2a] bg-[#151515] p-4">
      <h3 class="text-sm font-semibold text-[#ffa236]">Registros editoriales</h3>
      <p class="mt-1 text-xs text-gray-400">
        El ISWC se sincroniza automáticamente en la base de registros de composición.
      </p>

      <div v-if="(props.composition.registrations || []).length" class="mt-3 space-y-2">
        <div
          v-for="registration in props.composition.registrations"
          :key="registration.id"
          class="rounded border border-[#2a2a2a] px-3 py-2 text-xs text-gray-300"
        >
          <div class="font-medium uppercase">{{ registration.registration_type }}</div>
          <div class="text-gray-400">
            {{ registration.registration_number }}
            <span v-if="registration.society_name"> · {{ registration.society_name }}</span>
            <span v-if="registration.territory_code"> · {{ registration.territory_code }}</span>
          </div>
        </div>
      </div>
      <p v-else class="mt-3 text-xs text-gray-500">No hay registros todavía.</p>
    </div>

    <FormActions
      :cancel-href="props.cancelHref"
      :submit-label="props.mode === 'edit' ? 'Actualizar composición' : 'Guardar composición'"
      :processing="form.processing"
    />
  </form>
</template>

<style scoped>
.input {
  @apply w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}
</style>

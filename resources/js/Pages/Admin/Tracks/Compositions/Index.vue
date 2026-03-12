<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";

const props = defineProps({
  track: { type: Object, required: true },
  compositions: { type: Array, default: () => [] },
  available_compositions: { type: Array, default: () => [] },
});

const createForm = useForm({
  title: "",
  iswc: "",
  notes: "",
});

const attachForm = useForm({
  composition_id: "",
});
const useExistingComposition = ref(false);

watch(useExistingComposition, (enabled) => {
  if (!enabled) {
    attachForm.reset("composition_id");
    attachForm.clearErrors();
  }
});

const createComposition = () => {
  createForm.post(route("admin.tracks.compositions.store", props.track.id), {
    preserveScroll: true,
    onSuccess: () => createForm.reset(),
  });
};

const attachComposition = () => {
  attachForm.post(route("admin.tracks.compositions.attach", props.track.id), {
    preserveScroll: true,
    onSuccess: () => attachForm.reset("composition_id"),
  });
};

const detachComposition = (compositionId) => {
  router.delete(route("admin.tracks.compositions.detach", [props.track.id, compositionId]), {
    preserveScroll: true,
  });
};
</script>

<template>
  <AdminLayout title="Track · Composiciones">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-white">Track · {{ track.title }}</h1>
        <p class="text-sm text-gray-400">
          {{ track.release?.title || "-" }} · ISRC: {{ track.isrc || "-" }}
        </p>
      </div>
      <div class="flex items-center gap-2">
        <Link :href="route('admin.tracks.index')" class="btn-secondary">Volver a tracks</Link>
        <Link :href="route('admin.tracks.splits.index', track.id)" class="btn-secondary">Split master</Link>
      </div>
    </div>

    <div
      class="grid grid-cols-1 gap-4 mb-6"
      :class="{ 'xl:grid-cols-2': useExistingComposition }"
    >
      <section class="panel">
        <h2 class="text-lg font-semibold text-[#ffa236] mb-3">Crear composición para este track</h2>
        <form class="space-y-3" @submit.prevent="createComposition">
          <div>
            <label class="label">Título</label>
            <input v-model="createForm.title" type="text" class="input" placeholder="Nombre de la composición" />
            <p v-if="createForm.errors.title" class="error">{{ createForm.errors.title }}</p>
          </div>
          <div>
            <label class="label">ISWC</label>
            <input v-model="createForm.iswc" type="text" class="input" placeholder="T-123.456.789-0" />
            <p v-if="createForm.errors.iswc" class="error">{{ createForm.errors.iswc }}</p>
          </div>
          <div>
            <label class="label">Notas</label>
            <textarea v-model="createForm.notes" rows="3" class="input" placeholder="Notas editoriales"></textarea>
            <p v-if="createForm.errors.notes" class="error">{{ createForm.errors.notes }}</p>
          </div>
          <button type="submit" class="btn-primary" :disabled="createForm.processing">
            {{ createForm.processing ? "Creando..." : "Crear y vincular" }}
          </button>
        </form>

        <div class="mt-5 border-t border-[#2a2a2a] pt-4">
          <label class="inline-flex items-center gap-2 text-sm text-gray-300 cursor-pointer">
            <input v-model="useExistingComposition" type="checkbox" class="checkbox" />
            Esta pista usa una composición ya existente
          </label>
          <p class="text-xs text-gray-500 mt-1">
            Úsalo para casos como remix, acústico, live o nueva versión de una obra ya registrada.
          </p>
        </div>
      </section>

      <section v-if="useExistingComposition" class="panel">
        <h2 class="text-lg font-semibold text-[#ffa236] mb-3">Vincular composición existente</h2>
        <form class="space-y-3" @submit.prevent="attachComposition">
          <div>
            <label class="label">Composición</label>
            <select v-model="attachForm.composition_id" class="input">
              <option value="">Selecciona una composición</option>
              <option
                v-for="composition in available_compositions"
                :key="composition.id"
                :value="composition.id"
              >
                {{ composition.title }}{{ composition.iswc ? ` · ${composition.iswc}` : "" }}
              </option>
            </select>
            <p v-if="attachForm.errors.composition_id" class="error">
              {{ attachForm.errors.composition_id }}
            </p>
            <p v-if="!available_compositions.length" class="text-xs text-gray-500 mt-1">
              No hay composiciones disponibles para vincular.
            </p>
          </div>
          <button type="submit" class="btn-primary" :disabled="attachForm.processing">
            {{ attachForm.processing ? "Vinculando..." : "Vincular composición" }}
          </button>
        </form>
      </section>
    </div>

    <section class="panel">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold text-[#ffa236]">Composiciones vinculadas</h2>
        <span class="text-sm text-gray-400">{{ compositions.length }} vinculadas</span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-gray-300">
          <thead class="text-xs uppercase text-gray-400">
            <tr>
              <th class="px-3 py-2 text-left">Composición</th>
              <th class="px-3 py-2 text-left">ISWC</th>
              <th class="px-3 py-2 text-left">Splits composición</th>
              <th class="px-3 py-2 text-left">Tracks vinculados</th>
              <th class="px-3 py-2 text-left">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="composition in compositions"
              :key="composition.id"
              class="border-t border-[#2a2a2a]"
            >
              <td class="px-3 py-2">{{ composition.title }}</td>
              <td class="px-3 py-2">{{ composition.iswc || "-" }}</td>
              <td class="px-3 py-2">{{ composition.split_sets_count ?? 0 }}</td>
              <td class="px-3 py-2">{{ composition.tracks_count ?? 0 }}</td>
              <td class="px-3 py-2">
                <div class="flex flex-wrap items-center gap-3">
                  <Link
                    :href="route('admin.compositions.splits.index', { composition: composition.id, back: route('admin.tracks.compositions.index', track.id) })"
                    class="action-link"
                  >
                    Ver splits
                  </Link>
                  <Link
                    :href="route('admin.compositions.splits.create', { composition: composition.id, back: route('admin.tracks.compositions.index', track.id) })"
                    class="action-link"
                  >
                    Nuevo split
                  </Link>
                  <Link :href="route('admin.compositions.edit', composition.id)" class="action-link">
                    Editar composición
                  </Link>
                  <button type="button" class="action-danger" @click="detachComposition(composition.id)">
                    Desvincular
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!compositions.length">
              <td colspan="5" class="px-3 py-6 text-center text-gray-400">
                Este track no tiene composiciones vinculadas.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </AdminLayout>
</template>

<style scoped>
.panel {
  @apply bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4;
}

.label {
  @apply text-sm text-gray-300;
}

.input {
  @apply mt-1 w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}

.checkbox {
  @apply h-4 w-4 rounded border-[#2a2a2a] bg-[#0f0f0f] text-[#ffa236] focus:ring-[#ffa236];
}

.error {
  @apply text-red-400 text-xs mt-1;
}

.btn-primary {
  @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}

.btn-secondary {
  @apply bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors;
}

.action-link {
  @apply text-[#ffa236] hover:underline text-sm;
}

.action-danger {
  @apply text-red-300 hover:underline text-sm;
}
</style>

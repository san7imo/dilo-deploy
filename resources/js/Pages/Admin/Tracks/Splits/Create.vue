<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import FormActions from "@/Components/FormActions.vue";
import { useForm } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
  track: Object,
  artists: Array,
  externalArtists: {
    type: Array,
    default: () => [],
  },
});

const roleOptions = [
  { value: "label", label: "Label" },
  { value: "artist", label: "Artist" },
  { value: "producer", label: "Producer" },
  { value: "composer", label: "Composer" },
  { value: "publisher", label: "Publisher" },
  { value: "manager", label: "Manager" },
];

const participantTypeOptions = [
  { value: "internal", label: "Artista Dilo (interno)" },
  { value: "external_existing", label: "Artista externo existente" },
  { value: "external_new", label: "Artista externo nuevo (invitar)" },
  { value: "manual", label: "Manual / sin usuario" },
];

const participantTypeMeta = {
  internal: {
    cardClass: "border-emerald-500/30 bg-emerald-500/5",
    badgeClass: "border-emerald-500/40 bg-emerald-500/15 text-emerald-200",
    hintClass: "text-emerald-300",
    hint: "Participante interno de Dilo Records.",
  },
  external_existing: {
    cardClass: "border-sky-500/30 bg-sky-500/5",
    badgeClass: "border-sky-500/40 bg-sky-500/15 text-sky-200",
    hintClass: "text-sky-300",
    hint: "Participante externo con cuenta existente.",
  },
  external_new: {
    cardClass: "border-amber-500/30 bg-amber-500/5",
    badgeClass: "border-amber-500/40 bg-amber-500/15 text-amber-200",
    hintClass: "text-amber-300",
    hint: "Se enviará invitación al correo registrado.",
  },
  manual: {
    cardClass: "border-zinc-500/30 bg-zinc-500/5",
    badgeClass: "border-zinc-500/40 bg-zinc-500/15 text-zinc-200",
    hintClass: "text-zinc-300",
    hint: "Registro manual sin vinculación de usuario.",
  },
};

const createParticipant = (overrides = {}) => ({
  role: "artist",
  percentage: 0,
  participant_type: "internal",
  artist_id: "",
  user_id: "",
  name: "",
  payee_email: "",
  ...overrides,
});

const form = useForm({
  split_type: "master",
  contract: null,
  effective_from: "",
  effective_to: "",
  participants: [
    createParticipant({
      role: "label",
      participant_type: "manual",
      name: "Dilo Records",
    }),
  ],
});

const totalPercentage = computed(() =>
  form.participants.reduce((sum, p) => sum + Number(p.percentage || 0), 0)
);

const addParticipant = () => {
  form.participants.push(createParticipant());
};

const removeParticipant = (index) => {
  form.participants.splice(index, 1);
};

const onParticipantTypeChange = (participant) => {
  participant.artist_id = "";
  participant.user_id = "";

  if (participant.participant_type === "internal" || participant.participant_type === "external_existing") {
    participant.payee_email = "";
    if (participant.role !== "label") {
      participant.name = "";
    }
  }

  if (participant.participant_type === "manual" && participant.role === "label" && !participant.name) {
    participant.name = "Dilo Records";
  }
};

const onRoleChange = (participant) => {
  if (participant.role === "label" && participant.participant_type === "manual" && !participant.name) {
    participant.name = "Dilo Records";
  }
};

const participantTypeCardClass = (participantType) =>
  participantTypeMeta[participantType]?.cardClass || "border-[#2a2a2a] bg-[#0f0f0f]";

const participantTypeHintClass = (participantType) =>
  participantTypeMeta[participantType]?.hintClass || "text-gray-400";

const participantTypeHint = (participantType) =>
  participantTypeMeta[participantType]?.hint || "";

const participantTypeBadgeClass = (participantType) =>
  participantTypeMeta[participantType]?.badgeClass || "border-[#3a3a3a] bg-[#171717] text-gray-200";

const participantTypeLabel = (participantType) =>
  participantTypeOptions.find((option) => option.value === participantType)?.label || "Participante";

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
      <BackNavButton :href="route('admin.tracks.splits.index', track.id)" />
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <div class="panel max-w-xl">
        <label class="field-label">Contrato</label>
        <input
          type="file"
          class="input-file"
          @change="(e) => { form.contract = e.target.files[0]; }"
        />
        <p class="text-xs text-gray-500 mt-2">Sube PDF/JPG/PNG del acuerdo firmado.</p>
        <p v-if="form.errors.contract" class="text-red-500 text-sm mt-1">
          {{ form.errors.contract }}
        </p>
      </div>

      <div class="panel grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
        <div>
          <label class="field-label">Vigencia desde (opcional)</label>
          <input v-model="form.effective_from" type="date" class="input mt-2" />
          <p v-if="form.errors.effective_from" class="text-red-500 text-sm mt-1">
            {{ form.errors.effective_from }}
          </p>
        </div>
        <div>
          <label class="field-label">Vigencia hasta (opcional)</label>
          <input v-model="form.effective_to" type="date" class="input mt-2" />
          <p v-if="form.errors.effective_to" class="text-red-500 text-sm mt-1">
            {{ form.errors.effective_to }}
          </p>
        </div>
      </div>

      <div class="panel">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-[#ffa236] font-semibold">Participantes</h3>
          <button type="button" class="btn-secondary" @click="addParticipant">
            + Agregar
          </button>
        </div>

        <div class="text-sm text-gray-400 mb-4">
          Total: {{ totalPercentage.toFixed(2) }}%
        </div>

        <div class="space-y-4">
          <div
            v-for="(participant, index) in form.participants"
            :key="index"
            class="rounded-lg border p-4 space-y-4"
            :class="participantTypeCardClass(participant.participant_type)"
          >
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-sm font-semibold text-white">Participante #{{ index + 1 }}</p>
                <p class="text-xs" :class="participantTypeHintClass(participant.participant_type)">
                  {{ participantTypeHint(participant.participant_type) }}
                </p>
              </div>
              <span
                class="text-[11px] uppercase tracking-wide px-2 py-1 rounded-full border"
                :class="participantTypeBadgeClass(participant.participant_type)"
              >
                {{ participantTypeLabel(participant.participant_type) }}
              </span>
              <button
                type="button"
                class="btn-danger"
                @click="removeParticipant(index)"
                :disabled="form.participants.length === 1"
              >
                Eliminar
              </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
              <div class="lg:col-span-2">
                <label class="field-label">Rol</label>
                <select v-model="participant.role" class="input" @change="onRoleChange(participant)">
                  <option
                    v-for="option in roleOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
              </div>

              <div class="lg:col-span-2">
                <label class="field-label">%</label>
                <input v-model="participant.percentage" type="number" step="0.01" min="0" max="100" class="input text-right" />
              </div>

              <div class="lg:col-span-3">
                <label class="field-label">Tipo participante</label>
                <select
                  v-model="participant.participant_type"
                  class="input"
                  @change="onParticipantTypeChange(participant)"
                >
                  <option
                    v-for="option in participantTypeOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
              </div>

              <template v-if="participant.participant_type === 'internal'">
                <div class="lg:col-span-3">
                  <label class="field-label">Artista Dilo Records</label>
                  <select v-model="participant.artist_id" class="input">
                    <option value="">Selecciona artista interno</option>
                    <option v-for="a in artists" :key="a.id" :value="a.id">{{ a.name }}</option>
                  </select>
                </div>
              </template>

              <template v-else-if="participant.participant_type === 'external_existing'">
                <div class="lg:col-span-3">
                  <label class="field-label">Artista externo existente</label>
                  <select v-model="participant.user_id" class="input">
                    <option value="">Selecciona artista externo</option>
                    <option v-for="external in externalArtists" :key="external.id" :value="external.id">
                      {{ external.stage_name || external.name }} · {{ external.email }}
                    </option>
                  </select>
                </div>
              </template>

              <template v-else>
                <div class="lg:col-span-3">
                  <label class="field-label">Nombre</label>
                  <input v-model="participant.name" type="text" class="input" placeholder="Nombre visible" />
                </div>
              </template>

              <div class="lg:col-span-2">
                <label class="field-label">Email</label>
                <input
                  v-model="participant.payee_email"
                  type="email"
                  class="input"
                  :disabled="participant.participant_type === 'internal' || participant.participant_type === 'external_existing'"
                  :placeholder="participant.participant_type === 'external_new' ? 'Email para invitación' : 'Opcional'"
                />
                <p
                  v-if="participant.participant_type === 'internal' || participant.participant_type === 'external_existing'"
                  class="text-[11px] text-gray-500 mt-1"
                >
                  Email gestionado por el usuario seleccionado.
                </p>
              </div>
            </div>

            <p v-if="form.errors[`participants.${index}`]" class="text-red-500 text-xs">
              {{ form.errors[`participants.${index}`] }}
            </p>
            <p v-if="form.errors[`participants.${index}.artist_id`]" class="text-red-500 text-xs">
              {{ form.errors[`participants.${index}.artist_id`] }}
            </p>
            <p v-if="form.errors[`participants.${index}.user_id`]" class="text-red-500 text-xs">
              {{ form.errors[`participants.${index}.user_id`] }}
            </p>
            <p v-if="form.errors[`participants.${index}.payee_email`]" class="text-red-500 text-xs">
              {{ form.errors[`participants.${index}.payee_email`] }}
            </p>
            <p v-if="form.errors[`participants.${index}.name`]" class="text-red-500 text-xs">
              {{ form.errors[`participants.${index}.name`] }}
            </p>
          </div>
        </div>

        <p v-if="form.errors.participants" class="text-red-500 text-sm">
          {{ form.errors.participants }}
        </p>
      </div>

      <FormActions
        :cancel-href="route('admin.tracks.splits.index', track.id)"
        submit-label="Guardar Split"
        processing-label="Guardando..."
        :processing="form.processing"
      />
    </form>
  </AdminLayout>
</template>

<style scoped>
.panel {
  @apply bg-[#0f0f0f] border border-[#2a2a2a] rounded-xl p-5;
}
.input {
  @apply w-full h-11 bg-[#111111] border border-[#3a3a3a] rounded-md px-3 text-white placeholder:text-gray-500 focus:border-[#ffa236] focus:ring-2 focus:ring-[#ffa236]/20 outline-none transition;
}
.input:disabled {
  @apply bg-[#0d0d0d] border-[#2a2a2a] text-gray-500 cursor-not-allowed opacity-80;
}
.field-label {
  @apply block text-[11px] uppercase tracking-wide text-gray-300 mb-1.5;
}
.input-file {
  @apply block w-full text-sm text-gray-300 border border-[#3a3a3a] rounded-md cursor-pointer bg-[#111111] file:bg-[#ffa236] file:text-black file:px-3 file:py-2 file:border-0;
}
.btn-secondary {
  @apply border border-[#ffa236] text-[#ffa236] px-3 py-1.5 rounded-md text-sm hover:bg-[#ffa236]/10 transition;
}
.btn-danger {
  @apply h-[34px] px-3 border border-[#5a1f1f] rounded-md text-red-300 hover:text-red-200 text-sm disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>

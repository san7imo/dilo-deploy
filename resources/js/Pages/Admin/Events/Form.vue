<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import { computed, watch } from "vue";

const props = defineProps({
  event: { type: Object, default: () => ({}) },
  artists: { type: Array, default: () => [] },
  roadManagers: { type: Array, default: () => [] },
  mode: { type: String, default: "create" },
});

const { props: pageProps } = usePage();
const roleNames = computed(() => pageProps.auth?.user?.role_names || []);
const currentUserId = computed(() => pageProps.auth?.user?.id || null);
const canEditFinance = computed(() => roleNames.value.includes("admin"));
const isRoadManager = computed(() => roleNames.value.includes("roadmanager"));
const canAssignRoadManagers = computed(() => !isRoadManager.value);

const form = useForm({
  title: props.event.title || "",
  description: props.event.description || "",
  location: props.event.location || "",
  event_date: props.event.event_date || "",
  event_type: props.event.event_type || "",
  country: props.event.country || "",
  city: props.event.city || "",
  venue_address: props.event.venue_address || "",
  whatsapp_event: props.event.whatsapp_event || "",
  page_tickets: props.event.page_tickets || "",
  show_fee_total: props.event.show_fee_total || "",
  currency: props.event.currency || "USD",
  advance_percentage: props.event.advance_percentage || 50,
  advance_expected: props.event.advance_expected ?? true,
  full_payment_due_date: props.event.full_payment_due_date || "",
  status: props.event.status || "",
  artist_ids: props.event.artists ? props.event.artists.map(a => a.id) : [],
  main_artist_id: props.event.main_artist_id || null,
  road_manager_ids: props.event.road_managers ? props.event.road_managers.map(r => r.id) : [],
  poster_file: null,
});

watch(
  () => form.artist_ids,
  (artistIds) => {
    if (Array.isArray(artistIds) && artistIds.length === 1) {
      form.main_artist_id = artistIds[0];
      return;
    }

    if (!artistIds?.includes(form.main_artist_id)) {
      form.main_artist_id = null;
    }
  },
  { deep: true }
);

watch(
  [isRoadManager, currentUserId],
  ([roadManager, userId]) => {
    if (props.mode === "create" && roadManager && userId) {
      form.road_manager_ids = [userId];
    }
  },
  { immediate: true }
);

const handleSubmit = () => {
  console.log("🎤 Enviando formulario de evento...", form.data());

  const method = "post";
  const url =
    props.mode === "edit"
      ? route("admin.events.update", props.event.id)
      : route("admin.events.store");

  form
    .transform((data) => {
      const payload = {
        ...data,
        _method: props.mode === "edit" ? "put" : "post",
      };

      if (!canEditFinance.value) {
        delete payload.show_fee_total;
        delete payload.currency;
        delete payload.advance_percentage;
        delete payload.advance_expected;
        delete payload.full_payment_due_date;
      }

      if (!canAssignRoadManagers.value) {
        delete payload.road_manager_ids;
      }

      return payload;
    })
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

    <!-- Contacto y boletería -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="text-gray-300 text-sm">WhatsApp del evento</label>
        <input v-model="form.whatsapp_event" type="text" class="input"
          placeholder="Ej: +57 300 123 4567 o https://wa.me/573001234567" />
        <p class="text-gray-500 text-xs mt-1">Opcional. Se mostrará solo si tiene valor.</p>
      </div>
      <div>
        <label class="text-gray-300 text-sm">Página de tickets</label>
        <input v-model="form.page_tickets" type="text" class="input"
          placeholder="https://tus-tickets.com/evento" />
        <p class="text-gray-500 text-xs mt-1">Opcional. Se mostrará solo si tiene valor.</p>
      </div>
    </div>

    <!-- Nuevo: Localización  -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div>
        <label class="text-gray-300 text-sm">País</label>
        <input v-model="form.country" type="text" class="input" placeholder="País" />
      </div>
      <div>
        <label class="text-gray-300 text-sm">Ciudad</label>
        <input v-model="form.city" type="text" class="input" placeholder="Ciudad" />
      </div>
      <div>
        <label class="text-gray-300 text-sm">Dirección del venue</label>
        <input v-model="form.venue_address" type="text" class="input" placeholder="Dirección" />
      </div>
    </div>

    <!-- Nuevo: Tipo de evento y estado -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="text-gray-300 text-sm">Tipo de evento</label>
        <select v-model="form.event_type" class="input">
          <option value="">Selecciona un tipo</option>
          <option value="masivo">Masivo</option>
          <option value="publico">Público</option>
          <option value="discoteca">Discoteca</option>
          <option value="privado">Privado</option>
          <option value="meet_and_greet">Meet & Greet</option>
        </select>
      </div>
      <div>
        <label class="text-gray-300 text-sm">Estado</label>
        <select v-model="form.status" class="input">
          <option value="">Selecciona un estado</option>
          <option value="cotizado">Cotizado</option>
          <option value="reservado">Reservado</option>
          <option value="confirmado">Confirmado</option>
          <option value="pospuesto">Pospuesto</option>
          <option value="pagado">Pagado</option>
          <option value="cancelado">Cancelado</option>
        </select>
        <p class="text-gray-500 text-xs mt-1">
          Pagado solo aplica cuando los pagos cubren el fee del show.
        </p>
      </div>
    </div>

    <!-- Descripción -->
    <div>
      <label class="text-gray-300 text-sm">Descripción</label>
      <textarea v-model="form.description" rows="4" class="input" placeholder="Detalles del evento..."></textarea>
    </div>

    <!-- Póster -->
    <div>
      <label class="text-gray-300 text-sm">Póster / Afiche</label>
      <input type="file" class="input-file" @change="(e) => {
        form.poster_file = e.target.files[0];
        console.log('🖼 Archivo seleccionado:', e.target.files[0]);
      }" />
      <p v-if="form.errors.poster_file" class="text-red-500 text-sm mt-1">
        {{ form.errors.poster_file }}
      </p>
    </div>

    <!-- Finanzas del evento (solo admin) -->
    <div v-if="canEditFinance">
      <label class="text-gray-300 text-sm">Finanzas del evento</label>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-gray-300 text-sm">Fee total (show)</label>
          <input v-model="form.show_fee_total" type="number" step="0.01" class="input" placeholder="0.00" />
        </div>
        <div>
          <label class="text-gray-300 text-sm">Moneda</label>
          <input v-model="form.currency" type="text" class="input" placeholder="USD" maxlength="3" />
        </div>
        <div>
          <label class="text-gray-300 text-sm">% Anticipo</label>
          <input v-model="form.advance_percentage" type="number" step="0.01" class="input" placeholder="50" />
        </div>
        <div>
          <label class="text-gray-300 text-sm">Fecha pago final</label>
          <input v-model="form.full_payment_due_date" type="date" class="input" />
        </div>
      </div>
      <div class="mt-3 flex items-center gap-2">
        <input v-model="form.advance_expected" type="checkbox" class="checkbox" />
        <span class="text-gray-300 text-sm">¿Se espera anticipo?</span>
      </div>
    </div>

    <!-- Artistas -->
    <div>
      <label class="text-gray-300 text-sm">Lista de artistas</label>
      <select v-model="form.artist_ids" multiple class="input">
        <option v-for="a in artists" :key="a.id" :value="a.id">{{ a.name }}</option>
      </select>
      <p class="text-gray-500 text-xs mt-1">
        Puedes seleccionar varios artistas (Ctrl/Cmd + click)
      </p>
    </div>

    <div v-if="canAssignRoadManagers">
      <label class="text-gray-300 text-sm">Road managers asignados</label>
      <select v-model="form.road_manager_ids" multiple class="input">
        <option v-for="rm in roadManagers" :key="rm.id" :value="rm.id">
          {{ rm.name }} ({{ rm.email }})
        </option>
      </select>
      <p class="text-gray-500 text-xs mt-1">
        Selecciona los road managers que podran ver y reportar finanzas de este evento.
      </p>
    </div>

    <div>
      <label class="text-gray-300 text-sm">Artista principal</label>
      <select v-model="form.main_artist_id" class="input">
        <option value="" disabled>Seleccione el artista principal</option>
        <option v-for="a in artists.filter(a => form.artist_ids.includes(a.id))" :key="a.id" :value="a.id">
          {{ a.name }}
        </option>
      </select>
      <p class="text-gray-500 text-xs mt-1">
        selecciona el artista principal de la lista de invitados
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

.checkbox {
  @apply w-4 h-4 rounded bg-[#0f0f0f] border border-[#2a2a2a] text-[#ffa236] focus:ring-[#ffa236];
}
</style>

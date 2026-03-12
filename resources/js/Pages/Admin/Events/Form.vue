<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, watch } from "vue";
import FormActions from "@/Components/FormActions.vue";

const props = defineProps({
  event: { type: Object, default: () => ({}) },
  artists: { type: Array, default: () => [] },
  roadManagers: { type: Array, default: () => [] },
  organizers: { type: Array, default: () => [] },
  mode: { type: String, default: "create" },
  cancelHref: { type: String, default: "" },
});

const { props: pageProps } = usePage();
const roleNames = computed(() => pageProps.auth?.user?.role_names || []);
const currentUserId = computed(() => pageProps.auth?.user?.id || null);
const canEditFinance = computed(() => roleNames.value.includes("admin"));
const isRoadManager = computed(() => roleNames.value.includes("roadmanager"));
const canAssignRoadManagers = computed(() => !isRoadManager.value);
const canManageOrganizers = computed(
  () => roleNames.value.includes("admin") || roleNames.value.includes("contentmanager")
);

const buildCountryOptions = () => {
  if (typeof Intl === "undefined" || typeof Intl.DisplayNames !== "function") {
    return [
      "Argentina",
      "Bolivia",
      "Brasil",
      "Chile",
      "Colombia",
      "Costa Rica",
      "Ecuador",
      "El Salvador",
      "España",
      "Estados Unidos",
      "Guatemala",
      "Honduras",
      "México",
      "Panamá",
      "Paraguay",
      "Perú",
      "República Dominicana",
      "Uruguay",
      "Venezuela",
    ];
  }

  const regionNames = new Intl.DisplayNames(["es"], { type: "region" });
  const options = [];

  for (let first = 65; first <= 90; first += 1) {
    for (let second = 65; second <= 90; second += 1) {
      const code = String.fromCharCode(first) + String.fromCharCode(second);
      const name = regionNames.of(code);

      if (!name || name === code) continue;
      if (name.toLowerCase().includes("región")) continue;

      options.push(name);
    }
  }

  return [...new Set(options)].sort((a, b) =>
    a.localeCompare(b, "es", { sensitivity: "base" })
  );
};

const countryOptions = buildCountryOptions();

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
  organizer_id: props.event.organizer_id ?? "",
  google_maps_url: props.event.google_maps_url || "",
  google_maps_place_id: props.event.google_maps_place_id || "",
  latitude: props.event.latitude || "",
  longitude: props.event.longitude || "",
  show_fee_total: props.event.show_fee_total || "",
  currency: props.event.currency || "USD",
  advance_percentage: props.event.advance_percentage || 50,
  artist_share_percentage: props.event.artist_share_percentage ?? 70,
  label_share_percentage: props.event.label_share_percentage ?? 30,
  advance_expected: props.event.advance_expected ?? true,
  full_payment_due_date: props.event.full_payment_due_date || "",
  status: props.event.status || "",
  artist_ids: props.event.artists ? props.event.artists.map(a => a.id) : [],
  main_artist_id: props.event.main_artist_id || null,
  road_manager_ids: props.event.road_managers ? props.event.road_managers.map(r => r.id) : [],
  sponsors: Array.isArray(props.event.sponsors)
    ? props.event.sponsors.map((item) => ({
      name: item?.name || "",
      image_url: item?.image_url || "",
    }))
    : [],
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

const normalizeUrl = (value) => {
  if (!value) return "";
  const trimmed = String(value).trim();
  if (!trimmed) return "";
  if (/^https?:\/\//i.test(trimmed)) return trimmed;
  return `https://${trimmed}`;
};

const mapsPreviewLink = computed(() => {
  const manualUrl = normalizeUrl(form.google_maps_url);
  if (manualUrl) return manualUrl;

  if (form.latitude !== "" && form.longitude !== "") {
    return `https://www.google.com/maps?q=${encodeURIComponent(form.latitude)},${encodeURIComponent(form.longitude)}`;
  }

  const textParts = [form.venue_address, form.city, form.country, form.location]
    .map((part) => String(part || "").trim())
    .filter(Boolean);

  if (textParts.length === 0) return "";

  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(textParts.join(", "))}`;
});

const addSponsor = () => {
  form.sponsors.push({ name: "", image_url: "" });
};

const removeSponsor = (index) => {
  form.sponsors.splice(index, 1);
};

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

      if (payload.organizer_id === "" || payload.organizer_id === null) {
        payload.organizer_id = null;
      } else {
        payload.organizer_id = Number(payload.organizer_id);
      }

      if (!canEditFinance.value) {
        delete payload.show_fee_total;
        delete payload.currency;
        delete payload.advance_percentage;
        delete payload.artist_share_percentage;
        delete payload.label_share_percentage;
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

    <!-- Organizador -->
    <div class="space-y-4">
      <div>
        <p class="text-sm font-medium text-gray-200">Organizador / Empresario</p>
        <p class="text-xs text-gray-500">
          Selecciona un empresario de la base de datos. Si no existe, créalo y vuelve a seleccionarlo aquí.
        </p>
      </div>

      <div>
        <div class="flex items-center justify-between gap-3">
          <label class="text-gray-300 text-sm">Seleccionar empresario guardado</label>
          <Link
            v-if="canManageOrganizers"
            :href="route('admin.organizers.create')"
            target="_blank"
            class="text-xs font-semibold text-[#ffa236] hover:text-[#ffb54d] transition-colors"
          >
            + Nuevo empresario
          </Link>
        </div>
        <select v-model="form.organizer_id" class="input mt-1">
          <option value="">Sin empresario guardado</option>
          <option v-for="organizer in organizers" :key="organizer.id" :value="organizer.id">
            {{ organizer.company_name }}{{ organizer.contact_name ? ` · ${organizer.contact_name}` : "" }}
          </option>
        </select>
        <p v-if="form.errors.organizer_id" class="text-red-500 text-sm mt-1">{{ form.errors.organizer_id }}</p>
        <p class="text-gray-500 text-xs mt-1">Tip: usa “+ Nuevo empresario” y vuelve a cargar este formulario.</p>
      </div>
    </div>

    <!-- Nuevo: Localización  -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div>
        <label class="text-gray-300 text-sm">País</label>
        <input
          v-model="form.country"
          type="text"
          class="input"
          list="event-country-options"
          placeholder="Selecciona o escribe un país"
        />
        <datalist id="event-country-options">
          <option v-for="country in countryOptions" :key="country" :value="country" />
        </datalist>
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

    <div class="space-y-4">
      <div>
        <p class="text-sm font-medium text-gray-200">Google Maps</p>
        <p class="text-xs text-gray-500">
          Puedes guardar una URL directa o coordenadas para mostrar "Cómo llegar" en la vista pública.
        </p>
      </div>

      <div>
        <label class="text-gray-300 text-sm">URL de Google Maps</label>
        <input
          v-model="form.google_maps_url"
          type="url"
          class="input"
          placeholder="https://maps.google.com/..."
        />
        <p v-if="form.errors.google_maps_url" class="text-red-500 text-sm mt-1">{{ form.errors.google_maps_url }}</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="text-gray-300 text-sm">Place ID (opcional)</label>
          <input
            v-model="form.google_maps_place_id"
            type="text"
            class="input"
            placeholder="ChIJ..."
          />
          <p v-if="form.errors.google_maps_place_id" class="text-red-500 text-sm mt-1">
            {{ form.errors.google_maps_place_id }}
          </p>
        </div>
        <div>
          <label class="text-gray-300 text-sm">Latitud</label>
          <input v-model="form.latitude" type="number" step="0.0000001" class="input" placeholder="4.7110" />
          <p v-if="form.errors.latitude" class="text-red-500 text-sm mt-1">{{ form.errors.latitude }}</p>
        </div>
        <div>
          <label class="text-gray-300 text-sm">Longitud</label>
          <input v-model="form.longitude" type="number" step="0.0000001" class="input" placeholder="-74.0721" />
          <p v-if="form.errors.longitude" class="text-red-500 text-sm mt-1">{{ form.errors.longitude }}</p>
        </div>
      </div>

      <a
        v-if="mapsPreviewLink"
        :href="mapsPreviewLink"
        target="_blank"
        rel="noopener"
        class="inline-flex items-center gap-2 rounded-md border border-[#2a2a2a] bg-[#171717] px-3 py-2 text-sm text-gray-200 hover:bg-[#1f1f1f] transition-colors"
      >
        <i class="fa-solid fa-map-location-dot text-[#ffa236]"></i>
        Previsualizar en Google Maps
      </a>
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
          <option value="labor_social">Labor social</option>
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
          Pagado solo aplica cuando los ingresos cubren el fee del show.
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
          <label class="text-gray-300 text-sm">Fecha de ingreso final</label>
          <input v-model="form.full_payment_due_date" type="date" class="input" />
        </div>
        <div class="sm:col-span-2">
          <label class="text-gray-300 text-sm">% de participación</label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="text-gray-300 text-xs">Artista</label>
              <input v-model="form.artist_share_percentage" type="number" step="0.01" class="input"
                placeholder="70" />
            </div>
            <div>
              <label class="text-gray-300 text-xs">Disquera</label>
              <input v-model="form.label_share_percentage" type="number" step="0.01" class="input"
                placeholder="30" />
            </div>
          </div>
          <p v-if="form.errors.artist_share_percentage" class="text-red-500 text-sm mt-1">
            {{ form.errors.artist_share_percentage }}
          </p>
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

    <div class="space-y-3">
      <div class="flex items-center justify-between gap-3">
        <div>
          <label class="text-gray-300 text-sm">Patrocinadores</label>
          <p class="text-gray-500 text-xs mt-1">Agrega nombre y logo (URL) de cada patrocinador del evento.</p>
        </div>
        <button
          type="button"
          class="bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white text-sm font-semibold px-3 py-2 rounded-md transition-colors"
          @click="addSponsor"
        >
          + Agregar patrocinador
        </button>
      </div>

      <div v-if="form.sponsors.length === 0" class="rounded-md border border-dashed border-[#2a2a2a] p-4 text-sm text-gray-500">
        No hay patrocinadores agregados.
      </div>

      <div v-for="(sponsor, index) in form.sponsors" :key="index" class="rounded-md border border-[#2a2a2a] p-4 space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="text-gray-300 text-sm">Nombre</label>
            <input v-model="sponsor.name" type="text" class="input" placeholder="Nombre del patrocinador" />
            <p v-if="form.errors[`sponsors.${index}.name`]" class="text-red-500 text-sm mt-1">
              {{ form.errors[`sponsors.${index}.name`] }}
            </p>
          </div>
          <div>
            <label class="text-gray-300 text-sm">Logo (URL)</label>
            <input v-model="sponsor.image_url" type="url" class="input" placeholder="https://..." />
            <p v-if="form.errors[`sponsors.${index}.image_url`]" class="text-red-500 text-sm mt-1">
              {{ form.errors[`sponsors.${index}.image_url`] }}
            </p>
          </div>
        </div>
        <button
          type="button"
          class="text-sm text-red-300 hover:text-red-200 transition-colors"
          @click="removeSponsor(index)"
        >
          Eliminar patrocinador
        </button>
      </div>
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


    <FormActions
      :cancel-href="props.cancelHref"
      :submit-label="props.mode === 'edit' ? 'Actualizar evento' : 'Guardar evento'"
      :processing="form.processing"
    />
  </form>
</template>

<style scoped>
.input {
  @apply w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}

.input-file {
  @apply block w-full text-sm text-gray-400 border border-[#2a2a2a] rounded-md cursor-pointer bg-[#0f0f0f] file:bg-[#ffa236] file:text-black file:px-3 file:py-1;
}

.checkbox {
  @apply w-4 h-4 rounded bg-[#0f0f0f] border border-[#2a2a2a] text-[#ffa236] focus:ring-[#ffa236];
}
</style>

<script setup>
import { useForm } from "@inertiajs/vue3";
import FormActions from "@/Components/FormActions.vue";

const props = defineProps({
  organizer: {
    type: Object,
    default: () => ({}),
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
  company_name: props.organizer.company_name || "",
  contact_name: props.organizer.contact_name || "",
  address: props.organizer.address || "",
  whatsapp: props.organizer.whatsapp || "",
  email: props.organizer.email || "",
  logo_url: props.organizer.logo_url || "",
  website: props.organizer.website || "",
  instagram_url: props.organizer.instagram_url || "",
  facebook_url: props.organizer.facebook_url || "",
  tiktok_url: props.organizer.tiktok_url || "",
  x_url: props.organizer.x_url || "",
  notes: props.organizer.notes || "",
});

const handleSubmit = () => {
  if (props.mode === "edit") {
    form.put(route("admin.organizers.update", props.organizer.id));
    return;
  }

  form.post(route("admin.organizers.store"));
};
</script>

<template>
  <form class="space-y-6" @submit.prevent="handleSubmit">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="text-gray-300 text-sm">Empresa organizadora</label>
        <input v-model="form.company_name" type="text" class="input" placeholder="Nombre de la empresa" />
        <p v-if="form.errors.company_name" class="text-red-500 text-sm mt-1">{{ form.errors.company_name }}</p>
      </div>
      <div>
        <label class="text-gray-300 text-sm">Nombre del empresario</label>
        <input v-model="form.contact_name" type="text" class="input" placeholder="Nombre del contacto" />
        <p v-if="form.errors.contact_name" class="text-red-500 text-sm mt-1">{{ form.errors.contact_name }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="text-gray-300 text-sm">Dirección</label>
        <input v-model="form.address" type="text" class="input" placeholder="Dirección" />
        <p v-if="form.errors.address" class="text-red-500 text-sm mt-1">{{ form.errors.address }}</p>
      </div>
      <div>
        <label class="text-gray-300 text-sm">WhatsApp</label>
        <input v-model="form.whatsapp" type="text" class="input" placeholder="+57 300 000 0000" />
        <p v-if="form.errors.whatsapp" class="text-red-500 text-sm mt-1">{{ form.errors.whatsapp }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="text-gray-300 text-sm">Correo</label>
        <input v-model="form.email" type="email" class="input" placeholder="contacto@empresa.com" />
        <p v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</p>
      </div>
      <div>
        <label class="text-gray-300 text-sm">Logo (URL)</label>
        <input v-model="form.logo_url" type="url" class="input" placeholder="https://..." />
        <p v-if="form.errors.logo_url" class="text-red-500 text-sm mt-1">{{ form.errors.logo_url }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="text-gray-300 text-sm">Sitio web</label>
        <input v-model="form.website" type="url" class="input" placeholder="https://..." />
        <p v-if="form.errors.website" class="text-red-500 text-sm mt-1">{{ form.errors.website }}</p>
      </div>
      <div>
        <label class="text-gray-300 text-sm">Instagram (URL)</label>
        <input v-model="form.instagram_url" type="url" class="input" placeholder="https://instagram.com/..." />
        <p v-if="form.errors.instagram_url" class="text-red-500 text-sm mt-1">{{ form.errors.instagram_url }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <div>
        <label class="text-gray-300 text-sm">Facebook (URL)</label>
        <input v-model="form.facebook_url" type="url" class="input" placeholder="https://facebook.com/..." />
        <p v-if="form.errors.facebook_url" class="text-red-500 text-sm mt-1">{{ form.errors.facebook_url }}</p>
      </div>
      <div>
        <label class="text-gray-300 text-sm">TikTok (URL)</label>
        <input v-model="form.tiktok_url" type="url" class="input" placeholder="https://tiktok.com/@..." />
        <p v-if="form.errors.tiktok_url" class="text-red-500 text-sm mt-1">{{ form.errors.tiktok_url }}</p>
      </div>
      <div>
        <label class="text-gray-300 text-sm">X / Twitter (URL)</label>
        <input v-model="form.x_url" type="url" class="input" placeholder="https://x.com/..." />
        <p v-if="form.errors.x_url" class="text-red-500 text-sm mt-1">{{ form.errors.x_url }}</p>
      </div>
    </div>

    <div>
      <label class="text-gray-300 text-sm">Notas</label>
      <textarea v-model="form.notes" rows="4" class="input" placeholder="Información adicional del empresario"></textarea>
      <p v-if="form.errors.notes" class="text-red-500 text-sm mt-1">{{ form.errors.notes }}</p>
    </div>

    <FormActions
      :cancel-href="props.cancelHref"
      :submit-label="props.mode === 'edit' ? 'Actualizar empresario' : 'Guardar empresario'"
      :processing="form.processing"
    />
  </form>
</template>

<style scoped>
.input {
  @apply w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}
</style>

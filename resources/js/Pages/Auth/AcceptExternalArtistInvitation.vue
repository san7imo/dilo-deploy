<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
  state: {
    type: String,
    default: "invalid",
  },
  invitation: {
    type: Object,
    default: () => ({}),
  },
  token: {
    type: String,
    default: "",
  },
});

const form = useForm({
  name: "",
  stage_name: "",
  identification_type: "",
  identification_number: "",
  phone: "",
  additional_information: "",
  password: "",
  password_confirmation: "",
});

const submit = () => {
  form.post(route("external-artists.invitations.accept", props.token), {
    onFinish: () => {
      form.reset("password", "password_confirmation");
    },
  });
};
</script>

<template>
  <Head title="Invitación artista externo" />

  <div class="min-h-screen flex items-center justify-center bg-[#000000] text-white relative overflow-hidden px-4 sm:px-0">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,#1d1d1b_0%,#000000_100%)] opacity-95"></div>

    <div class="relative z-10 w-full max-w-xl p-6 sm:p-8 md:p-10 bg-[#1d1d1b]/90 backdrop-blur-md border border-[#2a2a2a] rounded-2xl shadow-2xl space-y-5">
      <h1 class="text-xl sm:text-2xl font-semibold text-[#ffa236]">Invitación Dilo Records</h1>

      <div v-if="state === 'invalid'" class="bg-red-900/30 border border-red-700 rounded-lg p-4 text-sm">
        Esta invitación no existe o ya no es válida.
      </div>

      <div v-else-if="state === 'expired'" class="bg-yellow-900/30 border border-yellow-700 rounded-lg p-4 text-sm">
        Esta invitación venció o fue revocada.
      </div>

      <div v-else-if="state === 'accepted'" class="bg-green-900/30 border border-green-700 rounded-lg p-4 text-sm">
        Esta invitación ya fue utilizada.
      </div>

      <form v-else @submit.prevent="submit" class="space-y-4">
        <div class="text-sm text-gray-300">
          <p><strong>Correo:</strong> {{ invitation.email }}</p>
          <p v-if="invitation.track_title"><strong>Canción asociada:</strong> {{ invitation.track_title }}</p>
        </div>

        <div>
          <label class="text-gray-300 text-sm">Nombre legal</label>
          <input v-model="form.name" type="text" class="input" />
          <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
        </div>

        <div>
          <label class="text-gray-300 text-sm">Nombre artístico</label>
          <input v-model="form.stage_name" type="text" class="input" />
          <p v-if="form.errors.stage_name" class="text-red-400 text-xs mt-1">{{ form.errors.stage_name }}</p>
        </div>

        <div>
          <label class="text-gray-300 text-sm">Tipo de documento</label>
          <select v-model="form.identification_type" class="input">
            <option value="">Selecciona tipo</option>
            <option value="passport">Pasaporte</option>
            <option value="national_id">Documento nacional / ID nacional</option>
            <option value="residence_permit">Permiso de residencia</option>
            <option value="tax_id">Identificación fiscal (Tax ID)</option>
            <option value="driver_license">Licencia de conducción</option>
            <option value="other">Otro</option>
          </select>
          <p v-if="form.errors.identification_type" class="text-red-400 text-xs mt-1">
            {{ form.errors.identification_type }}
          </p>
        </div>

        <div>
          <label class="text-gray-300 text-sm">Número de identificación</label>
          <input v-model="form.identification_number" type="text" class="input" />
          <p v-if="form.errors.identification_number" class="text-red-400 text-xs mt-1">{{ form.errors.identification_number }}</p>
        </div>

        <div>
          <label class="text-gray-300 text-sm">Teléfono (opcional)</label>
          <input v-model="form.phone" type="text" class="input" />
          <p v-if="form.errors.phone" class="text-red-400 text-xs mt-1">{{ form.errors.phone }}</p>
        </div>

        <div>
          <label class="text-gray-300 text-sm">Información adicional (opcional)</label>
          <textarea
            v-model="form.additional_information"
            class="input"
            rows="3"
            placeholder="Comparte datos relevantes para pagos o gestión administrativa."
          />
          <p v-if="form.errors.additional_information" class="text-red-400 text-xs mt-1">
            {{ form.errors.additional_information }}
          </p>
        </div>

        <div>
          <label class="text-gray-300 text-sm">Contraseña</label>
          <input v-model="form.password" type="password" class="input" />
          <p v-if="form.errors.password" class="text-red-400 text-xs mt-1">{{ form.errors.password }}</p>
        </div>

        <div>
          <label class="text-gray-300 text-sm">Confirmar contraseña</label>
          <input v-model="form.password_confirmation" type="password" class="input" />
        </div>

        <p v-if="form.errors.invitation" class="text-red-400 text-xs">{{ form.errors.invitation }}</p>
        <p v-if="form.errors.email" class="text-red-400 text-xs">{{ form.errors.email }}</p>

        <button
          type="submit"
          class="w-full py-2.5 bg-[#ffa236] text-black font-semibold rounded-lg hover:bg-[#ffb54d] transition disabled:opacity-50"
          :disabled="form.processing"
        >
          Crear cuenta
        </button>
      </form>

      <Link :href="route('login')" class="text-sm text-[#ffa236] hover:text-[#ffb54d] transition-colors">
        Ir a iniciar sesión
      </Link>
    </div>
  </div>
</template>

<style scoped>
.input {
  @apply mt-2 block w-full bg-[#111111] border border-[#2c2c2c] text-gray-100 focus:ring-[#ffa236] focus:border-[#ffa236] rounded-lg px-3 py-2;
}
</style>

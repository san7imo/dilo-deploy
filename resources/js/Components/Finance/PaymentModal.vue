<script setup>
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    show: { type: Boolean, default: false },
    form: { type: Object, required: true },
    paymentMethodOptions: { type: Array, default: () => [] },
    normalizeCurrency: { type: Function, default: null },
});

const emit = defineEmits(["close", "submit"]);

const onBlurCurrency = () => {
    if (typeof props.normalizeCurrency === "function") {
        props.normalizeCurrency();
    }
};
</script>

<template>
    <Modal :show="show" @close="emit('close')">
        <div class="p-6 space-y-5 text-white">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">Registrar pago</h2>
                    <p class="text-sm text-gray-400">Registra un pago y se convertirá a EUR según la tasa.</p>
                </div>
                <button type="button" class="text-gray-300 hover:text-white" @click="emit('close')">
                    Cerrar
                </button>
            </div>

            <form @submit.prevent="emit('submit')" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-gray-300 text-sm">Fecha</label>
                    <input v-model="form.payment_date" type="date" class="fin-input" />
                    <p v-if="form.errors.payment_date" class="text-red-500 text-sm mt-1">
                        {{ form.errors.payment_date }}
                    </p>
                </div>

                <div>
                    <label class="text-gray-300 text-sm">Monto</label>
                    <input v-model="form.amount_original" type="number" step="0.01" class="fin-input" />
                    <p v-if="form.errors.amount_original" class="text-red-500 text-sm mt-1">
                        {{ form.errors.amount_original }}
                    </p>
                </div>

                <div>
                    <label class="text-gray-300 text-sm">Moneda (ISO)</label>
                    <input v-model="form.currency" @blur="onBlurCurrency" type="text" class="fin-input"
                        placeholder="EUR / USD / COP..." />
                    <p class="text-gray-500 text-xs mt-1">Ej: EUR, USD, COP (3 letras).</p>
                </div>

                <div v-if="form.currency !== 'EUR'">
                    <label class="text-gray-300 text-sm">Tasa a EUR</label>
                    <input v-model="form.exchange_rate_to_base" type="number" step="0.000001" class="fin-input" />
                    <p class="text-gray-500 text-xs mt-1">Ej: si 1 USD = 0.92 EUR, pon 0.92</p>
                </div>

                <div v-else>
                    <label class="text-gray-300 text-sm">Tasa a EUR</label>
                    <input :value="1" disabled class="fin-input opacity-60" />
                    <p class="text-gray-500 text-xs mt-1">EUR no requiere tasa.</p>
                </div>

                <div>
                    <label class="text-gray-300 text-sm">Método de pago</label>
                    <select v-model="form.payment_method" class="fin-input">
                        <option value="" disabled>Selecciona un método</option>
                        <option v-for="opt in paymentMethodOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>

                    <p v-if="form.errors.payment_method" class="text-red-500 text-sm mt-1">
                        {{ form.errors.payment_method }}
                    </p>
                </div>

                <div class="flex items-center gap-2 mt-6">
                    <input v-model="form.is_advance" type="checkbox" class="fin-checkbox" />
                    <span class="text-gray-300 text-sm">¿Es anticipo?</span>
                </div>

                <div class="sm:col-span-2">
                    <label class="text-gray-300 text-sm">Notas</label>
                    <textarea v-model="form.notes" rows="3" class="fin-input"
                        placeholder="Notas adicionales sobre este pago..." />
                    <p v-if="form.errors.notes" class="text-red-500 text-sm mt-1">
                        {{ form.errors.notes }}
                    </p>
                </div>

                <div class="sm:col-span-2 flex justify-end gap-2 mt-2">
                    <button type="button" class="fin-btn-ghost" @click="emit('close')">
                        Cancelar
                    </button>
                    <button class="fin-btn-primary" type="submit" :disabled="form.processing">
                        Guardar pago
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

<style scoped>
.fin-input {
    @apply w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}

.fin-btn-primary {
    @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}

.fin-btn-ghost {
    @apply bg-transparent hover:bg-white/5 text-gray-200 font-semibold px-4 py-2 rounded-md transition-colors border border-[#2a2a2a];
}

.fin-checkbox {
    @apply w-4 h-4 rounded bg-[#0f0f0f] border border-[#2a2a2a] text-[#ffa236] focus:ring-[#ffa236];
}
</style>

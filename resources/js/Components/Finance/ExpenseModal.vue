<script setup>
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    show: { type: Boolean, default: false },
    form: { type: Object, required: true },
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
                    <h2 class="text-lg font-semibold">Registrar gasto</h2>
                    <p class="text-sm text-gray-400">Registra un gasto y se convertirá a USD según la tasa.</p>
                </div>
                <button type="button" class="text-gray-300 hover:text-white" @click="emit('close')">
                    Cerrar
                </button>
            </div>

            <form @submit.prevent="emit('submit')" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-gray-300 text-sm">Fecha</label>
                    <input v-model="form.expense_date" type="date" class="fin-input" />
                    <p v-if="form.errors.expense_date" class="text-red-500 text-sm mt-1">
                        {{ form.errors.expense_date }}
                    </p>
                </div>

                <div>
                    <label class="text-gray-300 text-sm">Nombre del gasto *</label>
                    <input v-model="form.name" type="text" class="fin-input" placeholder="Ej: Vuelo, Hotel..." />
                    <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <label class="text-gray-300 text-sm">Categoría</label>
                    <select v-model="form.category" class="fin-input">
                        <option value="">Selecciona una categoría</option>
                        <option value="transporte">Transporte</option>
                        <option value="hospedaje">Hospedaje</option>
                        <option value="alimentacion">Alimentación</option>
                        <option value="sonido">Sonido</option>
                        <option value="iluminacion">Iluminación</option>
                        <option value="seguridad">Seguridad</option>
                        <option value="marketing">Marketing</option>
                        <option value="fotografo">Fotógrafo</option>
                        <option value="filmmaker">Filmmaker</option>
                        <option value="visa">Visa</option>
                        <option value="ropa">Ropa</option>
                        <option value="impuestos">Impuestos</option>
                        <option value="otros">Otros</option>
                    </select>
                    <p v-if="form.errors.category" class="text-red-500 text-sm mt-1">
                        {{ form.errors.category }}
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
                        placeholder="USD / COP / MXN..." />
                    <p class="text-gray-500 text-xs mt-1">Ej: USD, COP, MXN (3 letras).</p>
                </div>

                <div v-if="form.currency !== 'USD'">
                    <label class="text-gray-300 text-sm">Tasa a USD</label>
                    <input v-model="form.exchange_rate_to_base" type="number" step="0.000001" class="fin-input" />
                    <p class="text-gray-500 text-xs mt-1">Ej: si 1 COP = 0.00025 USD, pon 0.00025</p>
                </div>

                <div v-else>
                    <label class="text-gray-300 text-sm">Tasa a USD</label>
                    <input :value="1" disabled class="fin-input opacity-60" />
                    <p class="text-gray-500 text-xs mt-1">USD no requiere tasa.</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="text-gray-300 text-sm">Descripción</label>
                    <textarea v-model="form.description" rows="3" class="fin-input"
                        placeholder="Detalles adicionales del gasto..." />
                    <p v-if="form.errors.description" class="text-red-500 text-sm mt-1">
                        {{ form.errors.description }}
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <label class="text-gray-300 text-sm">Comprobante (imagen)</label>
                    <input
                        type="file"
                        accept="image/*"
                        class="fin-input"
                        @change="(e) => { form.receipt_file = e.target.files[0]; }"
                    />
                    <p v-if="form.errors.receipt_file" class="text-red-500 text-sm mt-1">
                        {{ form.errors.receipt_file }}
                    </p>
                </div>

                <div class="sm:col-span-2 flex justify-end gap-2 mt-2">
                    <button type="button" class="fin-btn-ghost" @click="emit('close')">
                        Cancelar
                    </button>
                    <button class="fin-btn-secondary" type="submit" :disabled="form.processing">
                        Guardar gasto
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

.fin-btn-secondary {
    @apply bg-[#2a2a2a] hover:bg-[#333333] text-white font-semibold px-4 py-2 rounded-md transition-colors border border-[#3a3a3a];
}

.fin-btn-ghost {
    @apply bg-transparent hover:bg-white/5 text-gray-200 font-semibold px-4 py-2 rounded-md transition-colors border border-[#2a2a2a];
}
</style>

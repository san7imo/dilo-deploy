<script setup>
import { computed } from "vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    show: { type: Boolean, default: false },
    form: { type: Object, required: true },
    artists: { type: Array, default: () => [] },
    normalizeCurrency: { type: Function, default: null },
    event: { type: Object, default: null },
});

const emit = defineEmits(["close", "submit"]);

const categories = [
    { value: "alimentacion", label: "Alimentación" },
    { value: "transporte", label: "Transporte personal" },
    { value: "alojamiento", label: "Alojamiento personal" },
    { value: "recreacion", label: "Recreación" },
    { value: "otros", label: "Otros" },
];

const onBlurCurrency = () => {
    if (typeof props.normalizeCurrency === "function") {
        props.normalizeCurrency();
    }
};

const selectedArtist = computed(() => {
    if (!props.form.artist_id || !props.artists) return null;
    return props.artists.find((a) => a.id === props.form.artist_id);
});

// Formatear fecha para input type="date"
const formattedExpenseDate = computed({
    get() {
        if (!props.form.expense_date) return "";
        if (typeof props.form.expense_date === "string") {
            return props.form.expense_date.split("T")[0];
        }
      
        if (props.form.expense_date instanceof Date) {
            return props.form.expense_date.toISOString().split("T")[0];
        }
        return "";
    },
    set(value) {
        props.form.expense_date = value;
    },
});
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="3xl">
        <div class="p-6 space-y-5 text-white bg-[#0f0f0d]">
            <div class="flex items-center justify-between gap-4 border-b border-[#2a2a2a] pb-4">
                <div>
                    <h2 class="text-xl font-semibold text-[#ffa236]">Registrar gasto personal del artista</h2>
                    <p class="text-sm text-gray-400 mt-1">
                        Este gasto se descontará del 70% que le corresponde al artista.
                    </p>
                </div>
                <button type="button" class="text-gray-400 hover:text-white transition-colors" @click="emit('close')">
                    ✕
                </button>
            </div>

            <div v-if="event" class="bg-[#1d1d1b] border border-[#ffa236]/20 rounded-lg p-4">
                <p class="text-sm text-[#ffa236] font-semibold">Evento: {{ event.title }}</p>
                <p class="text-xs text-gray-400 mt-1">
                    Registra gastos personales del artista (alimentación, transporte personal, recreación, etc.)
                </p>
            </div>

            <form @submit.prevent="emit('submit')" class="space-y-4">
                <!-- Selección de artista -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-[#ffa236]">
                        Artista *
                    </label>
                    <select v-model.number="form.artist_id"
                        class="w-full bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg px-4 py-2 text-white text-sm focus:border-[#ffa236] focus:ring-1 focus:ring-[#ffa236] transition-colors"
                        required>
                        <option value="">Seleccionar artista</option>
                        <option v-for="artist in artists" :key="artist.id" :value="artist.id">
                            {{ artist.name }}
                        </option>
                    </select>
                    <p v-if="selectedArtist" class="text-xs text-gray-500 mt-1">
                        Este gasto se descontará del 70% que le corresponde a {{ selectedArtist.name }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Fecha del gasto *</label>
                        <input v-model="formattedExpenseDate" type="date"
                            class="w-full bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg px-4 py-2 text-white text-sm focus:border-[#ffa236] focus:ring-1 focus:ring-[#ffa236]"
                            required />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Categoría *</label>
                        <select v-model="form.category"
                            class="w-full bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg px-4 py-2 text-white text-sm focus:border-[#ffa236] focus:ring-1 focus:ring-[#ffa236]"
                            required>
                            <option value="">Seleccionar categoría</option>
                            <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                {{ cat.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Nombre del gasto *</label>
                    <input v-model="form.name" type="text"
                        class="w-full bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg px-4 py-2 text-white text-sm focus:border-[#ffa236] focus:ring-1 focus:ring-[#ffa236]"
                        placeholder="Ej: Cena en restaurante" required />
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Descripción</label>
                    <textarea v-model="form.description" rows="2"
                        class="w-full bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg px-4 py-2 text-white text-sm focus:border-[#ffa236] focus:ring-1 focus:ring-[#ffa236] resize-none"
                        placeholder="Detalles adicionales del gasto..."></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Monto *</label>
                        <input v-model.number="form.amount_original" type="number" step="0.01" min="0"
                            class="w-full bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg px-4 py-2 text-white text-sm focus:border-[#ffa236] focus:ring-1 focus:ring-[#ffa236]"
                            placeholder="0.00" required />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Moneda *</label>
                        <input v-model="form.currency" type="text" maxlength="3"
                            class="w-full bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg px-4 py-2 text-white text-sm uppercase focus:border-[#ffa236] focus:ring-1 focus:ring-[#ffa236]"
                            placeholder="USD" @blur="onBlurCurrency" required />
                    </div>

                    <div v-if="form.currency && form.currency.toUpperCase() !== 'USD'">
                        <label class="block text-sm font-semibold mb-2">Tasa de cambio</label>
                        <input v-model.number="form.exchange_rate_to_base" type="number" step="0.0001" min="0"
                            class="w-full bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg px-4 py-2 text-white text-sm focus:border-[#ffa236] focus:ring-1 focus:ring-[#ffa236]"
                            placeholder="1.0000" />
                        <p class="text-xs text-gray-500 mt-1">{{ form.currency }} a USD</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Comprobante (imagen)</label>
                    <input type="file" accept="image/jpeg,image/jpg,image/png,image/webp"
                        class="w-full bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg px-4 py-2 text-white text-sm file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:bg-[#ffa236] file:text-black hover:file:bg-[#ffb54d] file:cursor-pointer"
                        @change="(e) => (form.receipt_file = e.target.files[0])" />
                    <p class="text-xs text-gray-500 mt-1">JPG, PNG o WebP (máx. 4MB)</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Notas adicionales</label>
                    <textarea v-model="form.notes" rows="2"
                        class="w-full bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg px-4 py-2 text-white text-sm focus:border-[#ffa236] focus:ring-1 focus:ring-[#ffa236] resize-none"
                        placeholder="Observaciones adicionales..."></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-[#2a2a2a]">
                    <button type="button"
                        class="px-5 py-2 text-sm text-gray-300 hover:text-white border border-[#2a2a2a] rounded-lg hover:bg-[#1d1d1b] transition-colors"
                        @click="emit('close')">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-5 py-2 text-sm bg-[#ffa236] text-black font-semibold rounded-lg hover:bg-[#ffb54d] transition-colors">
                        Registrar gasto
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

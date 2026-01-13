<script setup>
import { Icon } from "@iconify/vue";
import { formatDateES } from "@/utils/date";

const props = defineProps({
    event: { type: Object, required: true },
    totals: { type: Object, required: true },
    statusBadge: { type: Object, required: true },
    statusForm: { type: Object, required: true },
    updatePaymentStatus: { type: Function, required: true },
    updateEventDetails: { type: Function, required: true },
    canSubmitPaymentStatus: { type: Boolean, default: true },
    paidStatusAlert: { type: [String, Boolean], default: "" },
    eventMetaForm: { type: Object, required: true },
    eventStatusBadge: { type: Object, required: true },
    eventStatusValue: { type: String, default: "" },
    canSubmitEventDetails: { type: Boolean, default: true },
    roadManagers: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-5 gap-4">
            <div class="card">
                <p class="text-gray-400 text-sm">Total pagado</p>
                <p class="text-white text-xl font-semibold">$ {{ totals.paid.toFixed(2) }}</p>
                <p class="text-gray-500 text-xs mt-1">Suma de pagos (convertidos a USD).</p>
            </div>
            <div class="card">
                <p class="text-gray-400 text-sm">Total gastos</p>
                <p class="text-white text-xl font-semibold">$ {{ totals.expenses.toFixed(2) }}</p>
                <p class="text-gray-500 text-xs mt-1">Suma de gastos (convertidos a USD).</p>
            </div>
            <div class="card">
                <p class="text-gray-400 text-sm">Neto</p>
                <p class="text-white text-xl font-semibold">$ {{ totals.net.toFixed(2) }}</p>
                <p class="text-gray-500 text-xs mt-1">Pagos − gastos.</p>
            </div>
            <div class="card">
                <p class="text-gray-400 text-sm">70% Artista</p>
                <p class="text-[#ffa236] text-xl font-semibold">$ {{ totals.shareArtist.toFixed(2) }}</p>
                <p class="text-gray-500 text-xs mt-1">Antes de gastos personales.</p>
            </div>
            <div class="card">
                <p class="text-gray-400 text-sm">Pago neto artista</p>
                <p class="text-green-400 text-xl font-semibold">$ {{ totals.shareArtistNet.toFixed(2) }}</p>
                <p class="text-gray-500 text-xs mt-1">Descontando gastos personales.</p>
            </div>
        </div>

        <div class="bg-[#ffa236]/5 border border-[#ffa236]/30 rounded-lg p-4 flex gap-3">
            <Icon icon="mdi:information-outline" class="text-[#ffa236] text-xl flex-shrink-0 mt-0.5" />
            <div>
                <p class="text-sm text-[#ffa236] font-semibold mb-1">Gastos personales del artista</p>
                <p class="text-sm text-gray-300">
                    Total: <span class="font-semibold text-[#ffa236]">${{ totals.artistPersonalExpenses.toFixed(2)
                        }}</span>
                </p>
                <p class="text-xs text-gray-400 mt-2">Se descuentan del 70% que le corresponde al artista. No afectan el
                    30% de la compañía.</p>
            </div>
        </div>

        <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h2 class="text-lg font-semibold">Estado de pago</h2>
                    <p class="text-sm text-gray-400">Marca si el evento se considera pagado o pendiente.</p>
                </div>
                <span :class="['px-3 py-1 rounded-full text-xs font-semibold', statusBadge.className]">{{
                    statusBadge.label }}</span>
            </div>
            <form @submit.prevent="updatePaymentStatus" class="flex flex-col sm:flex-row sm:items-center gap-4">
                <label class="flex items-center gap-2 text-sm text-gray-300">
                    <input v-model="statusForm.is_paid" type="checkbox" class="checkbox"
                        :disabled="!canSubmitPaymentStatus && !statusForm.is_paid" />
                    Marcar como pagado
                </label>
                <div class="flex-1"></div>
                <button type="submit" class="btn-primary self-start sm:self-auto"
                    :disabled="statusForm.processing || !canSubmitPaymentStatus">Guardar estado</button>
            </form>
            <p v-if="paidStatusAlert" class="text-amber-300 text-sm mt-3">
                {{ paidStatusAlert }}
            </p>
            <p v-if="statusForm.errors.is_paid" class="text-red-500 text-sm mt-2">
                {{ statusForm.errors.is_paid }}
            </p>
        </div>

        <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold">Estado y fechas</h2>
                    <p class="text-sm text-gray-400">Actualiza el estado del evento y fechas clave.</p>
                </div>
                <span :class="['px-3 py-1 rounded-full text-xs font-semibold border', eventStatusBadge.className]">
                    {{ eventStatusBadge.label }}
                </span>
            </div>
            <form @submit.prevent="updateEventDetails" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="text-gray-300 text-sm">Estado</label>
                    <select v-model="eventMetaForm.status" class="input">
                        <option value="">Selecciona un estado</option>
                        <option value="cotizado">Cotizado</option>
                        <option value="reservado">Reservado</option>
                        <option value="confirmado">Confirmado</option>
                        <option value="pospuesto">Pospuesto</option>
                        <option value="pagado" :disabled="eventStatusValue === 'pagado' && !canSubmitEventDetails">
                            Pagado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                    <p v-if="eventMetaForm.errors.status" class="text-red-500 text-sm mt-1">
                        {{ eventMetaForm.errors.status }}
                    </p>
                    <p v-else-if="eventStatusValue === 'pagado' && paidStatusAlert" class="text-amber-300 text-xs mt-1">
                        {{ paidStatusAlert }}
                    </p>
                </div>
                <div>
                    <label class="text-gray-300 text-sm">Fecha del evento</label>
                    <input v-model="eventMetaForm.event_date" type="date" class="input" />
                    <p v-if="eventMetaForm.errors.event_date" class="text-red-500 text-sm mt-1">
                        {{ eventMetaForm.errors.event_date }}
                    </p>
                </div>
                <div>
                    <label class="text-gray-300 text-sm">Fecha pago final</label>
                    <input v-model="eventMetaForm.full_payment_due_date" type="date" class="input" />
                    <p v-if="eventMetaForm.errors.full_payment_due_date" class="text-red-500 text-sm mt-1">
                        {{ eventMetaForm.errors.full_payment_due_date }}
                    </p>
                </div>
                <div class="sm:col-span-3 flex justify-end">
                    <button type="submit" class="btn-primary"
                        :disabled="eventMetaForm.processing || !canSubmitEventDetails">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>

        <div v-if="roadManagers.length" class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Confirmacion road managers</h2>
            <div class="space-y-3 text-sm">
                <div v-for="rm in roadManagers" :key="rm.id"
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-[#2a2a2a] pb-2">
                    <div>
                        <p class="text-white font-semibold">{{ rm.name }}</p>
                        <p class="text-gray-400 text-xs">{{ rm.email }}</p>
                    </div>
                    <div class="text-sm">
                        <span v-if="rm.pivot?.payment_confirmed_at" class="text-green-400 font-semibold">
                            Confirmado
                            <span class="block text-xs text-gray-400">
                                {{ formatDateES(rm.pivot.payment_confirmed_at) }}
                            </span>
                        </span>
                        <span v-else class="text-yellow-400 font-semibold">Pendiente</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Datos del evento</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                <div class="mini-card">
                    <p class="text-gray-400">Ubicación</p>
                    <p class="text-white font-semibold">
                        <span v-if="event.city || event.country">
                            {{ event.city }}{{ event.city && event.country ? "," : "" }} {{ event.country }}
                        </span>
                        <span v-else>{{ event.location || "—" }}</span>
                    </p>
                    <p v-if="event.location" class="text-gray-400 text-xs mt-1">{{ event.location }}</p>
                    <p v-if="event.venue_address" class="text-gray-500 text-xs mt-1">{{ event.venue_address }}</p>
                </div>
                <div class="mini-card">
                    <p class="text-gray-400">Tipo</p>
                    <p class="text-white font-semibold capitalize">{{ event.event_type || "—" }}</p>
                    <p class="text-gray-400 mt-2">Estado</p>
                    <span
                        :class="['inline-flex px-2 py-1 rounded-full text-xs font-semibold border mt-1', eventStatusBadge.className]">
                        {{ eventStatusBadge.label }}
                    </span>
                </div>
                <div class="mini-card">
                    <p class="text-gray-400">Fee del show</p>
                    <p class="text-white font-semibold">
                        <span>{{ event.currency || "USD" }}</span>
                        <span class="ml-1">{{ Number(event.show_fee_total ?? 0).toFixed(2) }}</span>
                    </p>
                    <p class="text-gray-400 mt-2">Moneda</p>
                    <p class="text-white font-semibold">{{ event.currency || "USD" }}</p>
                </div>
                <div class="mini-card">
                    <p class="text-gray-400">% Anticipo</p>
                    <p class="text-white font-semibold">{{ event.advance_percentage ?? 50 }}%</p>
                    <p class="text-gray-400 mt-2">¿Se espera anticipo?</p>
                    <p class="text-white font-semibold">{{ event.advance_expected ? "Sí" : "No" }}</p>
                </div>
                <div class="mini-card">
                    <p class="text-gray-400">Fecha pago final</p>
                    <p class="text-white font-semibold">{{ event.full_payment_due_date ?
                        formatDateES(event.full_payment_due_date) : "—" }}</p>
                </div>
                <div class="mini-card">
                    <p class="text-gray-400">Reparto (neto)</p>
                    <div class="mt-1 space-y-1">
                        <p class="text-white font-semibold">Label: $ {{ totals.shareLabel.toFixed(2) }}</p>
                        <p class="text-white font-semibold">Artista: $ {{ totals.shareArtist.toFixed(2) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="postcss">
.input {
    @apply w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}

.btn-primary {
    @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}

.checkbox {
    @apply w-4 h-4 rounded bg-[#0f0f0f] border border-[#2a2a2a] text-[#ffa236] focus:ring-[#ffa236];
}

.card {
    @apply bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-5;
}

.mini-card {
    @apply bg-[#111111] border border-[#2a2a2a] rounded-md p-3;
}
</style>

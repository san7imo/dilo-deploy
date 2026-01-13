<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import FinanceCharts from "@/Components/Finance/FinanceCharts.vue";
import PaymentModal from "@/Components/Finance/PaymentModal.vue";
import ExpenseModal from "@/Components/Finance/ExpenseModal.vue";
import PaymentsTable from "@/Components/Finance/PaymentsTable.vue";
import ExpensesTable from "@/Components/Finance/ExpensesTable.vue";
import AnalysisFilters from "@/Components/Finance/AnalysisFilters.vue";
import ArtistExpensesTable from "@/Components/Finance/ArtistExpensesTable.vue";
import ArtistExpenseModal from "@/Components/Finance/ArtistExpenseModal.vue";
import FinanceSummary from "@/Components/Finance/FinanceSummary.vue";
import useFinancePage from "@/composables/useFinancePage";
import { formatDateES } from "@/utils/date";

const props = defineProps({
    event: { type: Object, required: true },
    finance: { type: Object, default: () => ({}) },
});
const {
    roleNames,
    isAdmin,
    isRoadManager,
    activeTab,
    showPaymentModal,
    showExpenseModal,
    showArtistExpenseModal,
    filterType,
    filterYear,
    filterMonth,
    filterDateFrom,
    filterDateTo,
    paymentMethodOptions,
    paymentForm,
    expenseForm,
    artistExpenseForm,
    statusForm,
    eventMetaForm,
    confirmForm,
    roadManagers,
    availableArtists,
    currentRoadManager,
    roadManagerConfirmedAt,
    roadManagerDue,
    totals,
    paidStatusAlert,
    eventStatusValue,
    eventStatusBadge,
    statusAlert,
    eventArray,
    statusBadge,
    canSubmitPaymentStatus,
    canSubmitEventDetails,
    normalizePaymentCurrencyAndRate,
    normalizeExpenseCurrencyAndRate,
    normalizeArtistExpenseCurrencyAndRate,
    openPaymentModal,
    openExpenseModal,
    openArtistExpenseModal,
    submitPayment,
    deletePayment,
    submitExpense,
    deleteExpense,
    updatePaymentStatus,
    updateEventDetails,
    confirmRoadManagerPayment,
    submitArtistExpense,
    approveArtistExpense,
    deleteArtistExpense,
} = useFinancePage(props);
</script>

<template>
    <AdminLayout title="Finanzas">
        <div class="space-y-6 text-white">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold">Finanzas — {{ event.title }}</h1>
                    <div class="text-gray-400 text-sm mt-2 space-y-1">
                        <p>
                            Artista principal:
                            <span class="text-white">
                                {{ event.main_artist?.name || event.mainArtist?.name || "—" }}
                            </span>
                        </p>
                        <p>
                            Ubicación:
                            <span class="text-white">
                                <span v-if="event.city || event.country">
                                    {{ event.city }}{{ event.city && event.country ? "," : "" }}
                                    {{ event.country }}
                                    <span v-if="event.location" class="block text-gray-400 text-xs">{{ event.location
                                    }}</span>
                                    <span v-if="event.venue_address" class="block text-gray-500 text-xs">{{
                                        event.venue_address }}</span>
                                </span>
                                <span v-else>
                                    <span class="text-white">{{ event.location || "—" }}</span>
                                    <span v-if="event.venue_address" class="block text-gray-500 text-xs">{{
                                        event.venue_address }}</span>
                                </span>
                            </span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.events.index')" class="text-[#ffa236] hover:underline">
                        ← Volver
                    </Link>
                </div>
            </div>

            <div v-if="isRoadManager" class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">Pago pendiente al road manager</h2>
                        <p class="text-sm text-gray-400">
                            Corresponde al porcentaje restante despues del adelanto.
                        </p>
                        <p class="text-2xl font-semibold text-[#ffa236] mt-2">
                            {{ event.currency || "USD" }} {{ roadManagerDue.toFixed(2) }}
                        </p>
                    </div>
                    <div class="text-sm text-gray-300">
                        <button v-if="!roadManagerConfirmedAt" type="button" class="btn-primary"
                            :disabled="confirmForm.processing" @click="confirmRoadManagerPayment">
                            Confirmar pago recibido
                        </button>
                        <div v-else class="text-green-400 font-semibold">
                            Pago confirmado
                            <span v-if="roadManagerConfirmedAt" class="block text-xs text-gray-400 mt-1">
                                {{ formatDateES(roadManagerConfirmedAt) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="statusAlert" :class="['rounded-lg p-4 text-sm', statusAlert.className]">
                <p class="font-semibold">{{ statusAlert.title }}</p>
                <p class="opacity-90 mt-1">{{ statusAlert.message }}</p>
            </div>

            <div class="flex flex-wrap gap-2 border-b border-[#2a2a2a] pb-3">
                <button v-if="isAdmin" type="button" class="tab-btn"
                    :class="activeTab === 'resumen' ? 'tab-btn--active' : 'tab-btn--idle'"
                    @click="activeTab = 'resumen'">
                    Resumen
                </button>
                <button type="button" class="tab-btn"
                    :class="activeTab === 'pagos' ? 'tab-btn--active' : 'tab-btn--idle'" @click="activeTab = 'pagos'">
                    Pagos
                </button>
                <button type="button" class="tab-btn"
                    :class="activeTab === 'gastos' ? 'tab-btn--active' : 'tab-btn--idle'" @click="activeTab = 'gastos'">
                    Gastos del evento
                </button>
                <button type="button" class="tab-btn"
                    :class="activeTab === 'gastos-artista' ? 'tab-btn--active' : 'tab-btn--idle'"
                    @click="activeTab = 'gastos-artista'">
                    Gastos del artista
                </button>
                <button v-if="isAdmin" type="button" class="tab-btn"
                    :class="activeTab === 'analisis' ? 'tab-btn--active' : 'tab-btn--idle'"
                    @click="activeTab = 'analisis'">
                    Análisis
                </button>
                <div class="flex-1"></div>
                <div class="flex gap-2">
                    <button type="button" class="btn-primary" @click="openPaymentModal">+ Nuevo pago</button>
                    <button type="button" class="btn-secondary" @click="openExpenseModal">+ Nuevo gasto</button>
                    <button type="button" class="btn-secondary" @click="openArtistExpenseModal">+ Gasto del
                        artista</button>
                </div>
            </div>

            <FinanceSummary v-if="isAdmin && activeTab === 'resumen'" :event="event" :totals="totals"
                :status-badge="statusBadge" :status-form="statusForm" :update-payment-status="updatePaymentStatus"
                :can-submit-payment-status="canSubmitPaymentStatus" :paid-status-alert="paidStatusAlert"
                :event-meta-form="eventMetaForm" :update-event-details="updateEventDetails"
                :event-status-badge="eventStatusBadge" :event-status-value="eventStatusValue"
                :can-submit-event-details="canSubmitEventDetails" :road-managers="roadManagers" />

            <div v-else-if="activeTab === 'pagos'" class="space-y-4">
                <div
                    class="flex flex-wrap items-center justify-between gap-3 bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-5">
                    <div>
                        <h2 class="text-lg font-semibold">Pagos</h2>
                        <p class="text-sm text-gray-400">Total: $ {{ totals.paid.toFixed(2) }}</p>
                    </div>
                    <button type="button" class="btn-primary" @click="openPaymentModal">+ Nuevo pago</button>
                </div>
                <PaymentsTable :payments="event.payments || []" :can-delete="isAdmin" @delete="deletePayment" />
            </div>

            <div v-else-if="activeTab === 'gastos'" class="space-y-4">
                <div
                    class="flex flex-wrap items-center justify-between gap-3 bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-5">
                    <div>
                        <h2 class="text-lg font-semibold">Gastos del evento</h2>
                        <p class="text-sm text-gray-400">Total: $ {{ totals.expenses.toFixed(2) }}</p>
                    </div>
                    <button type="button" class="btn-secondary" @click="openExpenseModal">+ Nuevo gasto</button>
                </div>
                <ExpensesTable :expenses="event.expenses || []" :can-delete="isAdmin" @delete="deleteExpense" />
            </div>

            <div v-else-if="activeTab === 'gastos-artista'" class="space-y-4">
                <div
                    class="flex flex-wrap items-center justify-between gap-3 bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-5">
                    <div>
                        <h2 class="text-lg font-semibold">Gastos personales del artista</h2>
                        <p class="text-sm text-gray-400">
                            Total: $ {{ totals.artistPersonalExpenses.toFixed(2) }}
                            (se descuenta del 70% del artista)
                        </p>
                    </div>
                    <button type="button" class="btn-secondary" @click="openArtistExpenseModal">+ Nuevo gasto
                        personal</button>
                </div>

                <div
                    class="bg-gradient-to-r from-[#ffa236]/10 to-transparent border border-[#ffa236]/30 rounded-lg p-5 flex gap-4">
                    <Icon icon="mdi:lightbulb-outline" class="text-[#ffa236] text-2xl flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="text-sm font-semibold text-[#ffa236] mb-2">Política de gastos personales</p>
                        <p class="text-sm text-gray-300 leading-relaxed">
                            Los gastos personales del artista (alimentación, transporte, recreación, etc.) se descuentan
                            únicamente del 70% que le corresponde al artista.
                        </p>
                        <p class="text-xs text-gray-400 mt-2 italic">El 30% destinado a la compañía no se ve afectado
                            por estos gastos.</p>
                    </div>
                </div>

                <ArtistExpensesTable :expenses="event.artist_personal_expenses || []" :can-approve="isAdmin"
                    :can-delete="isAdmin || isRoadManager" @approve="approveArtistExpense"
                    @delete="deleteArtistExpense" />
            </div>

            <div v-else-if="isAdmin" class="space-y-6">
                <AnalysisFilters v-model:filter-type="filterType" v-model:filter-date-from="filterDateFrom"
                    v-model:filter-date-to="filterDateTo" v-model:filter-year="filterYear"
                    v-model:filter-month="filterMonth" />
                <FinanceCharts :totals="totals" :events="eventArray" :filter-type="filterType" :filter-year="filterYear"
                    :filter-month="filterMonth" :filter-date-from="filterDateFrom" :filter-date-to="filterDateTo"
                    currency="$" />
            </div>

            <PaymentModal :show="showPaymentModal" :form="paymentForm" :payment-method-options="paymentMethodOptions"
                :normalize-currency="normalizePaymentCurrencyAndRate" @close="showPaymentModal = false"
                @submit="submitPayment" />
            <ExpenseModal :show="showExpenseModal" :form="expenseForm"
                :normalize-currency="normalizeExpenseCurrencyAndRate" @close="showExpenseModal = false"
                @submit="submitExpense" />
            <ArtistExpenseModal :show="showArtistExpenseModal" :form="artistExpenseForm" :artists="availableArtists"
                :event="event" :normalize-currency="normalizeArtistExpenseCurrencyAndRate"
                @close="showArtistExpenseModal = false" @submit="submitArtistExpense" />
        </div>
    </AdminLayout>
</template>

<style scoped lang="postcss">
.input {
    @apply w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}

.btn-primary {
    @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}

.btn-secondary {
    @apply bg-[#2a2a2a] hover:bg-[#333333] text-white font-semibold px-4 py-2 rounded-md transition-colors border border-[#3a3a3a];
}

.btn-ghost {
    @apply bg-transparent hover:bg-white/5 text-gray-200 font-semibold px-4 py-2 rounded-md transition-colors border border-[#2a2a2a];
}

.checkbox {
    @apply w-4 h-4 rounded bg-[#0f0f0f] border border-[#2a2a2a] text-[#ffa236] focus:ring-[#ffa236];
}

.tab-btn {
    @apply px-3 py-2 rounded-md text-sm transition-colors border;
}

.tab-btn--active {
    @apply bg-[#ffa236] text-black font-semibold border-[#ffa236];
}

.tab-btn--idle {
    @apply bg-[#1d1d1b] text-gray-200 border-[#2a2a2a] hover:bg-[#222222];
}
</style>

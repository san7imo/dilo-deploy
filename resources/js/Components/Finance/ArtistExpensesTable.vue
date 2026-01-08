<script setup>
import { computed } from "vue";
import { Icon } from "@iconify/vue";

const props = defineProps({
    expenses: { type: Array, default: () => [] },
    canApprove: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
});

const emit = defineEmits(["delete", "approve", "edit"]);

const formatCurrency = (amount, currency = "USD") => {
    const val = Number(amount ?? 0);
    return `${currency} ${val.toFixed(2)}`;
};

const formatDate = (date) => {
    if (!date) return "—";
    return new Date(date).toLocaleDateString("es-ES", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const getCategoryBadgeClass = (category) => {
    const classes = {
        alimentacion: "bg-orange-500/20 text-orange-300 border-orange-500/40",
        transporte: "bg-blue-500/20 text-blue-300 border-blue-500/40",
        alojamiento: "bg-purple-500/20 text-purple-300 border-purple-500/40",
        recreacion: "bg-pink-500/20 text-pink-300 border-pink-500/40",
        otros: "bg-gray-500/20 text-gray-300 border-gray-500/40",
    };
    return classes[category?.toLowerCase()] || classes["otros"];
};
</script>

<template>
    <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
        <div v-if="!expenses || expenses.length === 0" class="text-gray-400 text-center py-4">
            No hay gastos personales del artista registrados.
        </div>

        <div v-else class="overflow-x-auto">
            <table class="min-w-full text-sm text-gray-300">
                <thead class="text-xs uppercase text-gray-400 border-b border-[#2a2a2a]">
                    <tr>
                        <th class="py-3 px-2 text-left">Fecha</th>
                        <th class="py-3 px-2 text-left">Nombre</th>
                        <th class="py-3 px-2 text-left">Categoría</th>
                        <th class="py-3 px-2 text-left">Monto original</th>
                        <th class="py-3 px-2 text-left">En USD</th>
                        <th class="py-3 px-2 text-left">Estado</th>
                        <th class="py-3 px-2 text-left">Comprobante</th>
                        <th class="py-3 px-2 text-left">Registrado por</th>
                        <th v-if="canApprove || canEdit || canDelete" class="py-3 px-2 text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="expense in expenses" :key="expense.id"
                        class="border-b border-[#2a2a2a] hover:bg-[#232321] transition-colors">
                        <td class="py-3 px-2 whitespace-nowrap">{{ formatDate(expense.expense_date) }}</td>

                        <td class="py-3 px-2">
                            <p class="font-semibold">{{ expense.name || "—" }}</p>
                            <p v-if="expense.description" class="text-xs text-gray-500 mt-1">
                                {{ expense.description }}
                            </p>
                        </td>

                        <td class="py-3 px-2 whitespace-nowrap">
                            <span v-if="expense.category"
                                :class="['px-2 py-1 rounded text-xs capitalize border', getCategoryBadgeClass(expense.category)]">
                                {{ expense.category }}
                            </span>
                            <span v-else class="text-gray-500">—</span>
                        </td>

                        <td class="py-3 px-2 whitespace-nowrap">
                            {{ formatCurrency(expense.amount_original, expense.currency) }}
                        </td>

                        <td class="py-3 px-2 whitespace-nowrap font-semibold">
                            {{ formatCurrency(expense.amount_base, "USD") }}
                        </td>

                        <td class="py-3 px-2 whitespace-nowrap">
                            <div v-if="expense.is_approved"
                                class="px-2 py-1 rounded text-xs bg-green-500/20 text-green-300 border border-green-500/40 flex items-center gap-1 w-fit">
                                <Icon icon="mdi:check-circle" class="text-sm" />
                                <span>Aprobado</span>
                            </div>
                            <div v-else
                                class="px-2 py-1 rounded text-xs bg-yellow-500/20 text-yellow-300 border border-yellow-500/40 flex items-center gap-1 w-fit">
                                <Icon icon="mdi:clock-outline" class="text-sm" />
                                <span>Pendiente</span>
                            </div>
                        </td>

                        <td class="py-3 px-2 whitespace-nowrap">
                            <a v-if="expense.receipt_url" :href="expense.receipt_url" target="_blank"
                                rel="noopener noreferrer" class="text-[#ffa236] hover:underline text-xs">
                                Ver comprobante
                            </a>
                            <span v-else class="text-gray-500">—</span>
                        </td>

                        <td class="py-3 px-2 text-xs text-gray-400">
                            {{ expense.created_by || "—" }}
                        </td>

                        <td v-if="canApprove || canEdit || canDelete" class="py-3 px-2 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button v-if="canApprove && !expense.is_approved" type="button"
                                    class="text-green-400 hover:underline text-xs" @click="emit('approve', expense.id)">
                                    Aprobar
                                </button>
                                <button v-if="canDelete" type="button" class="text-red-400 hover:underline text-xs"
                                    @click="emit('delete', expense.id)">
                                    Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="expenses && expenses.length > 0" class="mt-4 pt-4 border-t border-[#2a2a2a]">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-400">Total de gastos personales del artista:</span>
                <span class="text-[#ffa236] font-bold text-lg">
                    {{formatCurrency(expenses.filter(e => e.is_approved).reduce((sum, e) => sum + Number(e.amount_base
                        || 0), 0), "USD")}}
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                Solo los gastos aprobados se descuentan del 70% que le corresponde al artista.
            </p>
        </div>
    </div>
</template>

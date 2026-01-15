<script setup>
import { formatDateES } from "@/utils/date";
import { formatMoney, formatMoneyWithSymbol } from "@/utils/money";

defineProps({
    expenses: { type: Array, default: () => [] },
    canDelete: { type: Boolean, default: true },
});

const emit = defineEmits(["delete"]);
</script>

<template>
    <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
        <div v-if="!expenses || expenses.length === 0" class="text-gray-400">
            No hay gastos registrados.
        </div>

        <div v-else class="overflow-x-auto">
            <table class="min-w-full text-sm text-gray-300">
                <thead class="text-xs uppercase text-gray-400">
                    <tr>
                        <th class="py-2 text-left">Fecha</th>
                        <th class="py-2 text-left">Nombre</th>
                        <th class="py-2 text-left">Categoría</th>
                        <th class="py-2 text-left">Original</th>
                        <th class="py-2 text-left">USD</th>
                        <th class="py-2 text-left">Descripción</th>
                        <th class="py-2 text-left">Comprobante</th>
                        <th v-if="canDelete" class="py-2 text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="g in expenses" :key="g.id" class="border-t border-[#2a2a2a]">
                        <td class="py-2 whitespace-nowrap">{{ formatDateES(g.expense_date) }}</td>

                        <td class="py-2 font-semibold whitespace-nowrap">
                            {{ g.name || "—" }}
                        </td>

                        <td class="py-2 whitespace-nowrap">
                            <span v-if="g.category"
                                class="bg-[#2a2a2a] text-gray-300 px-2 py-1 rounded text-xs capitalize">
                                {{ g.category }}
                            </span>
                            <span v-else class="text-gray-500">—</span>
                        </td>

                        <td class="py-2 whitespace-nowrap">
                            {{ formatMoney(g.amount_original, g.currency) }}
                        </td>

                        <td class="py-2 whitespace-nowrap">
                            {{ formatMoneyWithSymbol(g.amount_base) }}
                        </td>

                        <td class="py-2 max-w-[280px]">
                            <span class="text-gray-300">
                                {{ (g.description || "").trim() || "—" }}
                            </span>
                        </td>

                        <td class="py-2 whitespace-nowrap">
                            <a
                                v-if="g.receipt_url"
                                :href="g.receipt_url"
                                target="_blank"
                                rel="noopener"
                                class="text-[#ffa236] hover:underline"
                            >
                                Ver
                            </a>
                            <span v-else class="text-gray-500">—</span>
                        </td>

                        <td v-if="canDelete" class="py-2 text-right whitespace-nowrap">
                            <button @click="emit('delete', g.id)" class="text-red-400 hover:underline">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

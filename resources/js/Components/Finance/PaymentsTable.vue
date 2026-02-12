<script setup>
import { formatDateES } from "@/utils/date";
import { formatMoney, formatMoneyWithSymbol } from "@/utils/money";

defineProps({
    payments: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: true },
    showCollaborator: { type: Boolean, default: false },
    showCreator: { type: Boolean, default: false },
});

const emit = defineEmits(["edit", "delete"]);

const isRoadManagerEntry = (payment) => {
    return !!payment?.creator?.roles?.some((role) => role?.name === "roadmanager");
};
</script>

<template>
    <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
        <div v-if="!payments || payments.length === 0" class="text-gray-400">
            No hay ingresos todavía.
        </div>

        <div v-else class="overflow-x-auto">
            <table class="min-w-full text-sm text-gray-300">
                <thead class="text-xs uppercase text-gray-400">
                    <tr>
                        <th class="py-2 text-left">Fecha</th>
                        <th class="py-2 text-left">Original</th>
                        <th class="py-2 text-left">USD</th>
                        <th class="py-2 text-left">Método</th>
                        <th v-if="showCollaborator" class="py-2 text-left">Recibe</th>
                        <th v-if="showCreator" class="py-2 text-left">Registrado por</th>
                        <th class="py-2 text-left">Anticipo</th>
                        <th class="py-2 text-left">Notas</th>
                        <th class="py-2 text-left">Comprobante</th>
                        <th v-if="canEdit || canDelete" class="py-2 text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="p in payments" :key="p.id" class="border-t border-[#2a2a2a]">
                        <td class="py-2 whitespace-nowrap">{{ formatDateES(p.payment_date) }}</td>

                        <td class="py-2 whitespace-nowrap">
                            {{ formatMoney(p.amount_original, p.currency) }}
                        </td>

                        <td class="py-2 whitespace-nowrap">
                            {{ formatMoneyWithSymbol(p.amount_base) }}
                        </td>

                        <td class="py-2 whitespace-nowrap capitalize">
                            {{ p.payment_method || "—" }}
                        </td>

                        <td v-if="showCollaborator" class="py-2 whitespace-nowrap">
                            {{ p.collaborator?.account_holder || "—" }}
                        </td>

                        <td v-if="showCreator" class="py-2 whitespace-nowrap">
                            <span
                                v-if="p.creator && isRoadManagerEntry(p)"
                                class="bg-blue-500/15 text-blue-300 px-2 py-1 rounded text-xs border border-blue-500/30"
                            >
                                Road manager
                            </span>
                            <span v-else-if="p.creator" class="text-gray-300">Admin</span>
                            <span v-else class="text-gray-500">—</span>
                        </td>

                        <td class="py-2 whitespace-nowrap">
                            <span :class="[
                                'px-2 py-1 rounded text-xs',
                                p.is_advance
                                    ? 'bg-[#ffa236]/15 text-[#ffa236] border border-[#ffa236]/30'
                                    : 'bg-[#2a2a2a] text-gray-300'
                            ]">
                                {{ p.is_advance ? "Sí" : "No" }}
                            </span>
                        </td>

                        <td class="py-2 max-w-[280px]">
                            <span class="text-gray-300">
                                {{ (p.notes || "").trim() || "—" }}
                            </span>
                        </td>

                        <td class="py-2 whitespace-nowrap">
                            <a
                                v-if="p.receipt_url"
                                :href="p.receipt_url"
                                target="_blank"
                                rel="noopener"
                                class="text-[#ffa236] hover:underline"
                            >
                                Ver
                            </a>
                            <span v-else class="text-gray-500">—</span>
                        </td>

                        <td v-if="canEdit || canDelete" class="py-2 text-right whitespace-nowrap">
                            <button v-if="canEdit" @click="emit('edit', p)"
                                class="text-[#ffa236] hover:underline mr-3">
                                Editar
                            </button>
                            <button v-if="canDelete" @click="emit('delete', p.id)" class="text-red-400 hover:underline">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

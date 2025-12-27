<script setup>
import { formatDateES } from "@/utils/date";

defineProps({
    payments: { type: Array, default: () => [] },
});

const emit = defineEmits(["delete"]);
</script>

<template>
    <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
        <div v-if="!payments || payments.length === 0" class="text-gray-400">
            No hay pagos todavía.
        </div>

        <div v-else class="overflow-x-auto">
            <table class="min-w-full text-sm text-gray-300">
                <thead class="text-xs uppercase text-gray-400">
                    <tr>
                        <th class="py-2 text-left">Fecha</th>
                        <th class="py-2 text-left">Original</th>
                        <th class="py-2 text-left">EUR</th>
                        <th class="py-2 text-left">Método</th>
                        <th class="py-2 text-left">Anticipo</th>
                        <th class="py-2 text-left">Notas</th>
                        <th class="py-2 text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="p in payments" :key="p.id" class="border-t border-[#2a2a2a]">
                        <td class="py-2 whitespace-nowrap">{{ formatDateES(p.payment_date) }}</td>

                        <td class="py-2 whitespace-nowrap">
                            {{ p.currency }} {{ Number(p.amount_original ?? 0).toFixed(2) }}
                        </td>

                        <td class="py-2 whitespace-nowrap">
                            € {{ Number(p.amount_base ?? 0).toFixed(2) }}
                        </td>

                        <td class="py-2 whitespace-nowrap capitalize">
                            {{ p.payment_method || "—" }}
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

                        <td class="py-2 text-right whitespace-nowrap">
                            <button @click="emit('delete', p.id)" class="text-red-400 hover:underline">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

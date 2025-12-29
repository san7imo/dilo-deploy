<script setup>
import { computed } from "vue";

const props = defineProps({
    filterType: { type: String, default: "all" },
    filterYear: { type: [String, Number, null], default: null },
    filterMonth: { type: [String, Number, null], default: null },
    filterDateFrom: { type: [String, null], default: null },
    filterDateTo: { type: [String, null], default: null },
});

const emit = defineEmits([
    "update:filterType",
    "update:filterYear",
    "update:filterMonth",
    "update:filterDateFrom",
    "update:filterDateTo",
]);

const filterTypeModel = computed({
    get: () => props.filterType,
    set: (val) => emit("update:filterType", val),
});

const filterDateFromModel = computed({
    get: () => props.filterDateFrom,
    set: (val) => emit("update:filterDateFrom", val),
});

const filterDateToModel = computed({
    get: () => props.filterDateTo,
    set: (val) => emit("update:filterDateTo", val),
});
</script>

<template>
    <div
        class="bg-gradient-to-br from-[#1d1d1b] to-[#151512] border border-[#3a3a38] rounded-xl p-6 flex flex-wrap gap-4 shadow-lg">
        <div class="flex-1 min-w-[200px]">
            <label class="text-[#ffa236] text-sm mb-2 font-semibold">
                Filtrar por estado
            </label>
            <select v-model="filterTypeModel"
                class="w-full bg-[#0f0f0d] border border-[#3a3a38] rounded-lg px-3 py-2 text-white text-sm hover:border-[#ffa236]/30 focus:border-[#ffa236] transition-colors">
                <option value="all">Todos</option>
                <option value="paid">Solo pagados</option>
                <option value="pending">Solo pendientes</option>
            </select>
        </div>

        <div class="flex-1 min-w-[200px]">
            <label class="text-[#ffa236] text-sm mb-2 font-semibold">Desde</label>
            <input v-model="filterDateFromModel" type="date"
                class="w-full bg-[#0f0f0d] border border-[#3a3a38] rounded-lg px-3 py-2 text-white text-sm hover:border-[#ffa236]/30 focus:border-[#ffa236] transition-colors" />
        </div>

        <div class="flex-1 min-w-[200px]">
            <label class="text-[#ffa236] text-sm mb-2 font-semibold">Hasta</label>
            <input v-model="filterDateToModel" type="date"
                class="w-full bg-[#0f0f0d] border border-[#3a3a38] rounded-lg px-3 py-2 text-white text-sm hover:border-[#ffa236]/30 focus:border-[#ffa236] transition-colors" />
        </div>
    </div>
</template>

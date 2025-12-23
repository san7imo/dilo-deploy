<script setup>
import ArtistLayout from '@/Layouts/ArtistLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    event: { type: Object, required: true },
    finance: { type: Object, default: () => ({}) },
});

const formatDate = (date) => {
    if (!date) return '—';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatShortDate = (date) => {
    if (!date) return '—';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('es-ES');
};

const formatTime = (date) => {
    if (!date) return '—';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatCurrency = (value, currency = 'EUR') => {
    const number = Number(value ?? 0);
    if (Number.isNaN(number)) return `${currency} 0.00`;
    // Use Intl.NumberFormat for locale-aware formatting. This will show currency symbol for common codes.
    try {
        return new Intl.NumberFormat('es-ES', { style: 'currency', currency }).format(number);
    } catch (e) {
        // Fallback to a simple formatted string if Intl doesn't support the currency code
        return `${currency} ${number.toFixed(2)}`;
    }
};

const isPaid = () => {
    if (props.event && typeof props.event.is_paid !== "undefined") {
        return !!props.event.is_paid;
    }
    return Number(props.finance?.total_paid_base ?? 0) > 0;
};
</script>

<template>
    <ArtistLayout>
        <div class="max-w-5xl mx-auto space-y-8">
            <Link :href="route('artist.events.index')"
                class="inline-flex items-center gap-2 text-[#ffa236] hover:text-[#ffb54d] mb-2 transition-colors">
                ← Volver a mis eventos
            </Link>

            <div
                class="rounded-2xl overflow-hidden border border-[#232323] bg-gradient-to-br from-[#161616] via-[#0f0f0f] to-[#0b0b0b] shadow-xl">
                <div class="p-8 space-y-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="space-y-2">
                            <p class="text-[11px] uppercase tracking-[0.2em] text-gray-500">{{
                                formatDate(event.event_date) }}</p>
                            <h1 class="text-3xl font-bold text-white leading-tight">{{ event.title }}</h1>
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span :class="[
                                    'px-3 py-1 rounded-full border text-[11px] font-semibold capitalize',
                                    isPaid() ? 'bg-green-500/15 text-green-200 border-green-500/30' : 'bg-amber-500/15 text-amber-200 border-amber-500/30'
                                ]">
                                    {{ isPaid() ? 'Pagado' : 'Pendiente' }}
                                </span>
                                <span v-if="event.event_type"
                                    class="px-3 py-1 rounded-full border border-[#2c2c2c] bg-[#111111] text-gray-300 capitalize">
                                    {{ event.event_type }}
                                </span>
                            </div>
                            <div class="space-y-1 text-sm text-gray-300">
                                <p v-if="event.city || event.country" class="text-gray-300">
                                    {{ event.city }}{{ event.city && event.country ? ',' : '' }} {{ event.country }}
                                </p>
                                <p v-if="event.location" class="text-gray-200">{{ event.location }}</p>
                                <p v-if="event.venue_address" class="text-xs text-gray-500">{{ event.venue_address }}
                                </p>
                                <p v-else-if="!event.city && !event.country && event.location" class="text-gray-300">
                                    {{ event.location }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="rounded-2xl border border-[#2c2c2c] bg-[#0d0d0d] px-4 py-3 text-right text-sm text-gray-300">
                            <p class="text-xs text-gray-500">Hora</p>
                            <p class="text-white font-semibold">{{ formatTime(event.event_date) }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="rounded-xl border border-[#242424] bg-[#101010] p-4">
                            <p class="text-gray-500 text-xs">Total pagado</p>
                            <p class="text-white text-xl font-semibold">{{ formatCurrency(finance.total_paid_base) }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-[#242424] bg-[#101010] p-4">
                            <p class="text-gray-500 text-xs">Anticipo pagado</p>
                            <p class="text-white text-xl font-semibold">{{ formatCurrency(finance.advance_paid_base) }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-[#242424] bg-[#101010] p-4">
                            <p class="text-gray-500 text-xs">Gastos</p>
                            <p class="text-red-400 text-xl font-semibold">{{ formatCurrency(finance.total_expenses_base)
                            }}</p>
                        </div>
                        <div class="rounded-xl border border-[#242424] bg-[#101010] p-4">
                            <p class="text-gray-500 text-xs">Resultado neto</p>
                            <p class="text-white text-xl font-semibold">{{ formatCurrency(finance.net_base) }}</p>
                        </div>
                        <div class="rounded-xl border border-[#242424] bg-[#101010] p-4 sm:col-span-2">
                            <p class="text-gray-500 text-xs">30% Dilo</p>
                            <p class="text-gray-100 text-xl font-semibold">{{
                                formatCurrency(finance.label_share_estimated_base) }}</p>
                        </div>
                        <div class="rounded-xl border border-[#242424] bg-[#101010] p-4 sm:col-span-2">
                            <p class="text-gray-500 text-xs">70% Artista</p>
                            <p class="text-[#ffa236] text-xl font-semibold">{{
                                formatCurrency(finance.artist_share_estimated_base) }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="rounded-xl border border-[#242424] bg-[#0f0f0f] p-4">
                                <p class="text-xs text-gray-500">Fecha</p>
                                <p class="text-white font-medium">{{ formatDate(event.event_date) }}</p>
                                <p class="text-gray-400 text-sm">{{ formatTime(event.event_date) }}</p>
                            </div>

                            <div v-if="event.location || event.city || event.country"
                                class="rounded-xl border border-[#242424] bg-[#0f0f0f] p-4 space-y-1">
                                <p class="text-xs text-gray-500">Ubicación</p>
                                <p v-if="event.city || event.country" class="text-white font-medium">
                                    {{ event.city }}{{ event.city && event.country ? ',' : '' }} {{ event.country }}
                                </p>
                                <p v-if="event.location" class="text-gray-300">{{ event.location }}</p>
                            </div>

                            <div v-if="event.venue_address" class="rounded-xl border border-[#242424] bg-[#0f0f0f] p-4">
                                <p class="text-xs text-gray-500">Venue</p>
                                <p class="text-white font-medium">{{ event.venue_address }}</p>
                            </div>

                            <div v-if="event.description" class="rounded-xl border border-[#242424] bg-[#0f0f0f] p-4">
                                <p class="text-xs text-gray-500 mb-1">Descripción</p>
                                <p class="text-white whitespace-pre-line">{{ event.description }}</p>
                            </div>

                            <div v-if="event.show_fee_total"
                                class="rounded-xl border border-[#242424] bg-[#0f0f0f] p-4 space-y-1">
                                <p class="text-xs text-gray-500">Fee del show</p>
                                <p class="text-white font-medium">{{ formatCurrency(event.show_fee_total, event.currency || 'EUR') }}</p>
                                <p v-if="event.advance_percentage" class="text-xs text-gray-500">Anticipo: {{
                                    event.advance_percentage }}%</p>
                            </div>
                        </div>

                        <div v-if="event.artists && event.artists.length"
                            class="rounded-xl border border-[#242424] bg-[#0f0f0f] p-4 space-y-3">
                            <p class="text-sm text-gray-400">Artistas participantes</p>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="artist in event.artists" :key="artist.id"
                                    class="px-3 py-1 bg-[#1b1b1b] border border-[#2a2a2a] rounded-full text-sm text-white">
                                    {{ artist.name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="event.payments && event.payments.length" class="space-y-3">
                        <h3 class="text-lg font-semibold text-white">Pagos registrados</h3>
                        <div class="rounded-xl border border-[#242424] bg-[#0f0f0f] overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="border-b border-[#2a2a2a] bg-[#0c0c0c]">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-gray-400">Fecha</th>
                                        <th class="px-4 py-3 text-left text-gray-400">Monto</th>
                                        <th class="px-4 py-3 text-left text-gray-400">Anticipo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="payment in event.payments" :key="payment.id"
                                        class="border-t border-[#1f1f1f]">
                                        <td class="px-4 py-3">{{ formatShortDate(payment.payment_date) }}</td>
                                        <td class="px-4 py-3">{{ formatCurrency(payment.amount_original, payment.currency || 'EUR') }}</td>
                                        <td class="px-4 py-3">
                                            <span v-if="payment.is_advance" class="text-green-400">Sí</span>
                                            <span v-else class="text-gray-500">—</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="event.expenses && event.expenses.length" class="space-y-3">
                        <h3 class="text-lg font-semibold text-white">Gastos del evento</h3>
                        <div class="rounded-xl border border-[#242424] bg-[#0f0f0f] overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="border-b border-[#2a2a2a] bg-[#0c0c0c]">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-gray-400">Concepto</th>
                                        <th class="px-4 py-3 text-left text-gray-400">Categoría</th>
                                        <th class="px-4 py-3 text-left text-gray-400">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="expense in event.expenses" :key="expense.id"
                                        class="border-t border-[#1f1f1f]">
                                        <td class="px-4 py-3 font-medium">{{ expense.name || expense.description || '—'
                                        }}</td>
                                        <td class="px-4 py-3">
                                            <span v-if="expense.category"
                                                class="bg-[#1b1b1b] text-gray-300 px-2 py-1 rounded text-xs border border-[#2a2a2a]">
                                                {{ expense.category }}
                                            </span>
                                            <span v-else class="text-gray-500">—</span>
                                        </td>
                                        <td class="px-4 py-3 text-red-400">{{ formatCurrency(expense.amount_original, expense.currency || 'EUR') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ArtistLayout>
</template>

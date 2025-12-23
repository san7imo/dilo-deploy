<script setup>
import { Head } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import PublicLayout from '@/Layouts/PublicLayout.vue'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    event: { type: Object, required: true },
})

// Estado para contador regresivo
const countdown = ref({
    days: 0,
    hours: 0,
    minutes: 0,
    seconds: 0,
})

const formatDate = (date) => {
    if (!date) return 'Próximamente'
    return new Intl.DateTimeFormat('es-ES', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(date))
}

const formatDateShort = (date) => {
    if (!date) return '—'
    return new Intl.DateTimeFormat('es-ES', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date))
}

// Calcular tiempo restante
const updateCountdown = () => {
    if (!props.event.event_date) return

    const eventTime = new Date(props.event.event_date).getTime()
    const now = new Date().getTime()
    const diff = eventTime - now

    if (diff <= 0) {
        countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 }
        return
    }

    countdown.value.days = Math.floor(diff / (1000 * 60 * 60 * 24))
    countdown.value.hours = Math.floor((diff / (1000 * 60 * 60)) % 24)
    countdown.value.minutes = Math.floor((diff / 1000 / 60) % 60)
    countdown.value.seconds = Math.floor((diff / 1000) % 60)
}

// Determinar si el evento ya pasó
const eventPassed = computed(() => {
    if (!props.event.event_date) return false
    return new Date(props.event.event_date) < new Date()
})

// Ejecutar contador cada segundo
let countdownInterval = null

onMounted(() => {
    updateCountdown()
    countdownInterval = setInterval(updateCountdown, 1000)
})

onUnmounted(() => {
    if (countdownInterval) {
        clearInterval(countdownInterval)
    }
})

// WhatsApp link
const whatsappMessage = encodeURIComponent(
    `Hola, me interesa el evento "${props.event.title}". ¿Me brindas más información?`
)
const whatsappLink = `https://wa.me/?text=${whatsappMessage}`

const handleShare = () => {
    if (navigator.share) {
        navigator.share({
            title: props.event.title,
            text: props.event.description,
            url: window.location.href
        })
    } else {
        navigator.clipboard.writeText(window.location.href)
    }
}
</script>

<template>
    <div class="bg-black text-white min-h-screen">

        <Head :title="`${event.title} — Dilo Records`" />

        <!-- Hero con poster -->
        <div class="relative h-[50vh] md:h-[60vh] overflow-hidden">
            <!-- Poster de fondo -->
            <img v-if="event.poster_url" :src="event.poster_url" :alt="event.title"
                class="absolute inset-0 w-full h-full object-cover" />
            <div v-else class="absolute inset-0 bg-gradient-to-br from-zinc-800 to-black" />

            <!-- Overlay oscuro -->
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" />

            <!-- Contenido sobre hero -->
            <div class="relative h-full flex items-end pb-10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                    <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                        {{ event.title }}
                    </h1>

                    <!-- Info rápida en hero -->
                    <div class="flex flex-wrap gap-6 text-sm md:text-base items-center">
                        <div v-if="event.event_date" class="flex items-center gap-2">
                            <Icon icon="mdi:calendar" class="text-gray-400 text-lg" />
                            <span>{{ formatDate(event.event_date) }}</span>
                        </div>
                        <div v-if="event.city || event.country" class="flex items-center gap-2">
                            <Icon icon="mdi:map-marker" class="text-gray-400 text-lg" />
                            <span>
                                {{ event.city }}{{ event.city && event.country ? ',' : '' }}
                                {{ event.country }}
                            </span>
                        </div>

                        <!-- Contador regresivo en hero -->
                        <div v-if="!eventPassed" class="flex items-center gap-3 ml-auto">
                            <span class="text-gray-400 text-xs uppercase">Comienza en:</span>
                            <div class="flex gap-1">
                                <div class="bg-orange-500/20 rounded px-2 py-1 text-center border border-orange-500/30">
                                    <p class="font-bold text-orange-400 text-sm">{{ countdown.days }}</p>
                                    <p class="text-[10px] text-gray-400">dias</p>
                                </div>
                                <div class="bg-orange-500/20 rounded px-2 py-1 text-center border border-orange-500/30">
                                    <p class="font-bold text-orange-400 text-sm">{{ countdown.hours }}</p>
                                    <p class="text-[10px] text-gray-400">horas</p>
                                </div>
                                <div class="bg-orange-500/20 rounded px-2 py-1 text-center border border-orange-500/30">
                                    <p class="font-bold text-orange-400 text-sm">{{ countdown.minutes }}</p>
                                    <p class="text-[10px] text-gray-400">minutos</p>
                                </div>
                                <div class="bg-orange-500/20 rounded px-2 py-1 text-center border border-orange-500/30">
                                    <p class="font-bold text-orange-400 text-sm">{{ countdown.seconds }}</p>
                                    <p class="text-[10px] text-gray-400">segundos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- Columna principal -->
                <div class="lg:col-span-2 space-y-12">
                    <!-- Descripción -->
                    <section v-if="event.description" class="space-y-4">
                        <h2 class="text-2xl font-bold">Acerca del evento</h2>
                        <p class="text-gray-300 leading-relaxed whitespace-pre-line">
                            {{ event.description }}
                        </p>
                    </section>

                    <!-- Ubicación detallada -->
                    <section v-if="event.location || event.venue_address || event.city || event.country"
                        class="space-y-4">
                        <h2 class="text-2xl font-bold">Ubicación</h2>
                        <div class="bg-zinc-900/50 rounded-2xl p-6 ring-1 ring-white/10 space-y-4">
                            <div v-if="event.city || event.country">
                                <p class="text-gray-400 text-sm uppercase tracking-wide">Ciudad / País</p>
                                <p class="text-lg font-medium">
                                    {{ event.city }}{{ event.city && event.country ? ',' : '' }}
                                    {{ event.country }}
                                </p>
                            </div>
                            <div v-if="event.location">
                                <p class="text-gray-400 text-sm uppercase tracking-wide">Lugar</p>
                                <p class="text-lg font-medium">{{ event.location }}</p>
                            </div>
                            <div v-if="event.venue_address">
                                <p class="text-gray-400 text-sm uppercase tracking-wide">Dirección</p>
                                <p class="text-lg font-medium">{{ event.venue_address }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Artistas participantes -->
                    <section v-if="event.artists && event.artists.length > 0" class="space-y-4">
                        <h2 class="text-2xl font-bold">Artistas participantes</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <a v-for="artist in event.artists" :key="artist.id"
                                :href="route('public.artists.show', artist.slug)"
                                class="group bg-gradient-to-br from-zinc-900 to-black rounded-2xl p-6 ring-1 ring-white/10 hover:ring-white/20 hover:shadow-xl transition">
                                <div class="flex items-center gap-4">
                                    <div v-if="artist.image_url"
                                        class="w-16 h-16 rounded-full flex-shrink-0 overflow-hidden">
                                        <img :src="artist.image_url" :alt="artist.name"
                                            class="w-full h-full object-cover group-hover:scale-105 transition" />
                                    </div>
                                    <div v-else
                                        class="w-16 h-16 rounded-full bg-zinc-800 flex items-center justify-center flex-shrink-0">
                                        <Icon icon="mdi:microphone" class="text-white text-2xl" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p
                                            class="font-semibold text-white group-hover:text-[#ffa236] transition truncate">
                                            {{ artist.name }}
                                        </p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </section>
                </div>

                <!-- Sidebar derecha -->
                <aside class="lg:col-span-1">
                    <div class="space-y-2">
                        <!-- Card de información rápida -->
                        <div class="bg-zinc-900/50 rounded-2xl p-6 ring-1 ring-white/10 space-y-4">
                            <h3 class="font-semibold text-lg">Detalles del evento</h3>

                            <div>
                                <p class="text-gray-400 text-sm uppercase tracking-wide mb-2 flex items-center gap-2">
                                    <Icon icon="mdi:calendar" class="text-lg" />
                                    Fecha
                                </p>
                                <p class="text-white font-semibold">
                                    {{ formatDate(event.event_date) }}
                                </p>
                            </div>
                        </div>

                        <!-- CTA Contactar -->
                        <a :href="whatsappLink" target="_blank"
                            class="flex items-center justify-center gap-2 w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-4 rounded-xl transition">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" class="w-5 h-5 fill-white">
                                <path
                                    d="M16 0C7.163 0 0 7.163 0 16c0 2.837.744 5.62 2.156 8.063L0 32l8.25-2.094A15.94 15.94 0 0016 32c8.837 0 16-7.163 16-16S24.837 0 16 0zm0 29.091a13.05 13.05 0 01-6.658-1.833l-.478-.283-4.9 1.244 1.309-4.783-.31-.493A13.032 13.032 0 012.91 16C2.91 8.767 8.767 2.91 16 2.91S29.09 8.767 29.09 16 23.233 29.091 16 29.091zm7.545-9.964c-.41-.205-2.425-1.195-2.8-1.332-.375-.137-.65-.205-.923.205-.273.41-1.06 1.332-1.298 1.606-.24.273-.478.308-.888.102-.41-.205-1.732-.637-3.297-2.03-1.217-1.085-2.04-2.424-2.28-2.833-.24-.41-.025-.63.18-.835.185-.185.41-.478.615-.717.205-.24.273-.41.41-.683.137-.273.068-.512-.034-.717-.102-.205-.923-2.224-1.265-3.04-.334-.8-.673-.692-.923-.705-.24-.012-.512-.015-.785-.015-.273 0-.717.102-1.093.512-.375.41-1.435 1.4-1.435 3.42 0 2.02 1.47 3.97 1.675 4.243.205.273 2.893 4.417 7.013 6.195.98.422 1.745.674 2.342.863.984.312 1.88.268 2.59.163.79-.117 2.425-.995 2.768-1.955.342-.96.342-1.783.24-1.955-.103-.17-.376-.273-.786-.478z" />
                            </svg>
                            Contactar por WhatsApp
                        </a>

                        <!-- Compartir -->
                        <button @click="handleShare"
                            class="w-full flex items-center justify-center gap-2 bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-3 rounded-xl ring-1 ring-white/10 hover:ring-white/20 transition">
                            <Icon icon="mdi:share-variant" class="text-lg" />
                            Compartir
                        </button>

                        <!-- Back -->
                        <a href="/eventos"
                            class="w-full flex items-center justify-center gap-2 bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-3 rounded-xl ring-1 ring-white/10 hover:ring-white/20 transition">
                            <Icon icon="mdi:arrow-left" class="text-lg" />
                            Volver a eventos
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</template>

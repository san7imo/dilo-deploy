<script setup>
import { Head } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import ShareEventModal from '@/Components/Public/Events/ShareEventModal.vue'

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

const showShareModal = ref(false)
const shareUrl = computed(() => {
    if (typeof window !== 'undefined') return window.location.href
    if (typeof route === 'function' && props.event?.slug) {
        return route('public.events.show', props.event.slug)
    }
    return ''
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

const whatsappMessage = encodeURIComponent(
    `Hola, me interesa el evento "${props.event.title}". ¿Me brindas más información?`
)

const getWhatsAppLink = (value) => {
    if (!value) return ''
    const trimmed = String(value).trim()
    if (!trimmed) return ''
    if (/^https?:\/\//i.test(trimmed)) {
        return trimmed
    }
    const cleanNumber = trimmed.replace(/\D/g, '')
    if (!cleanNumber) return ''
    return `https://wa.me/${cleanNumber}?text=${whatsappMessage}`
}

const getTicketsLink = (value) => {
    if (!value) return ''
    const trimmed = String(value).trim()
    if (!trimmed) return ''
    if (/^https?:\/\//i.test(trimmed)) {
        return trimmed
    }
    return `https://${trimmed}`
}

const whatsappLink = computed(() => getWhatsAppLink(props.event.whatsapp_event))
const ticketsLink = computed(() => getTicketsLink(props.event.page_tickets))
const locationSearchText = computed(() => {
    return [props.event.venue_address, props.event.city, props.event.country, props.event.location]
        .map((part) => String(part || '').trim())
        .filter(Boolean)
        .join(', ')
})
const mapsLink = computed(() => {
    const manualUrl = getTicketsLink(props.event.google_maps_url)
    if (manualUrl) return manualUrl

    const hasCoords = props.event.latitude !== null && props.event.latitude !== '' &&
        props.event.longitude !== null && props.event.longitude !== ''
    if (hasCoords) {
        return `https://www.google.com/maps?q=${encodeURIComponent(props.event.latitude)},${encodeURIComponent(props.event.longitude)}`
    }

    const placeId = String(props.event.google_maps_place_id || '').trim()
    if (placeId) {
        const query = locationSearchText.value || 'Evento'
        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}&query_place_id=${encodeURIComponent(placeId)}`
    }

    if (locationSearchText.value) {
        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(locationSearchText.value)}`
    }

    return ''
})
const mapsEmbedUrl = computed(() => {
    const hasCoords = props.event.latitude !== null && props.event.latitude !== '' &&
        props.event.longitude !== null && props.event.longitude !== ''
    if (hasCoords) {
        return `https://www.google.com/maps?q=${encodeURIComponent(props.event.latitude)},${encodeURIComponent(props.event.longitude)}&z=15&output=embed`
    }

    const placeId = String(props.event.google_maps_place_id || '').trim()
    if (placeId) {
        return `https://www.google.com/maps?q=place_id:${encodeURIComponent(placeId)}&output=embed`
    }

    if (locationSearchText.value) {
        return `https://www.google.com/maps?q=${encodeURIComponent(locationSearchText.value)}&output=embed`
    }

    return ''
})
const organizerWebsiteLink = computed(() => getTicketsLink(props.event.organizer_website))
const organizerWhatsappLink = computed(() => getWhatsAppLink(props.event.organizer_whatsapp))
const organizerSocialLinks = computed(() => {
    const links = [
        { key: 'instagram', label: 'Instagram', value: props.event.organizer_instagram_url, icon: 'mdi:instagram' },
        { key: 'facebook', label: 'Facebook', value: props.event.organizer_facebook_url, icon: 'mdi:facebook' },
        { key: 'tiktok', label: 'TikTok', value: props.event.organizer_tiktok_url, icon: 'ic:baseline-tiktok' },
        { key: 'x', label: 'X', value: props.event.organizer_x_url, icon: 'ri:twitter-x-fill' },
    ]

    return links
        .map((item) => ({ ...item, href: getTicketsLink(item.value) }))
        .filter((item) => item.href)
})

const hasOrganizerInfo = computed(() => {
    return Boolean(
        props.event.organizer_company_name ||
        props.event.organizer_contact_name ||
        props.event.organizer_logo_url ||
        organizerWebsiteLink.value ||
        organizerWhatsappLink.value ||
        props.event.organizer_email ||
        organizerSocialLinks.value.length
    )
})
const sponsors = computed(() => {
    if (!Array.isArray(props.event.sponsors)) return []

    return props.event.sponsors
        .map((item, index) => ({
            key: `${String(item?.name || '').trim()}-${index}`,
            name: String(item?.name || '').trim(),
            image_url: String(item?.image_url || '').trim(),
        }))
        .filter((item) => item.name || item.image_url)
})

const handleShare = () => {
    showShareModal.value = true
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

                            <div v-if="mapsLink || mapsEmbedUrl" class="pt-2 space-y-3">
                                <a
                                    v-if="mapsLink"
                                    :href="mapsLink"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-2 rounded-lg bg-[#ffa236] hover:bg-[#ffb54d] px-4 py-2 text-sm font-semibold text-black transition"
                                >
                                    <Icon icon="mdi:map-marker-path" class="text-base" />
                                    Ver en Google Maps
                                </a>

                                <div v-if="mapsEmbedUrl" class="overflow-hidden rounded-xl ring-1 ring-white/15">
                                    <iframe
                                        :src="mapsEmbedUrl"
                                        width="100%"
                                        height="280"
                                        style="border:0;"
                                        loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade"
                                        title="Mapa del evento"
                                    ></iframe>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section v-if="hasOrganizerInfo" class="space-y-4">
                        <h2 class="text-2xl font-bold">Organizador</h2>
                        <div class="bg-zinc-900/50 rounded-2xl p-6 ring-1 ring-white/10 space-y-5">
                            <div class="flex items-center gap-4">
                                <img
                                    v-if="event.organizer_logo_url"
                                    :src="event.organizer_logo_url"
                                    :alt="event.organizer_company_name || 'Organizador'"
                                    class="w-14 h-14 rounded-xl object-cover ring-1 ring-white/20"
                                />
                                <div>
                                    <p v-if="event.organizer_company_name" class="text-lg font-semibold text-white">
                                        {{ event.organizer_company_name }}
                                    </p>
                                    <p v-if="event.organizer_contact_name" class="text-sm text-gray-300">
                                        {{ event.organizer_contact_name }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a
                                    v-if="organizerWebsiteLink"
                                    :href="organizerWebsiteLink"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-2 rounded-lg px-3 py-2 bg-zinc-800 hover:bg-zinc-700 text-sm text-white transition"
                                >
                                    <Icon icon="mdi:web" class="text-base" />
                                    Sitio web
                                </a>

                                <a
                                    v-if="event.organizer_email"
                                    :href="`mailto:${event.organizer_email}`"
                                    class="inline-flex items-center gap-2 rounded-lg px-3 py-2 bg-zinc-800 hover:bg-zinc-700 text-sm text-white transition"
                                >
                                    <Icon icon="mdi:email-outline" class="text-base" />
                                    Correo
                                </a>

                                <a
                                    v-if="organizerWhatsappLink"
                                    :href="organizerWhatsappLink"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-2 rounded-lg px-3 py-2 bg-green-500/20 hover:bg-green-500/30 text-sm text-green-300 transition"
                                >
                                    <Icon icon="ic:baseline-whatsapp" class="text-base" />
                                    WhatsApp
                                </a>

                                <a
                                    v-for="social in organizerSocialLinks"
                                    :key="social.key"
                                    :href="social.href"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-2 rounded-lg px-3 py-2 bg-zinc-800 hover:bg-zinc-700 text-sm text-white transition"
                                >
                                    <Icon :icon="social.icon" class="text-base" />
                                    {{ social.label }}
                                </a>
                            </div>
                        </div>
                    </section>

                    <section v-if="sponsors.length > 0" class="space-y-4">
                        <h2 class="text-2xl font-bold">Sponsors</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div
                                v-for="sponsor in sponsors"
                                :key="sponsor.key"
                                class="rounded-xl border border-white/10 bg-zinc-900/60 px-4 py-4 flex items-center gap-3"
                            >
                                <img
                                    v-if="sponsor.image_url"
                                    :src="sponsor.image_url"
                                    :alt="sponsor.name || 'Sponsor'"
                                    class="h-12 w-12 rounded-lg object-cover ring-1 ring-white/15"
                                />
                                <div
                                    v-else
                                    class="h-12 w-12 rounded-lg bg-zinc-800 flex items-center justify-center ring-1 ring-white/10"
                                >
                                    <Icon icon="mdi:image-outline" class="text-xl text-gray-400" />
                                </div>
                                <p class="font-medium text-white">{{ sponsor.name || 'Sponsor' }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Artistas participantes -->
                    <section v-if="event.artists && event.artists.length > 0" class="space-y-4">
                        <h2 class="text-2xl font-bold">Artistas participantes</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <component v-for="artist in event.artists" :key="artist.id"
                                :is="artist.has_public_profile ? 'a' : 'div'"
                                :href="artist.has_public_profile ? route('public.artists.show', artist.slug) : undefined"
                                class="group bg-gradient-to-br from-zinc-900 to-black rounded-2xl p-6 ring-1 ring-white/10 transition"
                                :class="artist.has_public_profile ? 'hover:ring-white/20 hover:shadow-xl' : ''">
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
                                            class="font-semibold text-white transition truncate"
                                            :class="artist.has_public_profile ? 'group-hover:text-[#ffa236]' : ''">
                                            {{ artist.name }}
                                        </p>
                                    </div>
                                </div>
                            </component>
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

                        <!-- CTA Tickets -->
                        <a v-if="ticketsLink" :href="ticketsLink" target="_blank" rel="noopener"
                            class="flex items-center justify-center gap-2 w-full bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold py-4 rounded-xl transition">
                            <Icon icon="mdi:ticket-confirmation-outline" class="text-lg" />
                            Comprar entradas
                        </a>

                        <!-- CTA WhatsApp -->
                        <a v-if="whatsappLink" :href="whatsappLink" target="_blank" rel="noopener"
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

        <ShareEventModal
            :event="event"
            :share-url="shareUrl"
            :is-open="showShareModal"
            @close="showShareModal = false"
        />
    </div>
</template>

<script setup>
import ArtistLayout from '@/Layouts/ArtistLayout.vue'
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { Link } from '@inertiajs/vue3'

defineOptions({ layout: ArtistLayout })

const artist = ref(null)
const releases = ref([])
const loading = ref(false)
const error = ref('')

onMounted(async () => {
        loading.value = true
        try {
                const { data } = await axios.get(route('artist.profile.data'))
                artist.value = data.artist
                releases.value = data.releases || []
        } catch (e) {
                error.value = 'No se pudo cargar el perfil. Intenta nuevamente.'
        } finally {
                loading.value = false
        }
})

const formatDate = (date) => {
        if (!date) return '-'
        return new Date(date).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })
}

// Normalize and map social links to icon + URL
const serviceTemplates = {
    instagram: 'https://instagram.com/{handle}',
    twitter: 'https://x.com/{handle}',
    x: 'https://x.com/{handle}',
    facebook: 'https://facebook.com/{handle}',
    youtube: 'https://youtube.com/{handle}',
    tiktok: 'https://www.tiktok.com/@{handle}',
    soundcloud: 'https://soundcloud.com/{handle}',
    spotify: '{url}',
    website: '{url}',
    bandcamp: 'https://{handle}.bandcamp.com',
    apple: '{url}',
}

const iconMap = {
    instagram: 'fa-brands fa-instagram',
    twitter: 'fa-brands fa-twitter',
    x: 'fa-brands fa-x-twitter',
    facebook: 'fa-brands fa-facebook',
    youtube: 'fa-brands fa-youtube',
    tiktok: 'fa-brands fa-tiktok',
    soundcloud: 'fa-brands fa-soundcloud',
    spotify: 'fa-brands fa-spotify',
    website: 'fa-solid fa-link',
    bandcamp: 'fa-brands fa-bandcamp',
    apple: 'fa-brands fa-apple'
}

function normalizeValue(key, raw) {
    if (!raw) return null
    let v = String(raw).trim()

    // If it's an @handle, use the template for handles
    if (v.startsWith('@')) {
        const handle = v.slice(1)
        const tpl = serviceTemplates[key] || 'https://{handle}.com/{handle}'
        return tpl.replace('{handle}', encodeURIComponent(handle)).replace('{url}', v)
    }

    // If looks like a plain username without dots and no protocol, treat as handle for some services
    if (!/^https?:\/\//i.test(v) && /^[a-zA-Z0-9_.-]+$/.test(v) && (key in serviceTemplates) && serviceTemplates[key].includes('{handle}')) {
        return serviceTemplates[key].replace('{handle}', encodeURIComponent(v))
    }

    // If it's missing protocol, add https
    if (!/^https?:\/\//i.test(v)) {
        return 'https://' + v
    }

    return v
}

const socialLinks = computed(() => {
    const obj = artist.value?.social_links_formatted || artist.value?.social_links || {}
    const out = []
    for (const [k, raw] of Object.entries(obj)) {
        const key = k.toLowerCase()
        const url = normalizeValue(key, raw)
        if (!url) continue
        const icon = iconMap[key] || 'fa-solid fa-link'
        out.push({ key, url, icon, label: key.charAt(0).toUpperCase() + key.slice(1) })
    }
    return out
})
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[#ffa236]">Perfil profesional</h1>
                <p class="text-gray-400">Vista detallada de tu perfil público y lanzamientos.</p>
            </div>
            <Link :href="route('artist.profile.edit')" class="px-4 py-2 rounded-md bg-[#ffa236] text-black font-semibold hover:bg-[#ffb54d]">Editar perfil</Link>
        </div>

        <div v-if="error" class="bg-red-900/40 border border-red-800 text-red-200 text-sm p-4 rounded-lg">{{ error }}</div>

        <div class="bg-gradient-to-b from-[#0b0b0b] to-[#0f0f0f] rounded-xl p-6 border border-[#1f1f1f]">
            <div class="flex flex-col lg:flex-row gap-6 items-center lg:items-start">
                <div class="flex-shrink-0">
                    <img :src="artist?.image_url || artist?.main_image_url || '/placeholder.webp'" alt="avatar" class="h-48 w-48 rounded-full object-cover border-4 border-[#2a2a2a] shadow-md" />
                </div>

                <div class="flex-1">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-white">{{ artist?.name }}</h2>
                            <p class="text-sm text-gray-400 mt-1">{{ artist?.genre?.name || '' }} · {{ artist?.country || '' }}</p>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p class="text-xs text-gray-400">Lanzamientos</p>
                                <p class="text-lg font-semibold text-white">{{ releases.length }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-400">Pistas</p>
                                <p class="text-lg font-semibold text-white">{{ artist?.tracks_count ?? 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <p class="mt-4 text-gray-300 whitespace-pre-line leading-relaxed">{{ artist?.bio || 'Sin biografía.' }}</p>

                    <div class="mt-4 flex items-center gap-3">
                        <template v-if="socialLinks.length === 0">
                            <span class="text-gray-500 text-sm">No hay redes sociales vinculadas.</span>
                        </template>
                        <template v-else>
                            <a v-for="s in socialLinks" :key="s.key" :href="s.url" target="_blank" rel="noopener noreferrer" class="text-gray-300 hover:text-white p-2 rounded transition-colors" :title="s.label">
                                <i :class="s.icon + ' text-xl'"></i>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-[#111111] border border-[#2a2a2a] rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Lanzamientos</h3>
                    <div v-if="releases.length === 0" class="text-gray-500">No hay lanzamientos aún.</div>
                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="r in releases" :key="r.id" class="p-3 bg-[#0f0f0f] border border-[#2a2a2a] rounded flex gap-4 items-center">
                            <img :src="r.cover || '/placeholder.webp'" class="h-20 w-20 object-cover rounded" />
                            <div class="flex-1">
                                <Link :href="route('public.releases.show', r.slug || r.id)" class="block hover:underline">
                                    <p class="font-semibold text-white">{{ r.title }}</p>
                                </Link>
                                <p class="text-xs text-gray-400">{{ formatDate(r.release_date) }}</p>
                                <p class="text-sm text-gray-300 mt-2 line-clamp-2">{{ r.description || '' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-[#111111] border border-[#2a2a2a] rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-2">Información</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-300">
                        <div>
                            <p class="text-xs text-gray-400">Email</p>
                            <p class="text-white font-medium">{{ artist?.email || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Teléfono</p>
                            <p class="text-white font-medium">{{ artist?.phone || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">País</p>
                            <p class="text-white font-medium">{{ artist?.country || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Género</p>
                            <p class="text-white font-medium">{{ artist?.genre?.name || '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="bg-[#111111] border border-[#2a2a2a] rounded-xl p-6 text-center">
                    <p class="text-xs text-gray-400">Compartir perfil</p>
                    <div class="mt-3 flex items-center justify-center gap-3">
                        <a v-for="s in socialLinks" :key="s.key + '-share'" :href="s.url" target="_blank" rel="noopener noreferrer" class="text-gray-300 hover:text-white p-2 rounded transition-colors">
                            <i :class="s.icon + ' text-lg'"></i>
                        </a>
                    </div>
                </div>

                <div class="bg-[#111111] border border-[#2a2a2a] rounded-xl p-6 text-center">
                    <p class="text-xs text-gray-400">Contactar</p>
                    <div class="mt-3">
                        <a v-if="artist?.email" :href="`mailto:${artist.email}`" class="inline-block px-4 py-2 bg-[#ffa236] text-black rounded-md font-semibold">Enviar email</a>
                        <span v-else class="text-gray-500">No disponible</span>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>

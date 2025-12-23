<script setup>
import ArtistLayout from '@/Layouts/ArtistLayout.vue'
import { ref, onMounted } from 'vue'
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Perfil principal -->
            <div class="col-span-1 bg-[#111111] border border-[#2a2a2a] rounded-xl p-6">
                <div class="flex flex-col items-center text-center gap-4">
                    <img :src="artist?.image_url || artist?.main_image_url || '/placeholder.webp'" alt="avatar" class="h-40 w-40 rounded-full object-cover border-2 border-[#2a2a2a]" />
                    <div>
                        <h2 class="text-xl font-semibold text-white">{{ artist?.name }}</h2>
                        <p class="text-sm text-gray-400">{{ artist?.genre?.name || '' }} · {{ artist?.country || '' }}</p>
                    </div>
                    <p class="text-sm text-gray-300 whitespace-pre-line">{{ artist?.bio }}</p>

                    <div class="w-full mt-4 grid grid-cols-2 gap-3">
                        <div class="p-3 bg-[#0f0f0f] border border-[#2a2a2a] rounded">
                            <p class="text-xs text-gray-400">Lanzamientos</p>
                            <p class="text-lg font-semibold text-white">{{ releases.length }}</p>
                        </div>
                        <div class="p-3 bg-[#0f0f0f] border border-[#2a2a2a] rounded">
                            <p class="text-xs text-gray-400">Canciones</p>
                            <p class="text-lg font-semibold text-white">{{ artist?.tracks_count ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalles y contacto -->
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-[#111111] border border-[#2a2a2a] rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-2">Información</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-300">
                        <div>
                            <p class="text-xs text-gray-400">Nombre artístico</p>
                            <p class="text-white font-medium">{{ artist?.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">País</p>
                            <p class="text-white font-medium">{{ artist?.country }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Género</p>
                            <p class="text-white font-medium">{{ artist?.genre?.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Redes</p>
                            <p class="text-white font-medium">{{ artist?.social_links_formatted ? Object.keys(artist.social_links_formatted).filter(k=>artist.social_links_formatted[k]).join(', ') : '—' }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-[#111111] border border-[#2a2a2a] rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-2">Lanzamientos</h3>
                    <div v-if="releases.length === 0" class="text-gray-500">No hay lanzamientos aún.</div>
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="r in releases" :key="r.id" class="p-4 bg-[#0f0f0f] border border-[#2a2a2a] rounded">
                            <div class="flex items-start gap-4">
                                <img :src="r.cover || '/placeholder.webp'" class="h-16 w-16 object-cover rounded" />
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-semibold text-white">{{ r.title }}</p>
                                            <p class="text-xs text-gray-400">{{ r.artist?.name || artist?.name }}</p>
                                        </div>
                                        <div class="text-xs text-gray-400">{{ formatDate(r.release_date) }}</div>
                                    </div>
                                    <p class="text-sm text-gray-300 mt-2 line-clamp-2">{{ r.description || '' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-[#111111] border border-[#2a2a2a] rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-2">Contacto</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-300">
                        <div>
                            <p class="text-xs text-gray-400">Email</p>
                            <p class="text-white font-medium">{{ artist?.email || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Teléfono</p>
                            <p class="text-white font-medium">{{ artist?.phone || '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

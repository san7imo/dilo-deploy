<script setup>
import ArtistVideo from './ArtistVideo.vue'

const props = defineProps({
    artist: { type: Object, required: true },
    youtubeUrl: { type: String, default: '' },
    isOpen: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

const closeModal = () => {
    emit('close')
}

const handleBackdropClick = (e) => {
    if (e.target === e.currentTarget) {
        closeModal()
    }
}
</script>

<template>
    <transition name="fade">
        <div v-if="isOpen" @click="handleBackdropClick"
            class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <transition name="modal-scale">
                <div v-if="isOpen"
                    class="relative w-full max-w-4xl bg-zinc-950 rounded-3xl ring-1 ring-white/10 shadow-2xl p-8">
                    <!-- Botón cerrar -->
                    <button @click="closeModal"
                        class="absolute top-4 right-4 z-10 bg-white/10 hover:bg-white/20 text-white p-2 rounded-full transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    <!-- Contenido -->
                    <h2 class="text-3xl font-black text-white mb-2">Video Destacado</h2>
                    <p class="text-zinc-400 mb-8">{{ artist.name }}</p>

                    <!-- Video -->
                    <div class="w-full aspect-video rounded-2xl overflow-hidden">
                        <ArtistVideo :youtube-url="youtubeUrl" />
                    </div>
                </div>
            </transition>
        </div>
    </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.modal-scale-enter-active,
.modal-scale-leave-active {
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-scale-enter-from,
.modal-scale-leave-to {
    transform: scale(0.95);
    opacity: 0;
}
</style>
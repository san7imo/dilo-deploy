<!-- resources/js/Components/Public/Artists/TrackList.vue -->
<script setup>
const props = defineProps({
  tracks: { type: Array, default: () => [] },
})

const emit = defineEmits(['play'])
</script>

<template>
  <ul class="space-y-2">
    <li
      v-for="t in tracks"
      :key="t.id"
      class="flex items-center justify-between gap-3 bg-zinc-900/60 rounded-xl px-3 py-2 ring-1 ring-white/5"
    >
      <div class="min-w-0">
        <p class="text-sm text-white truncate">{{ t.title }}</p>
        <p class="text-xs text-zinc-400">{{ t.duration ? t.duration : '—' }}</p>
      </div>

      <button
        class="shrink-0 inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-white text-black text-xs font-semibold hover:opacity-90"
        @click="emit('play', t)"
        :disabled="!t.audio_url"
        :title="t.audio_url ? 'Reproducir' : 'Sin audio'"
      >
        ▶︎
      </button>
    </li>
  </ul>
</template>

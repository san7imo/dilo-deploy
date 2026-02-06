<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  links: { type: Array, default: () => [] },
  meta: { type: Object, default: null },
  preserveScroll: { type: Boolean, default: true },
  preserveState: { type: Boolean, default: true },
  showAlways: { type: Boolean, default: true },
})

const shouldRender = computed(() => {
  if (!props.showAlways) {
    return Array.isArray(props.links) && props.links.some((link) => link.url && !link.active)
  }
  return Array.isArray(props.links)
})

const pageInfo = computed(() => {
  const current = Number(props.meta?.current_page ?? 1)
  const last = Number(props.meta?.last_page ?? 1)
  return `Página ${current} de ${last}`
})

const isDisabled = (link) => !link.url || link.active

const displayLabel = (label) => {
  if (!label) return ''
  const raw = String(label).replace(/<[^>]*>/g, '').trim()
  const decoded = raw
    .replace(/&laquo;/g, '«')
    .replace(/&raquo;/g, '»')
    .replace(/&hellip;/g, '…')
  const lower = decoded.toLowerCase()

  if (lower.includes('previous') || lower.includes('anterior')) return '‹'
  if (lower.includes('next') || lower.includes('siguiente')) return '›'
  if (decoded.includes('«')) return '‹'
  if (decoded.includes('»')) return '›'
  if (decoded.includes('…') || decoded.includes('...')) return '…'

  return decoded
}
</script>

<template>
  <div v-if="shouldRender" class="flex flex-wrap items-center gap-2">
    <span class="text-xs text-gray-400 px-2">{{ pageInfo }}</span>
    <component
      v-for="link in links"
      :key="link.label"
      :is="isDisabled(link) ? 'span' : Link"
      :href="isDisabled(link) ? undefined : link.url"
      :preserve-scroll="isDisabled(link) ? undefined : preserveScroll"
      :preserve-state="isDisabled(link) ? undefined : preserveState"
      :class="[
        'px-3 py-2 rounded text-sm font-medium transition-colors',
        link.active
          ? 'bg-[#ffa236] text-black cursor-default'
          : link.url
          ? 'bg-[#2a2a2a] text-gray-300 hover:bg-[#3a3a3a]'
          : 'bg-[#1a1a1a] text-gray-500 cursor-not-allowed',
      ]"
      :aria-disabled="isDisabled(link)"
    >
      {{ displayLabel(link.label) }}
    </component>
  </div>
</template>

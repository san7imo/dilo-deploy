<!-- resources/js/Pages/Public/Contact.vue -->
<script setup>
import { Head } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import ContactSection from '@/Components/Public/Home/ContactSection.vue'

defineOptions({ layout: PublicLayout })

defineProps({
  contact: { type: Object, default: () => ({}) },
})
</script>

<template>
  <Head title="Contacto — Dilo Records" />

  <ContactSection :contact="contact" :full-height="true" />
</template>

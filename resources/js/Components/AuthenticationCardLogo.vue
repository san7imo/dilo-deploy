<script setup>
import { Link } from "@inertiajs/vue3";
</script>

<template>
  <Link
    :href="'/'"
    class="inline-block overflow-visible justify-center items-center"
  >
    <img
      src="@/Assets/Images/Logos/logo-blanco.webp"
      alt="Dilo Records Logo"
      width="200"
      height="150"
      class="object-contain transition-transform duration-300 hover:scale-110 hover:drop-shadow-[0_0_7px_#ffa236]"
      loading="lazy"
    />
  </Link>
</template>

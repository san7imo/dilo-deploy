<script setup>
import logoBlanco from '@/Assets/Images/Logos/responsive-blanco.webp';
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const { props } = usePage();
const user = props.auth?.user;

const isSidebarOpen = ref(true);

const roleNames = computed(() => {
  const names = user?.role_names;
  return Array.isArray(names) ? names : [];
});

const isAdmin = computed(() => roleNames.value.includes("admin"));
const isRoadManager = computed(() => roleNames.value.includes("roadmanager"));
const isContentManager = computed(() => roleNames.value.includes("contentmanager"));
const canManageContent = computed(() => isAdmin.value || isContentManager.value);
const dashboardRoute = computed(() =>
  isRoadManager.value || isContentManager.value
    ? "admin.events.index"
    : "admin.dashboard"
);
const dashboardLabel = computed(() =>
  isRoadManager.value ? "Eventos" : isContentManager.value ? "Eventos" : "Dashboard"
);
const panelTitle = computed(() =>
  isRoadManager.value
    ? "Panel Road Manager"
    : isContentManager.value
      ? "Panel Gestor de Contenido"
      : "Panel de Administración"
);
const isEventDashboard = computed(() => isRoadManager.value || isContentManager.value);

const logout = () => {
  router.post(route("logout"));
};
</script>

<template>
  <div class="flex min-h-screen bg-[#0d0d0d] text-white font-['Roboto',sans-serif]">
    <!-- Sidebar -->
    <aside
      :class="[
        'transition-all duration-300 ease-in-out bg-[#1d1d1b] border-r border-[#2a2a2a] flex flex-col',
        isSidebarOpen ? 'w-64' : 'w-20'
      ]"
    >
      <!-- Logo -->
      <div class="flex items-center justify-between p-4 border-b border-[#2a2a2a]">
        <Link :href="route(dashboardRoute)" class="flex items-center gap-2">
            <img
            :src="logoBlanco"
              alt="Dilo Records"
              width="60"
              height="60"
              class="h-10 w-auto object-contain"
            />
        </Link>
        <button
          @click="isSidebarOpen = !isSidebarOpen"
          class="text-[#ffa236] hover:text-[#ffb54d] transition-colors"
        >
          <svg
            v-if="isSidebarOpen"
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          <svg
            v-else
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
          </svg>
        </button>
      </div>

      <!-- Navegación -->
      <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
        <Link
          :href="route(dashboardRoute)"
          class="flex items-center gap-3 px-3 py-2 rounded-md text-sm hover:bg-[#2a2a2a] transition-colors"
          :class="{
            'bg-[#ffa236]/20 text-[#ffa236]': isEventDashboard
              ? route().current('admin.events.*')
              : route().current('admin.dashboard')
          }"
        >
          <i class="fa-solid fa-house"></i>
          <span v-if="isSidebarOpen">{{ dashboardLabel }}</span>
        </Link>

        <Link
          v-if="canManageContent"
          :href="route('admin.artists.index')"
          class="flex items-center gap-3 px-3 py-2 rounded-md text-sm hover:bg-[#2a2a2a] transition-colors"
          :class="{ 'bg-[#ffa236]/20 text-[#ffa236]': route().current('admin.artists.*') }"
        >
          <i class="fa-solid fa-user"></i>
          <span v-if="isSidebarOpen">Artistas</span>
        </Link>

        <Link
          v-if="canManageContent"
          :href="route('admin.team.index')"
          class="flex items-center gap-3 px-3 py-2 rounded-md text-sm hover:bg-[#2a2a2a] transition-colors"
          :class="{
            'bg-[#ffa236]/20 text-[#ffa236]':
              route().current('admin.team.*') ||
              route().current('admin.roadmanagers.*') ||
              route().current('admin.content-managers.*')
          }"
        >
          <i class="fa-solid fa-clipboard-user"></i>
          <span v-if="isSidebarOpen">Equipo de trabajo</span>
        </Link>

        <Link
          v-if="canManageContent"
          :href="route('admin.releases.index')"
          class="flex items-center gap-3 px-3 py-2 rounded-md text-sm hover:bg-[#2a2a2a] transition-colors"
          :class="{ 'bg-[#ffa236]/20 text-[#ffa236]': route().current('admin.releases.*') }"
        >
          <i class="fa-solid fa-compact-disc"></i>
          <span v-if="isSidebarOpen">Releases</span>
        </Link>

        <Link
          v-if="canManageContent"
          :href="route('admin.tracks.index')"
          class="flex items-center gap-3 px-3 py-2 rounded-md text-sm hover:bg-[#2a2a2a] transition-colors"
          :class="{ 'bg-[#ffa236]/20 text-[#ffa236]': route().current('admin.tracks.*') }"
        >
          <i class="fa-solid fa-music"></i>
          <span v-if="isSidebarOpen">Tracks</span>
        </Link>

        <Link
          v-if="canManageContent"
          :href="route('admin.events.index')"
          class="flex items-center gap-3 px-3 py-2 rounded-md text-sm hover:bg-[#2a2a2a] transition-colors"
          :class="{ 'bg-[#ffa236]/20 text-[#ffa236]': route().current('admin.events.*') }"
        >
          <i class="fa-solid fa-calendar-days"></i>
          <span v-if="isSidebarOpen">Eventos</span>
        </Link>

        <Link
          v-if="canManageContent"
          :href="route('admin.genres.index')"
          class="flex items-center gap-3 px-3 py-2 rounded-md text-sm hover:bg-[#2a2a2a] transition-colors"
          :class="{ 'bg-[#ffa236]/20 text-[#ffa236]': route().current('admin.genres.*') }"
        >
          <i class="fa-solid fa-tags"></i>
          <span v-if="isSidebarOpen">Géneros</span>
        </Link>
      </nav>

      <!-- Footer -->
      <div class="p-4 border-t border-[#2a2a2a] text-xs text-gray-500">
        <div v-if="isSidebarOpen" class="mb-2">{{ user?.name }}</div>
        <button
          type="button"
          class="flex items-center gap-2 text-[#ffa236] hover:text-[#ffb54d] transition-colors"
          @click="logout"
        >
          <i class="fa-solid fa-right-from-bracket"></i>
          <span v-if="isSidebarOpen">Cerrar sesión</span>
        </button>
      </div>
    </aside>

    <!-- Contenido principal -->
    <div class="flex-1 flex flex-col min-h-screen">
      <!-- Navbar -->
      <header
        class="h-14 bg-[#1d1d1b] border-b border-[#2a2a2a] flex items-center justify-between px-6"
      >
        <h1 class="text-lg font-semibold text-[#ffa236]">{{ panelTitle }}</h1>
        <Link :href="route('public.home')" class="text-gray-400 hover:text-white" aria-label="Inicio">
          <i class="fa-solid fa-house"></i>
        </Link>
      </header>

      <!-- Contenido dinámico -->
      <main class="flex-1 overflow-y-auto p-6 bg-[#0f0f0f]">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
::-webkit-scrollbar {
  width: 6px;
}
::-webkit-scrollbar-thumb {
  background: #ffa236;
  border-radius: 4px;
}
</style>

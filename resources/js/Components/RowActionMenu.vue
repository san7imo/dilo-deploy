<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";

defineProps({
  label: {
    type: String,
    default: "Abrir acciones",
  },
});

const isOpen = ref(false);
const isPositioned = ref(false);
const triggerRef = ref(null);
const menuRef = ref(null);
const menuStyle = ref({});
const lastMenuWidth = ref(176);
const lastMenuHeight = ref(140);

const closeMenu = () => {
  isOpen.value = false;
  isPositioned.value = false;
};

const computeMenuPosition = (menuWidth, menuHeight) => {
  if (!triggerRef.value) return {};

  const triggerRect = triggerRef.value.getBoundingClientRect();
  const viewportPadding = 8;
  const gap = 8;

  let left = triggerRect.right - menuWidth;
  left = Math.min(left, window.innerWidth - menuWidth - viewportPadding);
  left = Math.max(left, viewportPadding);

  let top = triggerRect.bottom + gap;
  if (top + menuHeight > window.innerHeight - viewportPadding) {
    top = Math.max(viewportPadding, triggerRect.top - menuHeight - gap);
  }

  return {
    position: "fixed",
    top: `${top}px`,
    left: `${left}px`,
    zIndex: 2000,
  };
};

const updateMenuPosition = () => {
  if (!isOpen.value || !triggerRef.value) return;

  const menuWidth = menuRef.value?.offsetWidth || lastMenuWidth.value;
  const menuHeight = menuRef.value?.offsetHeight || lastMenuHeight.value;
  lastMenuWidth.value = menuWidth;
  lastMenuHeight.value = menuHeight;

  menuStyle.value = computeMenuPosition(menuWidth, menuHeight);
  isPositioned.value = true;
};

const openMenu = async () => {
  if (!triggerRef.value) return;

  isPositioned.value = false;
  menuStyle.value = computeMenuPosition(lastMenuWidth.value, lastMenuHeight.value);
  isOpen.value = true;

  await nextTick();
  updateMenuPosition();
};

const toggleMenu = async () => {
  if (isOpen.value) {
    closeMenu();
    return;
  }

  await openMenu();
};

const handleDocumentClick = (event) => {
  if (!isOpen.value) return;

  const target = event.target;
  if (triggerRef.value?.contains(target)) return;
  if (menuRef.value?.contains(target)) return;

  closeMenu();
};

const handleEscape = (event) => {
  if (event.key === "Escape") closeMenu();
};

onMounted(() => {
  document.addEventListener("click", handleDocumentClick, true);
  document.addEventListener("keydown", handleEscape);
  window.addEventListener("resize", updateMenuPosition);
  window.addEventListener("scroll", updateMenuPosition, true);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleDocumentClick, true);
  document.removeEventListener("keydown", handleEscape);
  window.removeEventListener("resize", updateMenuPosition);
  window.removeEventListener("scroll", updateMenuPosition, true);
});

watch(isOpen, async (open) => {
  if (!open) return;
  await nextTick();
  updateMenuPosition();
});
</script>

<template>
  <div class="inline-block text-left">
    <button
      ref="triggerRef"
      type="button"
      class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-md border border-[#2a2a2a] bg-[#121212] text-gray-300 hover:border-[#3a3a3a] hover:text-white transition-colors"
      :aria-label="label"
      :aria-expanded="isOpen"
      @click.stop="toggleMenu"
    >
      <i class="fa-solid fa-ellipsis-vertical text-sm"></i>
    </button>

    <teleport to="body">
      <div
        v-if="isOpen"
        ref="menuRef"
        :style="[menuStyle, { visibility: isPositioned ? 'visible' : 'hidden' }]"
        class="min-w-[10rem] rounded-md border border-[#2a2a2a] bg-[#101010] p-1 shadow-xl"
        @click="closeMenu"
      >
        <slot />
      </div>
    </teleport>
  </div>
</template>

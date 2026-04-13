<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'

const props = defineProps({
  projects: { type: Array, required: true },
  forceMode: { type: String, default: null },
})

const emit = defineEmits(['projectClick'])

const isMobile = ref(false)

function checkMobile() {
  if (props.forceMode === 'mobile') { isMobile.value = true; return }
  if (props.forceMode === 'desktop') { isMobile.value = false; return }
  isMobile.value = window.innerWidth < 768
}

onMounted(() => {
  checkMobile()
  if (!props.forceMode) window.addEventListener('resize', checkMobile)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkMobile)
})

watch(() => props.forceMode, checkMobile)

const gridCols = computed(() => isMobile.value ? 1 : 3)
</script>

<template>
  <div
    class="pg-section"
    :class="isMobile ? 'pg-section--mobile' : 'pg-section--desktop'"
  >
    <slot name="header"></slot>

    <div
      class="pg-gallery"
      :style="{ 'grid-template-columns': `repeat(${gridCols}, 1fr)` }"
    >
      <div
        v-for="project in projects"
        :key="project.id"
        class="pg-item"
        tabindex="0"
        role="button"
        :aria-label="project.title"
        @click="emit('projectClick', project)"
        @keydown.enter="emit('projectClick', project)"
      >
        <img v-if="project.hero" :src="project.hero" :alt="project.title" loading="lazy" />
        <div v-else class="pg-item-placeholder">{{ project.title?.[0] || '?' }}</div>
        <div class="pg-label">{{ project.title.toUpperCase() }}</div>
      </div>
    </div>

    <slot name="footer"></slot>
  </div>
</template>

<style scoped>
.pg-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  background-color: var(--pg-bg, #5b3c88);
  color: var(--pg-text, #f5f0eb);
  position: relative;
  overflow: hidden;
}

.pg-section--mobile {
  padding: 48px 16px 135px;
}

.pg-section--desktop {
  padding: 84px 20px 135px;
}

/* ── Gallery grid ── */
.pg-gallery {
  display: grid;
  gap: 16px;
  width: 80%;
  max-width: 1200px;
}

.pg-section--desktop .pg-gallery {
  width: 100%;
  gap: 20px;
  padding: 0 20px;
}

/* ── Item ── */
.pg-item {
  position: relative;
  cursor: pointer;
  overflow: hidden;
}

.pg-item img {
  display: block;
  width: 100%;
  height: auto;
  object-fit: cover;
  transition: transform 0.3s ease;
  border-radius: 10px;
}

@media (hover: hover) {
  .pg-item:hover img {
    transform: scale(0.98);
  }
}

.pg-item:focus-visible {
  outline: 2px solid var(--pg-text, #f5f0eb);
  outline-offset: 2px;
}

.pg-item-placeholder {
  aspect-ratio: 16 / 9;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: 700;
  color: var(--pg-text, #f5f0eb);
  opacity: 0.3;
  background: rgba(255, 255, 255, 0.06);
  border-radius: 10px;
}

.pg-label {
  text-align: left;
  margin-top: 8px;
  font-weight: 300;
  letter-spacing: 1.6px;
  color: var(--pg-text, #f5f0eb);
}
</style>

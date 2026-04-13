<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'

const props = defineProps({
  projects: { type: Array, required: true },
  forceMode: { type: String, default: null },
  sectionRadius: { type: String, default: '0' },
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

const gridCols = computed(() => {
  if (isMobile.value) return 1
  return props.projects.length <= 2 ? 2 : 3
})
</script>

<template>
  <section
    class="pp-section"
    :class="isMobile ? 'pp-section--mobile' : 'pp-section--desktop'"
    :style="{ '--pp-section-radius': sectionRadius }"
  >
    <slot name="title">
      <h1 class="pp-title">Progetti</h1>
    </slot>

    <div
      class="pp-grid"
      :style="{ 'grid-template-columns': `repeat(${gridCols}, 1fr)` }"
    >
      <div
        v-for="project in projects"
        :key="project.id"
        class="pp-item"
        tabindex="0"
        role="button"
        :aria-label="project.title"
        @click="emit('projectClick', project)"
        @keydown.enter="emit('projectClick', project)"
      >
        <img v-if="project.gif" :src="project.gif" :alt="project.title" loading="lazy" />
        <div v-else class="pp-item-placeholder">{{ project.title?.[0] || '?' }}</div>
        <div class="pp-overlay">
          <div class="pp-overlay-title">{{ project.title }}</div>
          <div class="pp-overlay-info">{{ project.info }}</div>
        </div>
        <div class="pp-label">{{ project.title }}</div>
      </div>
    </div>

    <slot name="footer"></slot>
  </section>
</template>

<style scoped>
.pp-section {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  background-color: var(--pp-bg, #f5f0eb);
  color: var(--pp-text, #5b3c88);
  border-radius: var(--pp-section-radius, 0);
  overflow: hidden;
}

.pp-section--mobile {
  padding: 48px 16px;
}

.pp-section--desktop {
  padding: 84px 20px;
}

.pp-title {
  color: inherit;
  text-align: center;
  margin: 0 0 32px;
}

@media (min-width: 768px) {
  .pp-title {
    margin-bottom: 40px;
  }
}

/* ── Grid ── */
.pp-grid {
  display: grid;
  gap: 20px;
  width: 100%;
  max-width: 1200px;
}

@media (min-width: 992px) {
  .pp-grid {
    gap: 24px;
  }
}

/* ── Item ── */
.pp-item {
  position: relative;
  cursor: pointer;
  overflow: hidden;
  border-radius: 6px;
  -webkit-tap-highlight-color: transparent;
  aspect-ratio: 3 / 4;
}

@supports not (aspect-ratio: 3 / 4) {
  .pp-item::before {
    content: "";
    display: block;
    padding-bottom: 133.33%;
  }
}

.pp-item img {
  position: absolute;
  inset: 0;
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.pp-item:active img {
  transform: scale(0.98);
  transition: transform 0.15s ease;
}

.pp-item:focus-visible {
  outline: 2px solid var(--pp-text, #5b3c88);
  outline-offset: 2px;
}

.pp-item-placeholder {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: 700;
  color: var(--pp-text, #5b3c88);
  opacity: 0.3;
  background: rgba(91, 60, 136, 0.06);
}

/* ── Overlay (hover devices) ── */
.pp-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
  background-color: var(--pp-text, rgba(91, 60, 136, 1));
  color: white;
  padding: 40px 20px;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.3s ease;
}

.pp-overlay-title {
  font-weight: 500;
  font-family: "Young Serif", serif;
  text-align: center;
}

.pp-overlay-info {
  font-family: "Assistant", serif;
  letter-spacing: 1px;
  font-weight: 400;
  text-align: center;
  margin-top: auto;
}

@media (hover: hover) {
  .pp-item:hover .pp-overlay {
    opacity: 1;
    pointer-events: auto;
  }
  .pp-label {
    display: none;
  }
}

/* ── Label (touch devices) ── */
.pp-label {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 10px 12px;
  background: linear-gradient(transparent, rgba(91, 60, 136, 0.85));
  color: white;
  font-family: "Young Serif", serif;
  font-size: 1.1rem;
  pointer-events: none;
}

@media (hover: hover) {
  .pp-label {
    display: none;
  }
}
</style>

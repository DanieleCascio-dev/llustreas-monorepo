<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'

const props = defineProps({
  columns: { type: Array, required: true },
  forceMode: { type: String, default: null },
})

const emit = defineEmits(['imageClick'])

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
</script>

<template>
  <div
    class="mg-section"
    :class="isMobile ? 'mg-section--mobile' : 'mg-section--desktop'"
  >
    <slot name="header"></slot>

    <div v-if="columns.length" class="mg-grid" :class="isMobile ? 'mg-grid--mobile' : 'mg-grid--desktop'">
      <div class="mg-col" v-for="(col, colIdx) in columns" :key="colIdx">
        <div
          class="mg-item"
          v-for="image in col"
          :key="image.id"
          tabindex="0"
          role="button"
          :aria-label="image.title || 'Apri immagine'"
          @click="emit('imageClick', image)"
          @keydown.enter="emit('imageClick', image)"
        >
          <img :src="image.src" :alt="image.title || ''" loading="lazy" />
        </div>
      </div>
    </div>
    <div v-else class="mg-empty">
      <p>Nessuna immagine in galleria.</p>
    </div>

    <slot name="footer"></slot>
  </div>
</template>

<style scoped>
.mg-section {
  background-color: var(--mg-bg, #f5f0eb);
  overflow: hidden;
}

.mg-section--mobile {
  padding: 0 12px 32px;
}

.mg-section--desktop {
  padding: 0 24px 48px;
}

.mg-grid {
  display: flex;
}

.mg-grid--mobile {
  gap: 6px;
}

.mg-grid--desktop {
  gap: 8px;
}

.mg-col {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.mg-grid--mobile .mg-col {
  gap: 6px;
}

.mg-grid--desktop .mg-col {
  gap: 8px;
}

.mg-item {
  overflow: hidden;
  border-radius: 6px;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
}

.mg-item:focus-visible {
  outline: 2px solid var(--mg-text, #5b3c88);
  outline-offset: 2px;
}

.mg-item img {
  width: 100%;
  height: auto;
  display: block;
  transition: transform 0.35s ease, filter 0.35s ease;
}

@media (hover: hover) {
  .mg-item:hover img {
    transform: scale(1.03);
    filter: brightness(0.92);
  }
}

.mg-item:active img {
  transform: scale(0.98);
  transition: transform 0.12s ease;
}

.mg-empty {
  text-align: center;
  padding: 64px 24px;
  color: var(--mg-text, #5b3c88);
  font-size: 1rem;
}
</style>

<script setup>
defineProps({
  images: { type: Array, required: true },
  sectionRadius: { type: String, default: '0' },
})

const emit = defineEmits(['imageClick'])
</script>

<template>
  <div
    v-if="images.length"
    class="gpg-section"
    :style="{ '--gpg-section-radius': sectionRadius }"
  >
    <slot name="title">
      <h1 class="gpg-title">Illustrazioni</h1>
    </slot>

    <div class="gpg-grid">
      <div
        v-for="item in images"
        :key="item.id"
        class="gpg-item"
        tabindex="0"
        role="button"
        :aria-label="item.title || 'Apri immagine'"
        @click="emit('imageClick', item)"
        @keydown.enter="emit('imageClick', item)"
      >
        <img :src="item.url" :alt="item.title" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.gpg-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  background-color: var(--gpg-bg, #8472ac);
  color: var(--gpg-text, white);
  padding: 40px 16px 60px;
  border-radius: var(--gpg-section-radius, 0);
  overflow: hidden;
}

@media (min-width: 768px) {
  .gpg-section {
    padding: 40px 40px 84px;
  }
}

@media (min-width: 1200px) {
  .gpg-section {
    padding: 40px 200px 84px;
  }
}

.gpg-title {
  color: inherit;
  text-align: center;
  margin: 0 0 24px;
  z-index: 2;
}

@media (min-width: 768px) {
  .gpg-title {
    margin-bottom: 32px;
  }
}

.gpg-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
  width: 100%;
  max-width: 480px;
}

@media (min-width: 576px) {
  .gpg-grid {
    grid-template-columns: repeat(2, 1fr);
    max-width: none;
    gap: 8px;
  }
}

@media (min-width: 768px) {
  .gpg-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
  }
}

@media (min-width: 576px) and (max-width: 767px) {
  .gpg-item:last-child:nth-child(odd) {
    grid-column: 1 / -1;
    max-width: calc(50% - 4px);
    justify-self: center;
  }
}

.gpg-item {
  overflow: hidden;
  border-radius: 4px;
  aspect-ratio: 1 / 1;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
}

.gpg-item:focus-visible {
  outline: 2px solid white;
  outline-offset: 2px;
}

.gpg-item img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

@media (hover: hover) {
  .gpg-item:hover img {
    transform: scale(1.03);
  }
}

.gpg-item:active img {
  transform: scale(0.97);
  transition: transform 0.15s ease;
}
</style>

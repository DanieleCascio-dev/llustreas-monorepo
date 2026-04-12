<script setup>
/*** GALLERIA MASONRY — 2 colonne mobile, 3 colonne da 768px ***/
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import ImageModal from './ImageModal.vue'
import { useProjectStore } from '../store'

const store = useProjectStore()
const storeColumns = computed(() => store.columns)

onMounted(() => {
  store.fetchGallery()
})

const columnCount = ref(window.innerWidth < 768 ? 2 : 3)

let resizeTimer = 0
function onResize() {
  clearTimeout(resizeTimer)
  resizeTimer = window.setTimeout(() => {
    columnCount.value = window.innerWidth < 768 ? 2 : 3
  }, 150)
}

const allImages = computed(() => {
  if (!storeColumns.value?.length) return []
  const flat = []
  const maxLen = Math.max(...storeColumns.value.map(c => c.length))
  for (let row = 0; row < maxLen; row++) {
    for (let col = 0; col < storeColumns.value.length; col++) {
      if (storeColumns.value[col][row]) flat.push(storeColumns.value[col][row])
    }
  }
  return flat
})

const displayColumns = computed(() => {
  const flat = allImages.value
  if (!flat.length) return []
  const n = columnCount.value
  const cols = Array.from({ length: n }, () => [])
  flat.forEach((img, i) => cols[i % n].push(img))
  return cols
})

const isModalVisible = ref(false)
const selectedImage = ref({ src: '', title: '' })

function openModal(image) {
  selectedImage.value = image
  isModalVisible.value = true
}

function closeModal() {
  isModalVisible.value = false
  selectedImage.value = { src: '', title: '' }
}

function prevImage() {
  const idx = allImages.value.findIndex(i => i.src === selectedImage.value.src)
  if (idx <= 0) {
    selectedImage.value = allImages.value[allImages.value.length - 1]
  } else {
    selectedImage.value = allImages.value[idx - 1]
  }
}

function nextImage() {
  const idx = allImages.value.findIndex(i => i.src === selectedImage.value.src)
  if (idx >= allImages.value.length - 1) {
    selectedImage.value = allImages.value[0]
  } else {
    selectedImage.value = allImages.value[idx + 1]
  }
}

onMounted(() => {
  window.addEventListener('resize', onResize)
})
onBeforeUnmount(() => {
  window.removeEventListener('resize', onResize)
  clearTimeout(resizeTimer)
})
</script>

<template>
  <div class="masonry-section">
    <div class="masonry-grid" v-if="displayColumns.length">
      <div class="masonry-col" v-for="(col, colIdx) in displayColumns" :key="colIdx">
        <div
          class="masonry-item"
          v-for="image in col"
          :key="image.id"
          tabindex="0"
          role="button"
          :aria-label="image.title || 'Apri immagine'"
          @click="openModal(image)"
          @keydown.enter="openModal(image)"
        >
          <img :src="image.src" :alt="image.title || ''" loading="lazy" />
        </div>
      </div>
    </div>
    <div v-else class="masonry-empty">
      <p>Nessuna immagine in galleria.</p>
    </div>

    <transition name="modal-fade">
      <ImageModal
        :visible="isModalVisible"
        :imageSrc="selectedImage"
        @close="closeModal"
        @prev="prevImage"
        @next="nextImage"
      />
    </transition>
  </div>
</template>

<style lang="scss" scoped>
@use "../style/partials/variables" as *;

.masonry-section {
  background-color: $bg-light;
  padding: 0 12px 32px;

  @media (min-width: 768px) {
    padding: 0 24px 48px;
  }
}

.masonry-grid {
  display: flex;
  gap: 6px;

  @media (min-width: 768px) {
    gap: 8px;
  }
}

.masonry-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;

  @media (min-width: 768px) {
    gap: 8px;
  }
}

.masonry-item {
  overflow: hidden;
  border-radius: 6px;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;

  &:focus-visible {
    outline: 2px solid var(--brand-violet, #5b3c88);
    outline-offset: 2px;
  }

  img {
    width: 100%;
    height: auto;
    display: block;
    transition: transform 0.35s ease, filter 0.35s ease;
  }

  @media (hover: hover) {
    &:hover img {
      transform: scale(1.03);
      filter: brightness(0.92);
    }
  }

  &:active img {
    transform: scale(0.98);
    transition: transform 0.12s ease;
  }
}

.masonry-empty {
  text-align: center;
  padding: 64px 24px;
  color: $text-color;
  font-size: 1rem;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.4s;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>

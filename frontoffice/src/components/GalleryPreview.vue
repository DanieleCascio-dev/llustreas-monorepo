<script setup lang="ts">
/*** ANTEPRIMA GALLERIA — griglia di immagini nella homepage con lightbox ***/
import { useProjectStore } from "../store.js";
import { computed, onMounted, ref } from "vue";
import ImageModal from "./ImageModal.vue";

const projectStore = useProjectStore();

onMounted(() => {
  projectStore.fetchGalleryPreview()
})

const previewImages = computed(() => projectStore.galleryPreview)

const isModalVisible = ref(false)
const selectedImage = ref({ src: '', title: '' })

function openModal(item: any) {
  selectedImage.value = { src: item.url, title: item.title || '' }
  isModalVisible.value = true
}

function closeModal() {
  isModalVisible.value = false
  selectedImage.value = { src: '', title: '' }
}

function currentIndex() {
  return previewImages.value.findIndex(i => i.url === selectedImage.value.src)
}

function prevImage() {
  const list = previewImages.value
  if (!list.length) return
  const idx = currentIndex()
  const prev = idx <= 0 ? list.length - 1 : idx - 1
  selectedImage.value = { src: list[prev].url, title: list[prev].title || '' }
}

function nextImage() {
  const list = previewImages.value
  if (!list.length) return
  const idx = currentIndex()
  const next = idx >= list.length - 1 ? 0 : idx + 1
  selectedImage.value = { src: list[next].url, title: list[next].title || '' }
}
</script>

<template>
  <div class="gallery-preview" id="gallery-preview">
    <h1 class="preview-title title-custom">Illustrazioni</h1>

    <div class="preview-grid">
      <div
        class="preview-item"
        v-for="item in previewImages"
        :key="item.id"
        tabindex="0"
        role="button"
        :aria-label="item.title || 'Apri immagine'"
        @click="openModal(item)"
        @keydown.enter="openModal(item)"
      >
        <img :src="item.url" :alt="item.title" />
      </div>
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

<style scoped lang="scss">
@use "../style/partials/variables" as *;

.gallery-preview {
  display: flex;
  flex-direction: column;
  align-items: center;
  background-color: $text-violet;
  padding: 40px 16px 60px;

  @media (min-width: 768px) {
    padding: 40px 40px 84px;
  }

  @media (min-width: 1200px) {
    padding: 40px 200px 84px;
  }
}

.preview-title {
  color: white;
  text-align: center;
  margin-bottom: 24px;
  z-index: 2;

  @media (min-width: 768px) {
    margin-bottom: 32px;
  }
}

.preview-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
  width: 100%;
  max-width: 480px;

  @media (min-width: 576px) {
    grid-template-columns: repeat(2, 1fr);
    max-width: none;
    gap: 8px;
  }

  @media (min-width: 768px) {
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
  }
}

@media (min-width: 576px) and (max-width: 767px) {
  .preview-item:last-child:nth-child(odd) {
    grid-column: 1 / -1;
    max-width: calc(50% - 4px);
    justify-self: center;
  }
}

.preview-item {
  overflow: hidden;
  border-radius: 4px;
  aspect-ratio: 1 / 1;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;

  &:focus-visible {
    outline: 2px solid white;
    outline-offset: 2px;
  }

  img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
  }

  @media (hover: hover) {
    &:hover img {
      transform: scale(1.03);
    }
  }

  &:active img {
    transform: scale(0.97);
    transition: transform 0.15s ease;
  }
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

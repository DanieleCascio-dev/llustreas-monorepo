<script setup>
import { GalleryPreviewGrid as BaseGrid } from '@illustreas/shared-ui'
import { useProjectStore } from '../store.js'
import { computed, onMounted, ref } from 'vue'
import ImageModal from './ImageModal.vue'

const store = useProjectStore()

onMounted(() => {
  store.fetchGalleryPreview()
})

const previewImages = computed(() => store.galleryPreview)

const isModalVisible = ref(false)
const selectedImage = ref({ src: '', title: '' })

function openModal(item) {
  selectedImage.value = { src: item.url, title: item.title || '' }
  isModalVisible.value = true
}

function closeModal() {
  isModalVisible.value = false
  selectedImage.value = { src: '', title: '' }
}

function currentIndex() {
  return previewImages.value.findIndex((i) => i.url === selectedImage.value.src)
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
  <BaseGrid
    :images="previewImages"
    @image-click="openModal"
  >
    <template #title>
      <h1 class="preview-title title-custom">Illustrazioni</h1>
    </template>
  </BaseGrid>

  <transition name="modal-fade">
    <ImageModal
      :visible="isModalVisible"
      :imageSrc="selectedImage"
      @close="closeModal"
      @prev="prevImage"
      @next="nextImage"
    />
  </transition>
</template>

<style scoped lang="scss">
@use "../style/partials/variables" as *;

:deep(.gpg-section) {
  --gpg-bg: #{$text-violet};
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

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.4s;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>

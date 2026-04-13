<script setup>
import { GalleryPreviewCarousel as BaseCarousel } from '@illustreas/shared-ui'
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
  <BaseCarousel
    :images="previewImages"
    mobile-scroll
    marquee-speed="50s"
    @image-click="openModal"
  >
    <template #title>
      <h1 v-reveal class="carousel-title title-custom">Illustrazioni</h1>
    </template>
  </BaseCarousel>

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

:deep(.gpc-section) {
  --gpc-bg: #{$text-violet};
}

.carousel-title {
  color: white;
  text-align: center;
  margin-bottom: 28px;
  padding: 0 16px;

  @media (min-width: 768px) {
    margin-bottom: 36px;
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

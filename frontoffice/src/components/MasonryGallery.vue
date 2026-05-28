<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { MasonryGallery as BaseMasonryGallery } from '@illustreas/shared-ui'
import ImageModal from './ImageModal.vue'
import { useProjectStore } from '../store'

const store = useProjectStore()

const mobileQuery = typeof window !== 'undefined'
  ? window.matchMedia('(max-width: 767px)')
  : null

const isMobile = ref(mobileQuery?.matches ?? false)

function syncMobileState(event) {
  isMobile.value = event?.matches ?? mobileQuery?.matches ?? false
}

onMounted(() => {
  store.fetchGallery()
  syncMobileState()
  mobileQuery?.addEventListener('change', syncMobileState)
})

onBeforeUnmount(() => {
  mobileQuery?.removeEventListener('change', syncMobileState)
})

function deriveColumns(sourceColumns, columnCount) {
  const target = Array.from({ length: columnCount }, () => [])
  const maxRows = Math.max(0, ...sourceColumns.map(col => col.length))
  const flat = []

  for (let row = 0; row < maxRows; row++) {
    sourceColumns.forEach(col => {
      if (col[row]) flat.push(col[row])
    })
  }

  flat.forEach((image, index) => {
    target[index % columnCount].push(image)
  })

  return target
}

const activeColumns = computed(() =>
  isMobile.value ? deriveColumns(store.columns, 2) : store.columns
)

const allImages = computed(() => {
  const cols = activeColumns.value
  if (!cols?.length) return []
  const flat = []
  const maxLen = Math.max(...cols.map(c => c.length))
  for (let row = 0; row < maxLen; row++) {
    for (let col = 0; col < cols.length; col++) {
      if (cols[col][row]) flat.push(cols[col][row])
    }
  }
  return flat
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
</script>

<template>
  <div class="masonry-fo-wrap">
    <BaseMasonryGallery
      :columns="activeColumns"
      :force-mode="isMobile ? 'mobile' : 'desktop'"
      @image-click="openModal"
    />

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

.masonry-fo-wrap :deep(.mg-section) {
  background-color: $bg-light;
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

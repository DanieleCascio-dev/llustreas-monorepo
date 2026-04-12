<script setup>
/*** COMPONENTE PER LA VISUALIZZAZIONE GRAFICA DI UN PROGETTO A GRIGLIA ***/
import { ref, computed } from 'vue'
import ImageModal from './ImageModal.vue'

const props = defineProps({
  project: {
    type: Object,
    required: true
  }
});

const isTextImageBlock = (image) => {
  return image && image.type === "textImageBlock";
};

const simpleImages = computed(() =>
  (props.project?.images || []).filter(img => !isTextImageBlock(img))
)

const isModalVisible = ref(false)
const selectedImage = ref({ src: '', title: '' })

function openModal(image) {
  selectedImage.value = { src: image.src, title: '' }
  isModalVisible.value = true
}

function closeModal() {
  isModalVisible.value = false
  selectedImage.value = { src: '', title: '' }
}

function currentIndex() {
  return simpleImages.value.findIndex(i => i.src === selectedImage.value.src)
}

function prevImage() {
  const list = simpleImages.value
  if (!list.length) return
  const idx = currentIndex()
  const prev = idx <= 0 ? list.length - 1 : idx - 1
  selectedImage.value = { src: list[prev].src, title: '' }
}

function nextImage() {
  const list = simpleImages.value
  if (!list.length) return
  const idx = currentIndex()
  const next = idx >= list.length - 1 ? 0 : idx + 1
  selectedImage.value = { src: list[next].src, title: '' }
}
</script>

<template>
  <div class="project header" v-if="project">
    <h1 class="text-center"><strong>{{ project.title }}</strong></h1>
    <p v-if="project.description" class="project-description">{{ project.description }}</p>

    <div class="image-grid">
      <div
        v-for="image in project.images"
        :key="image.id"
        class="image-item"
        :class="{ 'image-item--text-block': isTextImageBlock(image) }"
      >
        <div
          v-if="isTextImageBlock(image)"
          class="text-image-block"
          :class="image.layout || 'text-left'"
          :style="{ backgroundColor: image.text.backgroundColor }"
        >
          <div
            class="block-text"
            :style="{ color: image.text.textColor, backgroundColor: image.text.backgroundColor }"
          >
            <div class="block-header">
              <div v-if="image.text.subtitle" class="block-subtitle">{{ image.text.subtitle }}</div>
              <div class="block-title"><strong>{{ image.text.title }}</strong></div>
            </div>
            <div class="block-paragraphs">
              <div
                v-for="(paragraph, index) in image.text.paragraphs"
                :key="index"
                class="block-paragraph"
              >
                <div v-if="paragraph.title" class="block-paragraph-title">
                  <strong>{{ paragraph.title }}</strong>
                </div>
                <div v-if="paragraph.textHtml" class="block-paragraph-text" v-html="paragraph.textHtml"></div>
                <div v-else class="block-paragraph-text">
                  {{ paragraph.text }}
                </div>
              </div>
            </div>
          </div>
          <div
            class="block-images"
            :class="image.imagePosition === 'bottom-right' ? 'block-images--bottom-right' : ''"
            :style="{ backgroundColor: image.text.backgroundColor }"
          >
            <img :src="image.image.src" :alt="project.title" />
          </div>
        </div>

        <img
          v-else
          :src="image.src"
          :alt="project.title"
          class="clickable-img"
          @click="openModal(image)"
        />
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

.project {
  background-color: $bg-light;
}

.project-description {
  text-align: center;
  max-width: 680px;
  margin: 0 auto 32px;
  padding: 0 16px;
  color: $text-color;
  font-size: 1rem;
  line-height: 1.6;
}

.image-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 4px;
  padding: 0 10px;

  @media (min-width: 600px) {
    grid-template-columns: repeat(2, 1fr);
  }

  @media (min-width: 900px) {
    grid-template-columns: repeat(3, 1fr);
  }

  .image-item {
    text-align: center;

    img {
      width: 100%;
      height: auto;
      display: block;
    }
  }

  .image-item--text-block {
    grid-column: 1 / -1;
  }
}

.clickable-img {
  cursor: pointer;
  transition: filter 0.3s ease;

  @media (hover: hover) {
    &:hover {
      filter: brightness(0.92);
    }
  }

  &:active {
    transform: scale(0.98);
    transition: transform 0.12s ease;
  }
}

.text-image-block {
  display: flex;
  flex-direction: column;
  width: 100%;

  @media (min-width: 900px) {
    flex-direction: row;
    align-items: stretch;
    min-height: 100vh;

    &.text-right {
      flex-direction: row-reverse;
    }

    &.text-left {
      flex-direction: row;
    }
  }
}

.block-text {
  font-size: 0.95rem;
  line-height: 1.7;
  padding: 40px 20px;
  display: flex;
  flex-direction: column;
  box-sizing: border-box;

  @media (min-width: 600px) {
    padding: 56px 32px;
  }

  @media (min-width: 900px) {
    flex: 0 0 55%;
    min-height: 100vh;
    padding: 80px 64px 64px;
  }
}

.block-header {
  margin-bottom: 28px;
}

.block-subtitle {
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  line-height: 1.2;
  margin-bottom: 6px;
}

.block-title {
  font-size: 1.8rem;
  letter-spacing: 0.02em;
  line-height: 1.05;

  @media (min-width: 900px) {
    font-size: 2.4rem;
  }
}

.block-paragraphs {
  column-count: 1;
  flex: 1;

  @media (min-width: 900px) {
    column-count: 2;
    column-gap: 32px;
  }
}

.block-paragraph {
  break-inside: avoid;
  margin-bottom: 24px;
}

.block-paragraph-title {
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-size: 0.8rem;
}

.block-paragraph-text {
  font-size: 0.95rem;
  line-height: 1.7;

  :deep(p) {
    margin: 0 0 12px;
  }

  :deep(ul), :deep(ol) {
    margin: 0 0 12px;
    padding-left: 20px;
  }
}

.block-images {
  min-height: 50vh;
  display: flex;
  align-items: center;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  @media (min-width: 900px) {
    align-self: stretch;
    flex: 1 1 auto;
    min-height: 0;
  }
}

.block-images--bottom-right {
  justify-content: flex-end;
  align-items: flex-end;
  position: relative;
  min-height: 40vh;

  img {
    position: absolute;
    right: 24px;
    bottom: 24px;
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
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

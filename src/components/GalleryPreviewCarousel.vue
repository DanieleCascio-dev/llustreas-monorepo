<script setup lang="ts">
import { useProjectStore } from "../store.js";
import { computed, onMounted, onBeforeUnmount, ref, watch, nextTick } from "vue";
import ImageModal from "./ImageModal.vue";
import { cloudinaryThumb } from "../utils/cloudinary";

const projectStore = useProjectStore();

onMounted(async () => {
  projectStore.fetchGalleryPreview()
  checkMobile()
  window.addEventListener('resize', checkMobile)
  await nextTick()
  if (isMobile.value) setupDotObserver()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkMobile)
  if (dotObserver) dotObserver.disconnect()
})

const previewImages = computed(() => projectStore.galleryPreview)

function thumbUrl(url: string): string {
  return cloudinaryThumb(url, 400)
}

const isMobile = ref(window.innerWidth < 576)
function checkMobile() {
  isMobile.value = window.innerWidth < 576
}

const loadedImages = ref(new Set())

function onImageLoad(id: number) {
  loadedImages.value = new Set(loadedImages.value).add(id)
}

const scrollContainerRef = ref(null)
const activeDot = ref(0)
let dotObserver: IntersectionObserver | null = null

function setupDotObserver() {
  if (dotObserver) dotObserver.disconnect()
  const container = scrollContainerRef.value
  if (!container) return

  const cards = container.querySelectorAll('.carousel-card')
  if (!cards.length) return

  dotObserver = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
          const idx = Array.from(cards).indexOf(entry.target as Element)
          if (idx >= 0) activeDot.value = idx
        }
      }
    },
    { root: container, threshold: 0.5 }
  )
  cards.forEach(card => dotObserver!.observe(card))
}

function scrollToDot(idx: number) {
  const container = scrollContainerRef.value
  if (!container) return
  const cards = container.querySelectorAll('.carousel-card')
  if (cards[idx]) {
    cards[idx].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' })
  }
}

watch([isMobile, previewImages], async () => {
  if (isMobile.value && previewImages.value.length) {
    await nextTick()
    setupDotObserver()
  }
})

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
  <div class="carousel-section" id="gallery-preview">
    <h1 v-reveal class="carousel-title title-custom">Illustrazioni</h1>

    <!-- Mobile: scroll nativo con snap -->
    <div v-if="isMobile" ref="scrollContainerRef" class="carousel-scroll">
      <div
        v-for="item in previewImages"
        :key="'m-' + item.id"
        class="carousel-card"
        :class="{ loaded: loadedImages.has(item.id) }"
        tabindex="0"
        role="button"
        :aria-label="item.title || 'Apri immagine'"
        @click="openModal(item)"
        @keydown.enter="openModal(item)"
      >
        <img :src="thumbUrl(item.url)" :alt="item.title" loading="lazy" @load="onImageLoad(item.id)" />
        <span class="carousel-card-title">{{ item.title }}</span>
      </div>
    </div>
    <div v-if="isMobile && previewImages.length" class="dot-indicator">
      <button
        v-for="(item, idx) in previewImages"
        :key="'dot-' + item.id"
        class="dot"
        :class="{ active: idx === activeDot }"
        :aria-label="'Vai a immagine ' + (idx + 1)"
        @click="scrollToDot(idx)"
      />
    </div>

    <!-- Desktop/Tablet: marquee infinito -->
    <div v-else class="marquee-wrap">
      <div class="marquee-track">
        <div
          v-for="item in previewImages"
          :key="'a-' + item.id"
          class="carousel-card"
          :class="{ loaded: loadedImages.has(item.id) }"
          tabindex="0"
          role="button"
          :aria-label="item.title || 'Apri immagine'"
          @click="openModal(item)"
          @keydown.enter="openModal(item)"
        >
          <img :src="thumbUrl(item.url)" :alt="item.title" loading="lazy" @load="onImageLoad(item.id)" />
          <span class="carousel-card-title">{{ item.title }}</span>
        </div>
        <!-- Duplicato per loop seamless -->
        <div
          v-for="item in previewImages"
          :key="'b-' + item.id"
          class="carousel-card"
          :class="{ loaded: loadedImages.has(item.id) }"
          aria-hidden="true"
          @click="openModal(item)"
        >
          <img :src="thumbUrl(item.url)" :alt="item.title" loading="lazy" />
          <span class="carousel-card-title">{{ item.title }}</span>
        </div>
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

.carousel-section {
  background-color: $text-violet;
  padding: 40px 0 60px;
  overflow: hidden;

  @media (min-width: 768px) {
    padding: 48px 0 72px;
  }
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

/* ── Mobile: scroll orizzontale nativo ── */
.carousel-scroll {
  display: flex;
  gap: 12px;
  padding: 0 16px;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;

  &::-webkit-scrollbar { display: none; }
}

.carousel-scroll .carousel-card {
  flex: 0 0 75vw;
  max-width: 320px;
  scroll-snap-align: center;
}

/* ── Dot indicator mobile ── */
.dot-indicator {
  display: flex;
  justify-content: center;
  gap: 8px;
  padding: 16px 0 4px;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  border: none;
  padding: 0;
  background: rgba(255, 255, 255, 0.35);
  cursor: pointer;
  transition: background 0.3s ease, transform 0.3s ease;
  -webkit-tap-highlight-color: transparent;

  &.active {
    background: white;
    transform: scale(1.3);
  }
}

/* ── Desktop/Tablet: marquee infinito ── */
.marquee-wrap {
  width: 100%;
  overflow: hidden;
  mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
  -webkit-mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);

  &:hover .marquee-track {
    animation-play-state: paused;
  }
}

.marquee-track {
  display: flex;
  gap: 16px;
  width: max-content;
  animation: marquee var(--marquee-speed, 50s) linear infinite;

  @media (min-width: 992px) {
    gap: 20px;
  }
}

@keyframes marquee {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

/* ── Card immagine ── */
.carousel-card {
  position: relative;
  flex-shrink: 0;
  border-radius: 14px;
  overflow: hidden;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  transition: box-shadow 0.35s ease;
  background: linear-gradient(110deg, rgba(255,255,255,0.08) 30%, rgba(255,255,255,0.18) 50%, rgba(255,255,255,0.08) 70%);
  background-size: 200% 100%;
  animation: shimmer 1.6s ease-in-out infinite;

  &.loaded {
    animation: none;
    background: none;
  }

  &:focus-visible {
    outline: 2px solid white;
    outline-offset: 2px;
  }

  img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: transform 0.4s ease, filter 0.4s ease, opacity 0.4s ease;
  }

  &.loaded img {
    opacity: 1;
  }
}

@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Dimensioni responsive delle card nel marquee */
.marquee-track .carousel-card {
  width: 280px;
  height: 280px;

  @media (min-width: 768px) {
    width: 320px;
    height: 320px;
  }

  @media (min-width: 992px) {
    width: 380px;
    height: 380px;
  }
}

/* Hover: zoom immagine dentro la card (bordi tondeggianti preservati) */
@media (hover: hover) {
  .carousel-card:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35);
    z-index: 2;
  }

  .carousel-card:hover img {
    transform: scale(1.08);
    filter: brightness(1.08);
  }
}

.carousel-card:active img {
  transform: scale(0.97);
  transition: transform 0.12s ease;
}

/* Titolo overlay sulla card */
.carousel-card-title {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 24px 14px 12px;
  background: linear-gradient(transparent, rgba(0, 0, 0, 0.55));
  color: white;
  font-family: "Young Serif", serif;
  font-size: 1rem;
  line-height: 1.2;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.3s ease;

  @media (min-width: 768px) {
    font-size: 1.1rem;
  }
}

/* Desktop: titolo nascosto, visibile solo nel modale */

/* Su mobile il titolo e' sempre visibile */
.carousel-scroll .carousel-card-title {
  opacity: 1;
}

/* Modal transitions */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.4s;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>

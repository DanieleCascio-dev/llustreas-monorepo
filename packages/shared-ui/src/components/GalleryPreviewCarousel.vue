<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { cloudinaryThumb } from '../utils/cloudinary.js'

const props = defineProps({
  images: { type: Array, required: true },
  mobileScroll: { type: Boolean, default: false },
  marqueeSpeed: { type: String, default: '50s' },
  thumbWidth: { type: Number, default: 400 },
  sectionRadius: { type: String, default: '0' },
  forceMode: { type: String, default: null },
})

const emit = defineEmits(['imageClick'])

const isMobile = ref(false)
function checkMobile() {
  if (props.forceMode === 'mobile') { isMobile.value = true; return }
  if (props.forceMode === 'desktop') { isMobile.value = false; return }
  isMobile.value = props.mobileScroll && window.innerWidth < 576
}

const showMobileScroll = computed(() => isMobile.value)

function thumbUrl(url) {
  return cloudinaryThumb(url, props.thumbWidth)
}

const loadedImages = ref(new Set())
function onImageLoad(id) {
  loadedImages.value = new Set(loadedImages.value).add(id)
}

// Mobile dot indicator
const scrollContainerRef = ref(null)
const activeDot = ref(0)
let dotObserver = null

function setupDotObserver() {
  if (dotObserver) dotObserver.disconnect()
  const container = scrollContainerRef.value
  if (!container) return
  const cards = container.querySelectorAll('.gpc-card')
  if (!cards.length) return
  dotObserver = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
          const idx = Array.from(cards).indexOf(entry.target)
          if (idx >= 0) activeDot.value = idx
        }
      }
    },
    { root: container, threshold: 0.5 },
  )
  cards.forEach((card) => dotObserver.observe(card))
}

function scrollToDot(idx) {
  const container = scrollContainerRef.value
  if (!container) return
  const cards = container.querySelectorAll('.gpc-card')
  if (cards[idx]) {
    cards[idx].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' })
  }
}

onMounted(async () => {
  checkMobile()
  if (props.mobileScroll) {
    window.addEventListener('resize', checkMobile)
  }
  await nextTick()
  if (isMobile.value) setupDotObserver()
})

onBeforeUnmount(() => {
  if (props.mobileScroll) {
    window.removeEventListener('resize', checkMobile)
  }
  if (dotObserver) dotObserver.disconnect()
})

watch(() => props.forceMode, () => {
  checkMobile()
})

watch([() => isMobile.value, () => props.images], async () => {
  if (isMobile.value && props.images.length) {
    await nextTick()
    setupDotObserver()
  }
})
</script>

<template>
  <div
    v-if="images.length"
    class="gpc-section"
    :style="{
      '--gpc-section-radius': sectionRadius,
      '--gpc-marquee-speed': marqueeSpeed,
    }"
  >
    <slot name="title">
      <h1 class="gpc-title">Illustrazioni</h1>
    </slot>

    <!-- Mobile: native horizontal scroll -->
    <div v-if="showMobileScroll" ref="scrollContainerRef" class="gpc-scroll">
      <div
        v-for="item in images"
        :key="'m-' + item.id"
        class="gpc-card"
        :class="{ loaded: loadedImages.has(item.id) }"
        tabindex="0"
        role="button"
        :aria-label="item.title || 'Apri immagine'"
        @click="emit('imageClick', item)"
        @keydown.enter="emit('imageClick', item)"
      >
        <img
          :src="thumbUrl(item.url)"
          :alt="item.title"
          loading="lazy"
          @load="onImageLoad(item.id)"
        />
        <span class="gpc-card-title">{{ item.title }}</span>
      </div>
    </div>
    <div v-if="showMobileScroll && images.length" class="gpc-dots">
      <button
        v-for="(item, idx) in images"
        :key="'dot-' + item.id"
        class="gpc-dot"
        :class="{ active: idx === activeDot }"
        :aria-label="'Vai a immagine ' + (idx + 1)"
        @click="scrollToDot(idx)"
      />
    </div>

    <!-- Desktop / always: infinite marquee -->
    <div v-else class="gpc-marquee-wrap">
      <div class="gpc-marquee-track">
        <div
          v-for="item in images"
          :key="'a-' + item.id"
          class="gpc-card"
          :class="{ loaded: loadedImages.has(item.id) }"
          tabindex="0"
          role="button"
          :aria-label="item.title || 'Apri immagine'"
          @click="emit('imageClick', item)"
          @keydown.enter="emit('imageClick', item)"
        >
          <img
            :src="thumbUrl(item.url)"
            :alt="item.title"
            loading="lazy"
            @load="onImageLoad(item.id)"
          />
          <span class="gpc-card-title">{{ item.title }}</span>
        </div>
        <div
          v-for="item in images"
          :key="'b-' + item.id"
          class="gpc-card"
          :class="{ loaded: loadedImages.has(item.id) }"
          aria-hidden="true"
          @click="emit('imageClick', item)"
        >
          <img
            :src="thumbUrl(item.url)"
            :alt="item.title"
            loading="lazy"
          />
          <span class="gpc-card-title">{{ item.title }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.gpc-section {
  background-color: var(--gpc-bg, #8472ac);
  color: var(--gpc-text, white);
  padding: 40px 0 60px;
  border-radius: var(--gpc-section-radius, 0);
  overflow: hidden;
}

@media (min-width: 768px) {
  .gpc-section {
    padding: 48px 0 72px;
  }
}

.gpc-title {
  color: inherit;
  text-align: center;
  margin: 0 0 28px;
  padding: 0 16px;
}

@media (min-width: 768px) {
  .gpc-title {
    margin-bottom: 36px;
  }
}

/* ── Mobile: native horizontal scroll ── */
.gpc-scroll {
  display: flex;
  gap: 12px;
  padding: 0 16px;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.gpc-scroll::-webkit-scrollbar {
  display: none;
}

.gpc-scroll .gpc-card {
  flex: 0 0 75vw;
  max-width: 320px;
  scroll-snap-align: center;
}

/* ── Dot indicator ── */
.gpc-dots {
  display: flex;
  justify-content: center;
  gap: 8px;
  padding: 16px 0 4px;
}

.gpc-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  border: none;
  padding: 0;
  background: rgba(255, 255, 255, 0.35);
  cursor: pointer;
  transition: background 0.3s ease, transform 0.3s ease;
  -webkit-tap-highlight-color: transparent;
}

.gpc-dot.active {
  background: white;
  transform: scale(1.3);
}

/* ── Desktop: infinite marquee ── */
.gpc-marquee-wrap {
  width: 100%;
  overflow: hidden;
  mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
  -webkit-mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
}

.gpc-marquee-wrap:hover .gpc-marquee-track {
  animation-play-state: paused;
}

.gpc-marquee-track {
  display: flex;
  gap: 16px;
  width: max-content;
  animation: gpc-marquee var(--gpc-marquee-speed, 50s) linear infinite;
}

@media (min-width: 992px) {
  .gpc-marquee-track {
    gap: 20px;
  }
}

@keyframes gpc-marquee {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-50%);
  }
}

/* ── Card ── */
.gpc-card {
  position: relative;
  flex-shrink: 0;
  border-radius: 14px;
  overflow: hidden;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  transition: box-shadow 0.35s ease;
  background: linear-gradient(
    110deg,
    rgba(255, 255, 255, 0.08) 30%,
    rgba(255, 255, 255, 0.18) 50%,
    rgba(255, 255, 255, 0.08) 70%
  );
  background-size: 200% 100%;
  animation: gpc-shimmer 1.6s ease-in-out infinite;
}

.gpc-card.loaded {
  animation: none;
  background: none;
}

.gpc-card:focus-visible {
  outline: 2px solid white;
  outline-offset: 2px;
}

.gpc-card img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: 0;
  transition: transform 0.4s ease, filter 0.4s ease, opacity 0.4s ease;
}

.gpc-card.loaded img {
  opacity: 1;
}

@keyframes gpc-shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Card sizes in marquee */
.gpc-marquee-track .gpc-card {
  width: 280px;
  height: 280px;
}

@media (min-width: 768px) {
  .gpc-marquee-track .gpc-card {
    width: 320px;
    height: 320px;
  }
}

@media (min-width: 992px) {
  .gpc-marquee-track .gpc-card {
    width: 380px;
    height: 380px;
  }
}

/* Hover effects */
@media (hover: hover) {
  .gpc-card:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35);
    z-index: 2;
  }

  .gpc-card:hover img {
    transform: scale(1.08);
    filter: brightness(1.08);
  }
}

.gpc-card:active img {
  transform: scale(0.97);
  transition: transform 0.12s ease;
}

/* Title overlay */
.gpc-card-title {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 24px 14px 12px;
  background: linear-gradient(transparent, rgba(0, 0, 0, 0.55));
  color: white;
  font-size: 1rem;
  line-height: 1.2;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.3s ease;
}

@media (min-width: 768px) {
  .gpc-card-title {
    font-size: 1.1rem;
  }
}

.gpc-scroll .gpc-card-title {
  opacity: 1;
}
</style>

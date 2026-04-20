<script setup>
import { computed, nextTick, onMounted, onUnmounted, watch, ref } from 'vue'

const props = defineProps({
  posts: { type: Array, required: true },
  draggable: { type: Boolean, default: false },
  mobileScroll: { type: Boolean, default: false },
  marqueeSpeed: { type: String, default: '80s' },
  sectionRadius: { type: String, default: '0' },
  forceMode: { type: String, default: null },
})

const emit = defineEmits(['reorder'])

const dragging = ref(null)
const dropTarget = ref(null)
const activeIndex = ref(0)
const isViewportMobile = ref(false)

const normalizedPosts = computed(() =>
  (props.posts || [])
    .map((post) => ({
      id: post.id,
      url: normalizeInstagramUrl(post.url),
    }))
    .filter((post) => Boolean(post.url))
)

const isMobileCarousel = computed(() => {
  if (props.forceMode === 'mobile') return true
  if (props.forceMode === 'desktop') return false
  return isViewportMobile.value
})

const canNavigate = computed(() => isMobileCarousel.value && normalizedPosts.value.length > 1)

function normalizeInstagramUrl(url) {
  if (!url) return ''

  try {
    const parsed = new URL(url)
    const shortcodeMatch = parsed.pathname.match(/^\/(?:p|reel)\/([A-Za-z0-9_-]+)/)
    if (!shortcodeMatch) return url

    return `${parsed.origin}${parsed.pathname.replace(/\/?$/, '/')}`
  } catch {
    return url
  }
}

function loadInstagramEmbedScript() {
  if (typeof window === 'undefined') return

  const processEmbeds = () => {
    if (window.instgrm?.Embeds?.process) {
      window.instgrm.Embeds.process()
    }
  }

  const existingScript = document.querySelector('script[src="//www.instagram.com/embed.js"], script[src="https://www.instagram.com/embed.js"]')
  if (existingScript) {
    processEmbeds()
    return
  }

  const script = document.createElement('script')
  script.async = true
  script.src = 'https://www.instagram.com/embed.js'
  script.onload = processEmbeds
  document.body.appendChild(script)
}

async function processEmbeds() {
  await nextTick()
  loadInstagramEmbedScript()
}

function checkViewport() {
  if (typeof window === 'undefined') return
  isViewportMobile.value = window.innerWidth <= 991
}

onMounted(() => {
  checkViewport()
  processEmbeds()
  if (typeof window !== 'undefined') {
    window.addEventListener('resize', checkViewport)
  }
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', checkViewport)
  }
})

watch(normalizedPosts, processEmbeds, { deep: true })
watch(() => props.forceMode, processEmbeds)

watch(
  normalizedPosts,
  (posts) => {
    if (activeIndex.value > posts.length - 1) {
      activeIndex.value = Math.max(0, posts.length - 1)
    }
  },
  { deep: true }
)

watch(activeIndex, processEmbeds)

function goToPost(index) {
  const total = normalizedPosts.value.length
  if (!total) return
  activeIndex.value = (index + total) % total
}

function previousPost() {
  goToPost(activeIndex.value - 1)
}

function nextPost() {
  goToPost(activeIndex.value + 1)
}

function onDragStart(event, postId) {
  if (!props.draggable) return
  dragging.value = postId
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/plain', String(postId))
}

function onDragOver(event, postId) {
  if (!props.draggable) return
  event.preventDefault()
  if (postId !== dragging.value) {
    dropTarget.value = postId
  }
}

function onDrop(targetId) {
  if (!props.draggable) return
  const fromId = dragging.value
  dropTarget.value = null
  dragging.value = null
  if (fromId === null || fromId === targetId) return
  emit('reorder', fromId, targetId)
}

function onDragEnd() {
  dragging.value = null
  dropTarget.value = null
}

function onDragLeave() {
  dropTarget.value = null
}
</script>

<template>
  <div
    v-if="normalizedPosts.length"
    class="ig-section"
    :class="{
      'ig-section--mobile': forceMode === 'mobile',
      'ig-section--carousel': isMobileCarousel,
      'ig-section--draggable': draggable,
    }"
    :style="{ '--ig-section-radius': sectionRadius }"
  >
    <slot name="title">
      <h1 class="ig-title">
        <a
          href="https://www.instagram.com/illust.reas/"
          target="_blank"
          rel="noopener"
          class="ig-title-link"
        >
          I miei post
          <svg class="ig-icon" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
          </svg>
        </a>
      </h1>
    </slot>

    <div class="ig-carousel-shell">
      <button
        v-if="canNavigate"
        type="button"
        class="ig-carousel-button ig-carousel-button--prev"
        aria-label="Post precedente"
        @click="previousPost"
      >
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M15.5 4.5 8 12l7.5 7.5" />
        </svg>
      </button>

      <button
        v-if="canNavigate"
        type="button"
        class="ig-carousel-button ig-carousel-button--next"
        aria-label="Post successivo"
        @click="nextPost"
      >
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="m8.5 4.5 7.5 7.5-7.5 7.5" />
        </svg>
      </button>

      <div
        class="ig-post-grid"
        :style="isMobileCarousel ? { '--ig-active-index': activeIndex } : null"
      >
      <article
        v-for="post in normalizedPosts"
        :key="post.id"
        class="ig-post-card"
        :class="{ 'ig-post-card--drop': dropTarget === post.id }"
        :draggable="draggable"
        @dragstart="onDragStart($event, post.id)"
        @dragover="onDragOver($event, post.id)"
        @dragleave="onDragLeave"
        @drop="onDrop(post.id)"
        @dragend="onDragEnd"
      >
        <div v-if="draggable" class="ig-drag-handle" aria-hidden="true">::</div>
        <blockquote
          class="instagram-media"
          :data-instgrm-permalink="post.url"
          data-instgrm-version="14"
        >
          <a :href="post.url" target="_blank" rel="noopener noreferrer">View this post on Instagram</a>
        </blockquote>
      </article>
      </div>
    </div>

    <div v-if="canNavigate" class="ig-carousel-dots" aria-label="Seleziona post Instagram">
      <button
        v-for="(post, index) in normalizedPosts"
        :key="`dot-${post.id}`"
        type="button"
        class="ig-carousel-dot"
        :class="{ 'ig-carousel-dot--active': index === activeIndex }"
        :aria-label="`Vai al post ${index + 1}`"
        :aria-current="index === activeIndex ? 'true' : null"
        @click="goToPost(index)"
      />
    </div>
  </div>
</template>

<style scoped>
.ig-section {
  background-color: var(--ig-bg, #8472ac);
  color: var(--ig-text, white);
  padding: 40px 16px 60px;
  border-radius: var(--ig-section-radius, 0);
  overflow: hidden;
}

@media (min-width: 768px) {
  .ig-section {
    padding: 48px 24px 72px;
  }
}

.ig-title {
  color: inherit;
  text-align: center;
  margin: 0 0 28px;
  padding: 0 16px;
}

@media (min-width: 768px) {
  .ig-title {
    margin-bottom: 36px;
  }
}

.ig-title-link {
  color: inherit;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  transition: opacity 0.2s;
}

@media (hover: hover) {
  .ig-title-link:hover {
    opacity: 0.75;
  }
}

.ig-icon {
  width: 0.7em;
  height: 0.7em;
}

.ig-post-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  align-items: start;
  gap: 20px;
  max-width: 1180px;
  margin: 0 auto;
}

.ig-carousel-shell {
  position: relative;
  max-width: 1180px;
  margin: 0 auto;
}

.ig-carousel-button {
  display: none;
}

.ig-carousel-dots {
  display: none;
}

.ig-section--carousel .ig-carousel-shell {
  overflow: hidden;
  width: min(100%, 470px);
  max-width: calc(100vw - 32px);
}

.ig-section--carousel .ig-post-grid {
  display: flex;
  gap: 0;
  max-width: none;
  transform: translateX(calc(var(--ig-active-index, 0) * -100%));
  transition: transform 0.35s ease;
}

.ig-section--carousel .ig-post-card {
  flex: 0 0 100%;
  background: transparent;
  box-shadow: none;
  display: flex;
  justify-content: center;
}

.ig-section--carousel .ig-carousel-button {
  position: absolute;
  top: 50%;
  z-index: 3;
  display: grid;
  width: 38px;
  height: 38px;
  place-items: center;
  border: 0;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.94);
  color: #262626;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.22);
  transform: translateY(-50%);
  cursor: pointer;
}

.ig-section--carousel .ig-carousel-button--prev {
  left: 8px;
}

.ig-section--carousel .ig-carousel-button--next {
  right: 8px;
}

.ig-section--carousel .ig-carousel-button svg {
  width: 22px;
  height: 22px;
  fill: none;
  stroke: currentColor;
  stroke-width: 2.4;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.ig-section--carousel .ig-carousel-dots {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-top: 18px;
}

.ig-section--carousel .ig-carousel-dot {
  width: 9px;
  height: 9px;
  padding: 0;
  border: 0;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.45);
  cursor: pointer;
}

.ig-section--carousel .ig-carousel-dot--active {
  background: #fff;
}

@media (max-width: 991px) {
  .ig-section:not(.ig-section--carousel) .ig-post-grid {
    grid-template-columns: 1fr;
  }
}

.ig-post-card {
  position: relative;
  min-width: 0;
  border: 2px solid transparent;
  border-radius: 8px;
  background: #fff;
  color: #262626;
  overflow: hidden;
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.16);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.ig-section--draggable .ig-post-card {
  cursor: grab;
}

.ig-section--draggable .ig-post-card:active {
  cursor: grabbing;
}

.ig-post-card--drop {
  border-color: rgba(255, 255, 255, 0.9);
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.2);
}

@media (hover: hover) {
  .ig-post-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.22);
  }
}

.ig-drag-handle {
  position: absolute;
  top: 8px;
  left: 8px;
  z-index: 2;
  width: 28px;
  height: 28px;
  border-radius: 999px;
  background: rgba(0, 0, 0, 0.55);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  letter-spacing: 1px;
  pointer-events: none;
}

.instagram-media {
  max-width: 100% !important;
  min-width: 0 !important;
  width: 100% !important;
  margin: 0 !important;
  border: 0 !important;
  box-shadow: none !important;
}

.ig-section--carousel .instagram-media {
  margin: 0 auto !important;
}
</style>

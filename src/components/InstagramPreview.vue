<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'

const props = defineProps({
  posts: { type: Array, required: true },
})

const emit = defineEmits(['reorder'])

const dragging = ref(null)
const dropTarget = ref(null)

function onDragStart(e, postId) {
  dragging.value = postId
  e.dataTransfer.effectAllowed = 'move'
}

function onDragOver(e, postId) {
  e.preventDefault()
  if (postId !== dragging.value) dropTarget.value = postId
}

function onDragLeave() {
  dropTarget.value = null
}

function onDrop(targetId) {
  dropTarget.value = null
  if (dragging.value === null || dragging.value === targetId) return
  emit('reorder', dragging.value, targetId)
  dragging.value = null
}

function onDragEnd() {
  dragging.value = null
  dropTarget.value = null
}

function loadEmbedScript() {
  if (window.instgrm) {
    window.instgrm.Embeds.process()
    return
  }
  if (document.querySelector('script[src*="instagram.com/embed.js"]')) return
  const script = document.createElement('script')
  script.src = '//www.instagram.com/embed.js'
  script.async = true
  script.onload = () => { if (window.instgrm) window.instgrm.Embeds.process() }
  document.head.appendChild(script)
}

function scheduleEmbed() {
  nextTick(() => setTimeout(() => {
    if (window.instgrm) window.instgrm.Embeds.process()
  }, 150))
}

onMounted(() => {
  if (props.posts.length) loadEmbedScript()
})

onBeforeUnmount(() => {})

watch(() => props.posts, scheduleEmbed, { deep: true })
</script>

<template>
  <div v-if="posts.length" class="ig-section">
    <h1 class="ig-title">
      <a href="https://www.instagram.com/illust.reas/" target="_blank" rel="noopener" class="ig-title-link">
        I miei post
        <svg class="ig-icon" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
        </svg>
      </a>
    </h1>

    <div class="ig-marquee-wrap">
      <div class="ig-marquee-track" :class="{ 'ig-marquee-paused': dragging }">
        <div
          v-for="post in posts"
          :key="'a-' + post.id"
          class="ig-embed-card"
          :class="{
            'ig-embed-card--dragging': dragging === post.id,
            'ig-embed-card--drop': dropTarget === post.id,
          }"
          draggable="true"
          @dragstart="onDragStart($event, post.id)"
          @dragover="onDragOver($event, post.id)"
          @dragleave="onDragLeave"
          @drop="onDrop(post.id)"
          @dragend="onDragEnd"
        >
          <blockquote
            class="instagram-media"
            :data-instgrm-permalink="post.url"
            data-instgrm-version="14"
          />
        </div>
        <div
          v-for="post in posts"
          :key="'b-' + post.id"
          class="ig-embed-card"
          :class="{
            'ig-embed-card--dragging': dragging === post.id,
            'ig-embed-card--drop': dropTarget === post.id,
          }"
          aria-hidden="true"
          draggable="true"
          @dragstart="onDragStart($event, post.id)"
          @dragover="onDragOver($event, post.id)"
          @dragleave="onDragLeave"
          @drop="onDrop(post.id)"
          @dragend="onDragEnd"
        >
          <blockquote
            class="instagram-media"
            :data-instgrm-permalink="post.url"
            data-instgrm-version="14"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ig-section {
  background-color: #8472ac;
  padding: 40px 0 60px;
  border-radius: 12px;
  overflow: hidden;
}

.ig-title {
  color: white;
  text-align: center;
  margin: 0 0 28px;
  font-size: 2.2rem;
  font-weight: 700;
  padding: 0 16px;
}

.ig-title-link {
  color: inherit;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  transition: opacity 0.2s;
}
.ig-title-link:hover { opacity: 0.75; }

.ig-icon {
  width: 0.7em;
  height: 0.7em;
}

.ig-marquee-wrap {
  width: 100%;
  overflow: hidden;
  mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
  -webkit-mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
}

.ig-marquee-wrap:hover .ig-marquee-track {
  animation-play-state: paused;
}

.ig-marquee-track {
  display: flex;
  gap: 20px;
  width: max-content;
  animation: ig-marquee 80s linear infinite;
}

.ig-marquee-track.ig-marquee-paused {
  animation-play-state: paused;
}

@keyframes ig-marquee {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

.ig-embed-card {
  flex: 0 0 auto;
  width: 320px;
  cursor: grab;
  border: 3px solid transparent;
  border-radius: 14px;
  transition: border-color 0.2s, opacity 0.2s, transform 0.2s, box-shadow 0.2s;
}
.ig-embed-card:active { cursor: grabbing; }

.ig-embed-card--dragging {
  opacity: 0.4;
}
.ig-embed-card--drop {
  border-color: white;
  box-shadow: 0 0 0 3px rgba(255,255,255,.5);
  transform: scale(1.03);
}

.ig-embed-card :deep(iframe) {
  border-radius: 12px !important;
  min-width: 100% !important;
  pointer-events: none;
}

.ig-embed-card :deep(.instagram-media) {
  margin: 0 !important;
  width: 100% !important;
  min-width: unset !important;
  max-width: 100% !important;
}
</style>

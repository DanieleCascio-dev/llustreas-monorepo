<script setup>
import { useProjectStore } from "../store.js"
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from "vue"

const store = useProjectStore()
const posts = computed(() => store.instagramPosts)

const isMobile = ref(window.innerWidth < 768)

function checkMobile() {
  isMobile.value = window.innerWidth < 768
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
  }, 200))
}

onMounted(() => {
  window.addEventListener('resize', checkMobile)
  if (posts.value.length) loadEmbedScript()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkMobile)
})

watch(posts, scheduleEmbed)
watch(isMobile, scheduleEmbed)
</script>

<template>
  <div v-if="posts.length" class="ig-section">
    <h1 v-reveal class="ig-title title-custom">
      <a href="https://www.instagram.com/illust.reas/" target="_blank" rel="noopener" class="ig-title-link">
        I miei post
        <svg class="ig-icon" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
        </svg>
      </a>
    </h1>

    <!-- Mobile: native horizontal scroll -->
    <div v-if="isMobile" class="ig-scroll">
      <div v-for="post in posts" :key="'m-' + post.id" class="ig-embed-card">
        <blockquote
          class="instagram-media"
          :data-instgrm-permalink="post.url"
          data-instgrm-version="14"
        />
      </div>
    </div>

    <!-- Desktop: infinite marquee -->
    <div v-else class="ig-marquee-wrap">
      <div class="ig-marquee-track">
        <div v-for="post in posts" :key="'a-' + post.id" class="ig-embed-card">
          <blockquote
            class="instagram-media"
            :data-instgrm-permalink="post.url"
            data-instgrm-version="14"
          />
        </div>
        <div v-for="post in posts" :key="'b-' + post.id" class="ig-embed-card" aria-hidden="true">
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

<style scoped lang="scss">
@use "../style/partials/variables" as *;

.ig-section {
  background-color: $text-violet;
  padding: 40px 0 60px;
  overflow: hidden;

  @media (min-width: 768px) {
    padding: 48px 0 72px;
  }
}

.ig-title {
  color: white;
  text-align: center;
  margin-bottom: 28px;
  padding: 0 16px;

  @media (min-width: 768px) {
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

  &:hover { opacity: 0.75; }
}

.ig-icon {
  width: 0.7em;
  height: 0.7em;
}

/* ── Mobile: native horizontal scroll ── */
.ig-scroll {
  display: flex;
  gap: 16px;
  padding: 0 16px;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;

  &::-webkit-scrollbar { display: none; }

  .ig-embed-card {
    flex: 0 0 85vw;
    max-width: 400px;
    min-width: 280px;
    scroll-snap-align: center;
  }
}

/* ── Desktop: infinite marquee ── */
.ig-marquee-wrap {
  width: 100%;
  overflow: hidden;
  mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
  -webkit-mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);

  &:hover .ig-marquee-track {
    animation-play-state: paused;
  }
}

.ig-marquee-track {
  display: flex;
  gap: 20px;
  width: max-content;
  animation: ig-marquee 80s linear infinite;

  .ig-embed-card {
    flex: 0 0 auto;
    width: 320px;

    @media (min-width: 1200px) {
      width: 350px;
    }
  }
}

@keyframes ig-marquee {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

/* ── Embed overrides ── */
.ig-embed-card :deep(iframe) {
  border-radius: 12px !important;
  min-width: 100% !important;
}

.ig-embed-card :deep(.instagram-media) {
  margin: 0 !important;
  width: 100% !important;
  min-width: unset !important;
  max-width: 100% !important;
}
</style>

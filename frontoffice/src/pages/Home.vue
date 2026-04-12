<script setup>
/*** PAGINA HOME — carica dati API prima di mostrare il contenuto ***/
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useProjectStore } from '../store'
import AppHero from "../components/AppHero.vue";
import ProjectsPreview from "../components/ProjectsPreview.vue";
import GalleryPreviewCarousel from "../components/GalleryPreviewCarousel.vue";
import GalleryPreview from "../components/GalleryPreview.vue";
import InstagramCarousel from "../components/InstagramCarousel.vue";
import AboutMePreview from "../components/AboutMePreview.vue";
import FullPageLoader from "../components/FullPageLoader.vue";

const store = useProjectStore()
const loading = ref(true)

const galleryComponent = computed(() =>
  store.galleryPreviewMode === 'grid' ? GalleryPreview : GalleryPreviewCarousel
)

const galleryWrapRef = ref(null)
const aboutWrapRef = ref(null)
const flowerRedProgress = ref(0)
const flowerYellowProgress = ref(0)
let ticking = false

function updateFlowerPositions() {
  const vh = window.innerHeight

  const gallery = galleryWrapRef.value
  if (gallery) {
    const rect = gallery.getBoundingClientRect()
    const start = vh
    const end = -rect.height * 0.4
    const raw = (start - rect.top) / (start - end)
    flowerRedProgress.value = Math.max(0, Math.min(1, raw))
  }

  const about = aboutWrapRef.value
  if (about) {
    const rect = about.getBoundingClientRect()
    const start = vh
    const end = -rect.height * 0.5
    const raw = (start - rect.top) / (start - end)
    flowerYellowProgress.value = Math.max(0, Math.min(1, raw))
  }
}

function onScroll() {
  if (!ticking) {
    ticking = true
    requestAnimationFrame(() => {
      updateFlowerPositions()
      ticking = false
    })
  }
}

onMounted(async () => {
  await Promise.all([
    store.fetchFeaturedProjects(),
    store.fetchGalleryPreview(),
    store.fetchGalleryPreviewMode(),
    store.fetchHeroLayers(),
    store.fetchInstagramPosts(),
    store.fetchAboutMe(),
  ])
  loading.value = false

  await nextTick()

  window.addEventListener('scroll', onScroll, { passive: true })
  updateFlowerPositions()
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
})
</script>

<template>
  <FullPageLoader v-model="loading" />
  <div v-if="!loading" class="home-container">
    <AppHero />
    <ProjectsPreview/>
    <div ref="galleryWrapRef" class="gallery-section-wrap">
      <img
        src="../assets/img/backgrounds/4_FIORE_SINGOLO_02.png"
        alt=""
        class="flower-red"
        :style="{
          transform: `translateY(-50%) translateX(${-100 + flowerRedProgress * 100}%)`,
        }"
        aria-hidden="true"
      />
      <component :is="galleryComponent" />
    </div>
    <div ref="aboutWrapRef" class="about-section-wrap">
      <AboutMePreview/>
      <img
        src="../assets/img/backgrounds/4_FIORE_SINGOLO_01.png"
        alt=""
        class="flower-yellow"
        :style="{
          transform: `translateY(50%) translateX(${100 - flowerYellowProgress * 100}%)`,
        }"
        aria-hidden="true"
      />
    </div>
    <InstagramCarousel />
  </div>
</template>

<style scoped lang="scss">
@use "../style/partials/variables" as *;

.home-container{
  position: relative;
  overflow-x: clip;

  .gallery-section-wrap {
    position: relative;
    overflow-x: clip;
  }

  .flower-red {
    position: absolute;
    left: -2px;
    top: 80%;
    width: 130px;
    height: auto;
    z-index: 2;
    pointer-events: none;
    will-change: transform, opacity;

    @media (min-width: 578px) {
      width: 140px;
      left: -20px;
    }
    @media (min-width: 768px) {
      width: 170px;
      left: -10px;
    }
    @media (min-width: 1024px) {
      width: 200px;
      left: 0;
    }
  }

  .about-section-wrap {
    position: relative;
    overflow-x: clip;
  }

  .flower-yellow {
    position: absolute;
    right: -10px;
    bottom: -60px;
    width: 150px;
    height: auto;
    z-index: 2;
    pointer-events: none;
    will-change: transform;

    @media (min-width: 578px) {
      width: 170px;
      right: -10px;
    }
    @media (min-width: 768px) {
      width: 200px;
      right: 0;
    }
    @media (min-width: 1024px) {
      width: 240px;
    }
  }
}
</style>

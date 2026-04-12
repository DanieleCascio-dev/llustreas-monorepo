<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useProjectStore } from '../store'

const store = useProjectStore()
const heroRef = ref(null)
const layerRefs = ref({})

let ticking = false
let prefersReducedMotion = false

const sortedLayers = computed(() => {
  return [...store.heroLayers].sort((a, b) => a.order - b.order)
})

function setLayerRef(id) {
  return (el) => {
    if (el) layerRefs.value[id] = el
  }
}

function onScroll() {
  if (ticking || prefersReducedMotion) return
  ticking = true
  requestAnimationFrame(() => {
    const scrollY = window.scrollY
    for (const layer of sortedLayers.value) {
      const el = layerRefs.value[layer.id]
      if (!el || layer.parallax_speed === 0) continue

      if (layer.type === 'slideshow') {
        el.style.transform = `translate3d(-50%, ${scrollY * layer.parallax_speed}px, 0)`
      } else {
        el.style.transform = `translate3d(0, ${scrollY * layer.parallax_speed}px, 0)`
      }
    }
    ticking = false
  })
}

onMounted(() => {
  prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  if (!prefersReducedMotion) {
    window.addEventListener('scroll', onScroll, { passive: true })
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
})

function slideDuration(layer) {
  const count = layer.images?.length || 1
  return (count * 4) + 's'
}

function slideDelay(layer, index) {
  return (index * 4) + 's'
}

function cssConfigClass(layer) {
  const cfg = layer.css_config || {}
  return cfg.class || ''
}
</script>

<template>
  <div class="header">
    <div class="hero" ref="heroRef" v-if="sortedLayers.length">
      <template v-for="layer in sortedLayers" :key="layer.id">
        <!-- Layer tipo image -->
        <picture
          v-if="layer.type === 'image' && layer.images?.length"
          class="hero-layer"
          :class="[cssConfigClass(layer)]"
          :style="{ zIndex: layer.z_index }"
          :ref="setLayerRef(layer.id)"
        >
          <source
            :srcset="layer.images[0].desktop_src"
            :media="`(min-width: ${layer.breakpoint}px)`"
          />
          <img :src="layer.images[0].mobile_src" :alt="layer.images[0].alt || ''" />
        </picture>

        <!-- Layer tipo slideshow -->
        <div
          v-else-if="layer.type === 'slideshow' && layer.images?.length"
          class="hero-titles"
          :style="{ zIndex: layer.z_index }"
          :ref="setLayerRef(layer.id)"
        >
          <template v-for="(img, si) in layer.images" :key="img.id">
            <img
              :src="img.mobile_src"
              :alt="img.alt || ''"
              class="hero-title d-sm-none"
              :style="{
                animationDelay: slideDelay(layer, si),
                animationDuration: slideDuration(layer),
              }"
            />
            <img
              :src="img.desktop_src"
              :alt="img.alt || ''"
              class="hero-title d-none d-sm-block"
              :style="{
                animationDelay: slideDelay(layer, si),
                animationDuration: slideDuration(layer),
              }"
            />
          </template>
        </div>
      </template>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@use "../style/partials/variables" as *;

.hero {
  position: relative;
  height: 100vh;
  width: 100%;
  overflow: hidden;
}

.hero-layer {
  position: absolute;
  inset: 0;
  will-change: transform;

  img {
    display: block;
    width: 100%;
    height: 120%;
    object-fit: cover;
    object-position: center bottom;
  }
}

/* Layer fiori/primo piano: ancorati al fondo */
.hero-layer:last-of-type {
  top: auto;
  bottom: 0;
  height: auto;

  img {
    height: auto;
  }
}

.hero-titles {
  position: absolute;
  top: -60px;
  left: 50%;
  transform: translate3d(-50%, 0, 0);
  width: 85%;
  max-width: 600px;
  will-change: transform;
  pointer-events: none;

  @media (min-width: 450px) {
    top: 5%;
    width: 90%;
    max-width: 100%;
  }

  @media (min-width: 576px) {
    width: 80%;
  }

  @media (min-width: 768px) {
    top: 10%;
    width: 75%;
  }

  @media (min-width: 1024px) {
    width: 65%;
  }

  @media (min-width: 1200px) {
    width: 60%;
  }
}

.hero-title {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 300px;
  opacity: 0;
  animation: hero-fade 12s infinite;

  @media (min-width: 450px) {
    height: auto;
  }
}

@keyframes hero-fade {
  0%   { opacity: 0; }
  10%  { opacity: 1; }
  30%  { opacity: 1; }
  40%  { opacity: 0; }
  100% { opacity: 0; }
}
</style>

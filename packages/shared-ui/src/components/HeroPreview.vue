<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'

const props = defineProps({
  layers: { type: Array, required: true },
  forceMode: { type: String, default: null },
  scrollOffset: { type: Number, default: 0 },
})

const isMobile = ref(false)

function checkMobile() {
  if (props.forceMode === 'mobile') { isMobile.value = true; return }
  if (props.forceMode === 'desktop') { isMobile.value = false; return }
  isMobile.value = window.innerWidth < 576
}

onMounted(() => {
  checkMobile()
  if (!props.forceMode) window.addEventListener('resize', checkMobile)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkMobile)
})

watch(() => props.forceMode, checkMobile)

const sortedLayers = computed(() =>
  [...props.layers].sort((a, b) => (a.order ?? a.z_index) - (b.order ?? b.z_index))
)

function layerTransform(layer) {
  const offset = props.scrollOffset * layer.parallax_speed
  return `translate3d(0, ${offset}px, 0)`
}

function slideshowTransform(layer) {
  const offset = props.scrollOffset * layer.parallax_speed
  return `translate3d(-50%, ${offset}px, 0)`
}

function imageSrc(layer) {
  if (!layer.images?.length) return null
  const img = layer.images[0]
  return isMobile.value ? img.mobile_src : img.desktop_src
}

function slides(layer) {
  if (!layer.images?.length) return []
  return layer.images.map(img =>
    isMobile.value ? img.mobile_src : img.desktop_src
  ).filter(Boolean)
}

function slideDuration(layer) {
  const count = layer.images?.length || 1
  return (count * 4) + 's'
}

function slideDelay(index) {
  return (index * 4) + 's'
}

const imageLayers = computed(() =>
  sortedLayers.value.filter(l => l.type === 'image' && l.images?.length)
)

function isLastImageLayer(layer) {
  return imageLayers.value.length > 0 &&
    imageLayers.value[imageLayers.value.length - 1].id === layer.id
}
</script>

<template>
  <div class="hp-hero" :class="isMobile ? 'hp-hero--mobile' : 'hp-hero--desktop'">
    <template v-for="layer in sortedLayers" :key="layer.id">
      <!-- Image layer: responsive <picture> when no forceMode, direct src when forced -->
      <picture
        v-if="layer.type === 'image' && layer.images?.length && !forceMode"
        class="hp-layer"
        :style="{ zIndex: layer.z_index, transform: layerTransform(layer) }"
      >
        <source
          :srcset="layer.images[0].desktop_src"
          :media="`(min-width: ${layer.breakpoint || 576}px)`"
        />
        <img :src="layer.images[0].mobile_src" :alt="layer.images[0].alt || ''" />
      </picture>

      <div
        v-else-if="layer.type === 'image' && imageSrc(layer)"
        class="hp-layer"
        :class="{ 'hp-layer--last': isLastImageLayer(layer) }"
        :style="{ zIndex: layer.z_index, transform: layerTransform(layer) }"
      >
        <img :src="imageSrc(layer)" alt="" />
      </div>

      <!-- Slideshow layer: responsive when no forceMode -->
      <div
        v-if="layer.type === 'slideshow' && layer.images?.length && !forceMode"
        class="hp-titles"
        :style="{ zIndex: layer.z_index, transform: slideshowTransform(layer) }"
      >
        <template v-for="(img, si) in layer.images" :key="img.id">
          <img
            :src="img.mobile_src"
            :alt="img.alt || ''"
            class="hp-slide hp-slide--mobile-only"
            :style="{
              animationDelay: slideDelay(si),
              animationDuration: slideDuration(layer),
            }"
          />
          <img
            :src="img.desktop_src"
            :alt="img.alt || ''"
            class="hp-slide hp-slide--desktop-only"
            :style="{
              animationDelay: slideDelay(si),
              animationDuration: slideDuration(layer),
            }"
          />
        </template>
      </div>

      <div
        v-else-if="layer.type === 'slideshow' && slides(layer).length && forceMode"
        class="hp-titles"
        :style="{ zIndex: layer.z_index, transform: slideshowTransform(layer) }"
      >
        <img
          v-for="(src, si) in slides(layer)"
          :key="si"
          :src="src"
          alt=""
          class="hp-slide"
          :style="{
            animationDelay: slideDelay(si),
            animationDuration: slideDuration(layer),
          }"
        />
      </div>
    </template>
  </div>
</template>

<style scoped>
.hp-hero {
  position: relative;
  width: 100%;
  overflow: hidden;
  background: var(--hp-bg, #eee);
}

.hp-hero--desktop {
  aspect-ratio: 16 / 9;
}

.hp-hero--mobile {
  aspect-ratio: 9 / 16;
}

/* When used in frontoffice without forceMode, fill viewport */
.hp-hero:not(.hp-hero--mobile):not(.hp-hero--desktop) {
  height: 100vh;
  height: 100dvh;
}

/* ── Image layers ── */
.hp-layer {
  position: absolute;
  inset: 0;
  will-change: transform;
}

.hp-layer img {
  display: block;
  width: 100%;
  height: 120%;
  object-fit: cover;
  object-position: center bottom;
}

.hp-layer--last {
  top: auto;
  bottom: 0;
  height: auto;
}

.hp-layer--last img {
  height: auto;
}

/* ── Slideshow titles ── */
.hp-titles {
  position: absolute;
  left: 50%;
  transform: translate3d(-50%, 0, 0);
  will-change: transform;
  pointer-events: none;
}

.hp-hero--mobile .hp-titles {
  top: -7%;
  width: 85%;
  height: 35%;
}

.hp-hero--desktop .hp-titles {
  top: 10%;
  width: 65%;
}

/* Frontoffice responsive (no forceMode) */
.hp-hero:not(.hp-hero--mobile):not(.hp-hero--desktop) .hp-titles {
  top: -60px;
  width: 85%;
  max-width: 600px;
}

@media (min-width: 450px) {
  .hp-hero:not(.hp-hero--mobile):not(.hp-hero--desktop) .hp-titles {
    top: 5%;
    width: 90%;
    max-width: 100%;
  }
}

@media (min-width: 576px) {
  .hp-hero:not(.hp-hero--mobile):not(.hp-hero--desktop) .hp-titles {
    width: 80%;
  }
}

@media (min-width: 768px) {
  .hp-hero:not(.hp-hero--mobile):not(.hp-hero--desktop) .hp-titles {
    top: 10%;
    width: 75%;
  }
}

@media (min-width: 1024px) {
  .hp-hero:not(.hp-hero--mobile):not(.hp-hero--desktop) .hp-titles {
    width: 65%;
  }
}

@media (min-width: 1200px) {
  .hp-hero:not(.hp-hero--mobile):not(.hp-hero--desktop) .hp-titles {
    width: 60%;
  }
}

/* ── Slides ── */
.hp-slide {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  opacity: 0;
  animation: hp-fade infinite;
}

.hp-hero--mobile .hp-slide {
  height: 100%;
}

.hp-hero--desktop .hp-slide {
  height: auto;
}

/* Frontoffice responsive slide sizing */
.hp-hero:not(.hp-hero--mobile):not(.hp-hero--desktop) .hp-slide {
  height: 300px;
}

@media (min-width: 450px) {
  .hp-hero:not(.hp-hero--mobile):not(.hp-hero--desktop) .hp-slide {
    height: auto;
  }
}

/* Responsive show/hide for slides (frontoffice only) */
.hp-slide--mobile-only {
  display: block;
}

.hp-slide--desktop-only {
  display: none;
}

@media (min-width: 576px) {
  .hp-slide--mobile-only {
    display: none;
  }
  .hp-slide--desktop-only {
    display: block;
  }
}

@keyframes hp-fade {
  0%   { opacity: 0; }
  10%  { opacity: 1; }
  30%  { opacity: 1; }
  40%  { opacity: 0; }
  100% { opacity: 0; }
}
</style>

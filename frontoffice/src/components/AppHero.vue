<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { HeroPreview as BaseHeroPreview } from '@illustreas/shared-ui'
import { useProjectStore } from '../store'

const store = useProjectStore()
const scrollY = ref(0)
let ticking = false
let prefersReducedMotion = false

const sortedLayers = computed(() => store.heroLayers)

function onScroll() {
  if (ticking || prefersReducedMotion) return
  ticking = true
  requestAnimationFrame(() => {
    scrollY.value = window.scrollY
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
</script>

<template>
  <div class="header">
    <BaseHeroPreview
      :layers="sortedLayers"
      :scroll-offset="scrollY"
    />
  </div>
</template>

<style lang="scss" scoped>
@use "../style/partials/variables" as *;

.header :deep(.hp-hero) {
  height: 100vh;
  height: 100dvh;
}

.header :deep(.hp-layer:last-of-type) {
  top: auto;
  bottom: 0;
  height: auto;

  img {
    height: auto;
  }
}
</style>

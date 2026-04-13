<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'

const props = defineProps({
  image: { type: String, default: '' },
  title: { type: String, default: '' },
  paragraphs: { type: Array, default: () => [] },
  tags: { type: Array, default: () => [] },
  divider: { type: Object, default: () => ({ thickness: 1.5, color: '#5b3c88', opacity: 25, height: 100 }) },
  titleColor: { type: String, default: '#5b3c88' },
  textColor: { type: String, default: '#5b3c88' },
  sectionRadius: { type: String, default: '0' },
  forceMode: { type: String, default: null },
})

const isMobile = ref(false)

function checkMobile() {
  if (props.forceMode === 'mobile') { isMobile.value = true; return }
  if (props.forceMode === 'desktop') { isMobile.value = false; return }
  isMobile.value = window.innerWidth < 768
}

onMounted(() => {
  checkMobile()
  if (!props.forceMode) window.addEventListener('resize', checkMobile)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkMobile)
})

watch(() => props.forceMode, checkMobile)

const marqueeText = computed(() => {
  if (!props.tags.length) return ''
  return props.tags.join(' · ') + ' · '
})

const dividerLineStyle = computed(() => ({
  width: (props.divider.thickness ?? 1.5) + 'px',
  height: (props.divider.height ?? 100) + '%',
  background: props.divider.color || '#5b3c88',
  opacity: (props.divider.opacity ?? 25) / 100,
}))
</script>

<template>
  <section
    class="am-section"
    :class="isMobile ? 'am-section--mobile' : 'am-section--desktop'"
    :style="{ '--am-section-radius': sectionRadius }"
  >
    <div class="am-inner" :class="isMobile ? 'am-inner--mobile' : 'am-inner--desktop'">
      <div class="am-photo-col" :class="isMobile ? 'am-photo-col--centered' : 'am-photo-col--side'">
        <slot name="photo">
          <div class="am-photo">
            <img v-if="image" :src="image" alt="" class="am-photo-img" loading="lazy" />
            <div v-else class="am-photo-placeholder">Nessuna immagine</div>
          </div>
        </slot>
        <div v-if="marqueeText" class="am-marquee" aria-hidden="true">
          <div class="am-marquee-track">
            <span class="am-marquee-text">{{ marqueeText }}&nbsp;</span>
            <span class="am-marquee-text">{{ marqueeText }}&nbsp;</span>
            <span class="am-marquee-text">{{ marqueeText }}&nbsp;</span>
            <span class="am-marquee-text">{{ marqueeText }}&nbsp;</span>
          </div>
        </div>
      </div>

      <div v-if="!isMobile" class="am-divider" aria-hidden="true">
        <span class="am-divider-line" :style="dividerLineStyle"></span>
      </div>

      <div class="am-text-col" :class="isMobile ? 'am-text-col--center' : 'am-text-col--left'">
        <slot name="title-heading">
          <h1 class="am-title" :style="{ color: titleColor }">{{ title || 'Titolo' }}</h1>
        </slot>
        <div class="am-paragraphs">
          <p
            v-for="(p, idx) in paragraphs"
            :key="idx"
            class="am-paragraph"
            :style="{ color: textColor }"
            v-html="p || '(vuoto)'"
          ></p>
          <p v-if="!paragraphs.length" class="am-empty">Nessun paragrafo</p>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.am-section {
  background-color: var(--am-bg, #f5f0eb);
  color: var(--am-text, #5b3c88);
  border-radius: var(--am-section-radius, 0);
  overflow: hidden;
}

.am-section--mobile {
  padding: 56px 24px;
}

.am-section--desktop {
  padding: 80px 50px;
}

@media (min-width: 1200px) {
  .am-section--desktop {
    padding: 100px 200px;
  }
}

/* ── Inner layout ── */
.am-inner {
  max-width: 1100px;
  margin: 0 auto;
}

.am-inner--desktop {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 0;
}

.am-inner--mobile {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 40px;
}

/* ── Photo column ── */
.am-photo-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
}

.am-photo-col--side {
  flex: 0 0 40%;
  margin-top: -20px;
}

.am-photo-col--centered {
  width: 70%;
}

.am-photo {
  width: 80%;
  max-width: 420px;
  margin: 0 auto;
  border-radius: 12px;
  overflow: hidden;
}

.am-photo-col--side .am-photo {
  width: 90%;
}

.am-photo-img {
  width: 100%;
  height: auto;
  object-fit: contain;
  border-radius: 12px;
  transition: transform 0.4s ease;
}

@media (hover: hover) {
  .am-photo-img:hover {
    transform: scale(1.03);
  }
}

.am-photo-placeholder {
  width: 100%;
  aspect-ratio: 3 / 4;
  background: rgba(91, 60, 136, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--am-text, #5b3c88);
  font-size: 12px;
  border-radius: 12px;
  opacity: 0.5;
}

/* ── Marquee ── */
.am-marquee {
  width: 80%;
  max-width: 420px;
  overflow: hidden;
  margin-top: 16px;
}

.am-photo-col--side .am-marquee {
  width: 90%;
}

.am-marquee-track {
  display: flex;
  width: max-content;
  animation: am-marquee-scroll 14s linear infinite;
}

.am-marquee-text {
  flex-shrink: 0;
  white-space: nowrap;
  font-family: "Work Sans", sans-serif;
  font-size: clamp(0.7rem, 1.3vw, 0.85rem);
  text-transform: uppercase;
  letter-spacing: 0.18em;
  color: var(--am-text, #5b3c88);
}

@keyframes am-marquee-scroll {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

/* ── Divider ── */
.am-divider {
  display: flex;
  justify-content: center;
  align-self: stretch;
  padding: 20px 28px;
  flex-shrink: 0;
}

.am-divider-line {
  display: block;
}

/* ── Text column ── */
.am-text-col--left {
  flex: 1;
  text-align: left;
  padding-top: 8px;
}

.am-text-col--center {
  text-align: center;
  width: 100%;
}

.am-title {
  font-family: "Young Serif", serif;
  font-size: clamp(2.8rem, 6vw, 5rem);
  letter-spacing: 0.04em;
  margin: 0 0 8px;
  color: var(--am-text, #5b3c88);
}

.am-paragraphs {
  width: 100%;
}

.am-text-col--left .am-paragraphs {
  width: 90%;
}

.am-paragraph {
  font-size: 15px;
  line-height: 1.7;
  margin: 0 0 14px;
}

.am-empty {
  font-size: 12px;
  opacity: 0.5;
  font-style: italic;
}

@media (prefers-reduced-motion: reduce) {
  .am-marquee-track {
    animation: none;
  }
}
</style>

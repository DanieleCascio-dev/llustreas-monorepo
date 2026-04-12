<script setup>
import { computed } from 'vue'
import { useProjectStore } from '../store'

const store = useProjectStore()

const aboutMe = computed(() => store.aboutMe)

const marqueeText = computed(() => {
  const tags = aboutMe.value?.tags
  if (!tags?.length) return ''
  return tags.join(' · ') + ' · '
})

const dividerLineStyle = computed(() => {
  const d = aboutMe.value?.divider
  if (!d) return {}
  return {
    width: (d.thickness ?? 1.5) + 'px',
    height: (d.height ?? 100) + '%',
    background: d.color || '#5b3c88',
  }
})

const dividerFinalOpacity = computed(() => {
  const d = aboutMe.value?.divider
  return (d?.opacity ?? 25) / 100
})

const titleColorStyle = computed(() => {
  const c = aboutMe.value?.titleColor
  return c ? { color: c } : {}
})

const textColorStyle = computed(() => {
  const c = aboutMe.value?.textColor
  return c ? { color: c } : {}
})
</script>

<template>
  <section v-if="aboutMe" class="about-me-preview" id="about-me">
    <div class="about-me-inner">
      <div v-reveal class="about-me-photo">
        <img :src="aboutMe.image" alt="Letizia Amelia Ragione" class="photo-img" />
        <div v-if="marqueeText" class="marquee" aria-hidden="true">
          <div class="marquee__track">
            <span class="marquee__text">{{ marqueeText }}&nbsp;</span>
            <span class="marquee__text">{{ marqueeText }}&nbsp;</span>
            <span class="marquee__text">{{ marqueeText }}&nbsp;</span>
            <span class="marquee__text">{{ marqueeText }}&nbsp;</span>
          </div>
        </div>
      </div>

      <div class="about-me-divider" aria-hidden="true">
        <span
          v-reveal
          class="divider-line"
          :style="{ ...dividerLineStyle, '--divider-opacity': dividerFinalOpacity }"
        ></span>
      </div>

      <div class="about-me-text">
        <h1 v-reveal class="about-me-title" :style="titleColorStyle">{{ aboutMe.title }}</h1>
        <div class="about-me-paragraphs">
          <p
            v-for="(p, idx) in aboutMe.paragraphs"
            :key="idx"
            v-reveal="150 + idx * 150"
            class="text-custom"
            :style="textColorStyle"
            v-html="p"
          ></p>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped lang="scss">
@use "../style/partials/variables" as *;

.about-me-preview {
  background-color: $bg-light;
  color: $text-violet;
  padding: 56px 24px;
  overflow: hidden;

  @media (min-width: 768px) {
    padding: 80px 50px;
  }

  @media (min-width: 1200px) {
    padding: 100px 200px;
  }
}

.about-me-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 40px;
  max-width: 1100px;
  margin: 0 auto;

  @media (min-width: 768px) {
    flex-direction: row;
    align-items: flex-start;
    gap: 0;
  }
}

.about-me-photo {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;

  &.reveal {
    opacity: 0;
    transform: translateX(-40px) scale(0.92);
    transition: opacity 0.7s ease, transform 0.9s cubic-bezier(0.16, 1, 0.3, 1);
  }
  &.reveal--visible {
    opacity: 1;
    transform: translateX(0) scale(1);
  }

  @media (min-width: 768px) {
    flex: 0 0 40%;
    margin-top: -20px;
  }
}

.marquee {
  width: 80%;
  max-width: 420px;
  overflow: hidden;
  margin-top: 16px;

  @media (min-width: 768px) {
    width: 90%;
  }
}

.marquee__track {
  display: flex;
  width: max-content;
  animation: marquee-scroll 14s linear infinite;
}

.marquee__text {
  flex-shrink: 0;
  white-space: nowrap;
  font-family: "Work Sans", sans-serif;
  font-size: clamp(0.7rem, 1.3vw, 0.85rem);
  text-transform: uppercase;
  letter-spacing: 0.18em;
  color: $text-violet;
}

@keyframes marquee-scroll {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

.photo-img {
  width: 80%;
  max-width: 420px;
  height: auto;
  object-fit: contain;
  border-radius: 12px;
  transition: transform 0.4s ease;

  @media (min-width: 768px) {
    width: 90%;
  }

  @media (hover: hover) {
    &:hover {
      transform: scale(1.03);
    }
  }
}

.about-me-divider {
  display: none;

  @media (min-width: 768px) {
    display: flex;
    justify-content: center;
    align-self: stretch;
    padding: 20px 28px;
  }
}

.divider-line {
  display: block;
  width: 1.5px;
  height: 100%;
  background: $text-violet;
  --divider-opacity: 0.25;

  &.reveal {
    opacity: 0;
    transform: scaleY(0);
    transform-origin: top center;
    transition: transform 1s cubic-bezier(0.16, 1, 0.3, 1) 0.3s,
                opacity 0.6s ease 0.3s;
  }
  &.reveal--visible {
    opacity: var(--divider-opacity);
    transform: scaleY(1);
  }
}

.about-me-text {
  text-align: center;

  @media (min-width: 768px) {
    text-align: left;
    flex: 1;
    padding-top: 8px;
  }
}

.about-me-title {
  font-family: "Young Serif", serif;
  font-size: clamp(2.8rem, 6vw, 5rem);
  letter-spacing: 0.04em;
  margin-bottom: 8px;
  color: $text-violet;

  &.reveal {
    opacity: 0;
    transform: translateX(-20px);
    transition: opacity 0.6s ease, transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
  }
  &.reveal--visible {
    opacity: 1;
    transform: translateX(0);
  }
}

.about-me-paragraphs {
  width: 100%;

  @media (min-width: 768px) {
    width: 90%;
  }

  p {
    line-height: 1.7;
    margin-bottom: 14px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .about-me-photo.reveal,
  .about-me-title.reveal,
  .divider-line.reveal {
    opacity: 1;
    transform: none;
    transition: none;
  }
  .marquee__track {
    animation: none;
  }
}
</style>

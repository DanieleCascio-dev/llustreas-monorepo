<script setup>
import { computed } from 'vue'
import { AboutMePreview as BaseAboutMe } from '@illustreas/shared-ui'
import { useProjectStore } from '../store'

const store = useProjectStore()
const aboutMe = computed(() => store.aboutMe)
</script>

<template>
  <BaseAboutMe
    v-if="aboutMe"
    :image="aboutMe.image"
    :title="aboutMe.title"
    :paragraphs="aboutMe.paragraphs || []"
    :tags="aboutMe.tags || []"
    :divider="aboutMe.divider || {}"
    :title-color="aboutMe.titleColor"
    :text-color="aboutMe.textColor"
    class="about-me-fo"
  >
    <template #photo>
      <div v-reveal class="am-photo-wrap">
        <img :src="aboutMe.image" alt="Letizia Amelia Ragione" class="photo-img" />
      </div>
    </template>
    <template #title-heading>
      <h1 v-reveal class="about-me-title" :style="{ color: aboutMe.titleColor || '#5b3c88' }">
        {{ aboutMe.title }}
      </h1>
    </template>
  </BaseAboutMe>
</template>

<style scoped lang="scss">
@use "../style/partials/variables" as *;

.about-me-fo {
  :deep(.am-section) {
    background-color: $bg-light;
    color: $text-violet;
  }
}

.am-photo-wrap {
  width: 80%;
  max-width: 420px;
  margin: 0 auto;
  border-radius: 12px;
  overflow: hidden;

  @media (min-width: 768px) {
    width: 90%;
  }

  &.reveal {
    opacity: 0;
    transform: translateX(-40px) scale(0.92);
    transition: opacity 0.7s ease, transform 0.9s cubic-bezier(0.16, 1, 0.3, 1);
  }
  &.reveal--visible {
    opacity: 1;
    transform: translateX(0) scale(1);
  }
}

.photo-img {
  width: 100%;
  height: auto;
  object-fit: contain;
  border-radius: 12px;
  transition: transform 0.4s ease;

  @media (hover: hover) {
    &:hover {
      transform: scale(1.03);
    }
  }
}

.about-me-title {
  font-family: "Young Serif", serif;
  font-size: clamp(2.8rem, 6vw, 5rem);
  letter-spacing: 0.04em;
  margin: 0 0 8px;
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

@media (prefers-reduced-motion: reduce) {
  .am-photo-wrap.reveal,
  .about-me-title.reveal {
    opacity: 1;
    transform: none;
    transition: none;
  }
}
</style>

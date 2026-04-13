<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref } from "vue";
import { useRouter } from "vue-router";
import { ProjectsPreview as BaseProjectsPreview } from "@illustreas/shared-ui";
import { useProjectStore } from "../store.js";
import fioreImg from "../assets/img/backgrounds/4_FIORE_SINGOLO_04.png";

const projectStore = useProjectStore();
const router = useRouter();
const sectionRef = ref<HTMLElement | null>(null);
const flowerRef = ref<HTMLElement | null>(null);
let ticking = false;

function onScroll() {
  if (ticking || !flowerRef.value || !sectionRef.value) return;
  ticking = true;
  requestAnimationFrame(() => {
    const rect = sectionRef.value!.getBoundingClientRect();
    const vh = window.innerHeight;
    const progress = Math.max(0, Math.min(1, 1 - rect.bottom / (vh + rect.height)));
    flowerRef.value!.style.transform = `translateY(${180 - progress * 180}px)`;
    ticking = false;
  });
}

onMounted(() => {
  projectStore.fetchFeaturedProjects();
  if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
  }
});

onBeforeUnmount(() => {
  window.removeEventListener("scroll", onScroll);
});

const featured = computed(() => projectStore.featuredProjects);

async function onProjectClick(project: any) {
  await router.push({ name: "ProjectDetail", params: { slug: project.slug } });
}

function goToProjectsList() {
  router.push({ name: "projects" });
}
</script>

<template>
  <div ref="sectionRef" class="pp-fo-wrap">
    <BaseProjectsPreview
      :projects="featured"
      @project-click="onProjectClick"
    >
      <template #title>
        <h1 v-reveal class="preview-title title-custom">Progetti</h1>
      </template>
      <template #footer>
        <div v-reveal class="btn-wrap">
          <div class="look-btn subtitle-custom" @click="goToProjectsList">
            Sbircia
          </div>
        </div>
        <img
          ref="flowerRef"
          :src="fioreImg"
          alt=""
          class="rising-flower"
          aria-hidden="true"
        />
      </template>
    </BaseProjectsPreview>
  </div>
</template>

<style scoped lang="scss">
@use "../style/partials/variables" as *;

.pp-fo-wrap {
  position: relative;
}

.pp-fo-wrap :deep(.pp-section) {
  background: $bg-light;
  color: $text-violet;
}

.preview-title {
  color: $text-violet;
  text-align: center;
  margin-bottom: 32px;

  @media (min-width: 768px) {
    margin-bottom: 40px;
  }
}

.rising-flower {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 160px;
  height: auto;
  z-index: 2;
  pointer-events: none;
  will-change: transform;

  @media (min-width: 576px) {
    width: 220px;
  }

  @media (min-width: 1024px) {
    width: 300px;
  }
}

.btn-wrap {
  margin-top: 32px;
  display: flex;
  justify-content: center;
}

.look-btn {
  display: flex;
  justify-content: center;
  align-items: center;
  background-image: url("../assets/img/backgrounds/SFONDI_PULSANTI_01_VIOLA.svg");
  background-size: contain;
  background-repeat: no-repeat;
  width: 200px;
  min-height: 48px;
  height: 70px;
  color: $bg-white;
  font-family: "Young Serif", serif;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  transition: color 0.3s ease;

  @media (hover: hover) {
    &:hover {
      color: $text-violet;
      background-image: url("../assets/img/backgrounds/SFONDI_PULSANTI_01_BIANCO.svg");
    }
  }

  &:active {
    opacity: 0.85;
  }
}
</style>

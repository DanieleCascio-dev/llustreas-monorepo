<script setup lang="ts">
  /*** ANTEPRIMA PROGETTI — mostra i progetti in evidenza nella homepage ***/
  import { useProjectStore } from "../store.js";
  import { computed, onMounted, onBeforeUnmount, ref } from "vue";
  import { useRouter } from "vue-router";
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
      const progress = Math.max(
        0,
        Math.min(1, 1 - rect.bottom / (vh + rect.height)),
      );
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

  const goToProjectsList = () => {
    router.push({ name: "projects" });
  };

  async function goToProject(slug: string) {
    await router.push({ name: "ProjectDetail", params: { slug } });
  }
</script>

<template>
  <div ref="sectionRef" class="projects-preview">
    <h1 v-reveal class="preview-title title-custom">Progetti</h1>

    <div class="project-grid">
      <div
        v-for="(project, idx) in featured"
        :key="project.id"
        v-reveal="idx * 120"
        class="project-item"
        @click="goToProject(project.slug)"
      >
        <img :src="project.gif" :alt="project.title" />
        <div class="project-overlay">
          <div class="overlay-title subtitle-custom">{{ project.title }}</div>
          <div class="overlay-info text-custom">{{ project.info }}</div>
        </div>
        <!-- Titolo fisso visibile su mobile (no hover) -->
        <div class="project-label">{{ project.title }}</div>
      </div>
    </div>

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
  </div>
</template>

<style scoped lang="scss">
  @use "../style/partials/variables" as *;

  .projects-preview {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    background: $bg-light;
    padding: 48px 16px;
    overflow: hidden;

    @media (min-width: 768px) {
      padding: 84px 20px;
    }
  }

  /* ── Fiore decorativo che "sale" con lo scroll ── */
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

  .preview-title {
    color: $text-violet;
    text-align: center;
    margin-bottom: 32px;

    @media (min-width: 768px) {
      margin-bottom: 40px;
    }
  }

  /* ── Griglia progetti ── */
  .project-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    width: 100%;
    max-width: 1200px;

    @media (min-width: 576px) {
      grid-template-columns: repeat(2, 1fr);
    }

    @media (min-width: 992px) {
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
    }
  }

  /* Centra l'ultimo progetto se e' solo nella sua riga (layout a 2 colonne) */
  @media (min-width: 576px) and (max-width: 991px) {
    .project-item:last-child:nth-child(odd) {
      grid-column: 1 / -1;
      max-width: calc(50% - 10px);
      justify-self: center;
    }
  }

  /* ── Singolo progetto ── */
  .project-item {
    position: relative;
    cursor: pointer;
    overflow: hidden;
    border-radius: 6px;
    -webkit-tap-highlight-color: transparent;

    aspect-ratio: 3 / 4;

    /* padding-bottom fallback for iOS Safari where aspect-ratio
     inside CSS Grid can collapse the container to zero height */
    @supports not (aspect-ratio: 3 / 4) {
      &::before {
        content: "";
        display: block;
        padding-bottom: 133.33%;
      }
    }

    img {
      position: absolute;
      inset: 0;
      display: block;
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    &:active img {
      transform: scale(0.98);
      transition: transform 0.15s ease;
    }

    &:focus-visible {
      outline: 2px solid $text-violet;
      outline-offset: 2px;
    }
  }

  /* Overlay con info — solo per dispositivi con hover */
  .project-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    background-color: rgba($text-violet, 1);
    color: white;
    padding: 40px 20px;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;

    .overlay-title {
      font-weight: 500;
      font-family: "Young Serif", serif;
      text-align: center;
    }

    .overlay-info {
      font-family: "Assistant", serif;
      letter-spacing: 1px;
      font-weight: 400;
      text-align: center;
      margin-top: auto;
    }
  }

  @media (hover: hover) {
    .project-item:hover .project-overlay {
      opacity: 1;
      pointer-events: auto;
    }
    .project-label {
      display: none;
    }
  }

  /* Titolo fisso visibile su dispositivi touch (no hover) */
  .project-label {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 10px 12px;
    background: linear-gradient(transparent, rgba($text-violet, 0.85));
    color: white;
    font-family: "Young Serif", serif;
    font-size: 1.1rem;
    pointer-events: none;

    @media (hover: hover) {
      display: none;
    }
  }

  /* ── Bottone "Sbircia" ── */
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

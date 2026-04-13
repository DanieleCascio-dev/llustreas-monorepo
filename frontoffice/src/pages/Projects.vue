<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { ProjectsGrid as BaseProjectsGrid } from "@illustreas/shared-ui";
import { useProjectStore } from "../store";
import FullPageLoader from "../components/FullPageLoader.vue";

const projectStore = useProjectStore();
const router = useRouter();
const loading = ref(true);

onMounted(async () => {
  await projectStore.fetchProjects();
  loading.value = false;
});

const filteredProjects = computed(() =>
  [...projectStore.projects].sort((a, b) => a.order - b.order)
);

function onProjectClick(project: any) {
  router.push({ name: "ProjectDetail", params: { slug: project.slug } });
}

const scrollToId = (id: string) => {
  const el = document.getElementById(id);
  if (!el) return;
  const headerH =
    (document.querySelector("nav.nav-fixed") as HTMLElement)?.clientHeight ?? 80;
  const y = el.getBoundingClientRect().top + window.scrollY - headerH;
  window.scrollTo({ top: y, behavior: "smooth" });
};
</script>

<template>
  <FullPageLoader v-model="loading" />
  <div class="projects-page">
    <BaseProjectsGrid
      :projects="filteredProjects"
      @project-click="onProjectClick"
    >
      <template #header>
        <div class="header-content pt-5">
          <div class="projects-header-stack">
            <div class="projects-header-title">
              <img
                src="../assets/img/projectText/1_SCRITTA-19.svg"
                alt="Progetti"
                class="text-img-1 d-none d-md-block"
              />
              <img
                src="../assets/img/projectText/1_SCRITTA-20.svg"
                alt="Progetti"
                class="text-img-2 d-md-none"
              />
            </div>
            <div
              @click.prevent="scrollToId('projects-gallery')"
              class="projects-scroll-cta"
            >
              <img
                src="../assets/img/projectText/1_SCRITTA-21.svg"
                alt="Scorri ai progetti"
                class="button-img"
              />
            </div>
          </div>
        </div>
        <div id="projects-gallery" style="scroll-margin-top: 90px"></div>
      </template>

      <template #footer>
        <div class="flower">
          <img
            src="../assets/img/backgrounds/4_FIORE_SINGOLO_04.png"
            alt="flower"
            class="flower-img"
          />
        </div>
      </template>
    </BaseProjectsGrid>
  </div>
</template>

<style scoped lang="scss">
@use "../style/partials/variables" as *;

.projects-page :deep(.pg-section) {
  background-color: var(--header-bg);
  color: $bg-light;
  overflow-x: clip;
  overflow-y: clip;

  @supports not (overflow: clip) {
    overflow-x: hidden;
    overflow-y: hidden;
  }
}

.header-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
}

.projects-header-stack {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 2rem;
  width: min(1420px, 98vw);
  max-width: 100%;
  margin-inline: auto;
  margin-bottom: 3rem;
  box-sizing: border-box;
  padding-inline: clamp(12px, 3vw, 32px);
}

.projects-header-title {
  width: 100%;

  .text-img-1,
  .text-img-2 {
    display: block;
    width: 100%;
    height: auto;
  }
}

.projects-scroll-cta {
  align-self: center;
  cursor: pointer;
  flex-shrink: 0;
  line-height: 0;

  .button-img {
    display: block;
    width: 104px;
    height: auto;

    @media (min-width: 768px) {
      width: 140px;
    }

    @media (min-width: 1200px) {
      width: 168px;
    }
  }
}

.flower {
  position: absolute;
  bottom: -18px;
  right: -72px;
  z-index: 1;
  overflow: hidden;

  @media (min-width: 768px) {
    bottom: -25px;
    right: -90px;
  }

  @media (min-width: 1024px) {
    bottom: -25px;
    right: -90px;
  }

  @media (min-width: 1200px) {
    bottom: -30px;
    right: -72px;
  }

  @media (min-width: 1500px) {
    bottom: 0;
    right: 0;
  }

  .flower-img {
    width: 250px;
    height: auto;

    @media (min-width: 768px) {
      width: 250px;
    }

    @media (min-width: 1024px) {
      width: 250px;
    }

    @media (min-width: 1200px) {
      width: 250px;
    }

    @media (min-width: 1500px) {
      width: 300px;
    }
  }
}
</style>

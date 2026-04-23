<script setup>
/*** DETTAGLIO PROGETTO — carica il progetto singolo dall'API tramite slug ***/
import { useProjectStore } from "../store";
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import GridProject from "../components/GridProject.vue";
import ColumnProject from "../components/ColumnProject.vue";
import FullPageLoader from "../components/FullPageLoader.vue";

defineOptions({ inheritAttrs: false });

const route = useRoute();
const router = useRouter();
const projectStore = useProjectStore();

const slug = computed(() => route.params.slug);
const project = computed(() => projectStore.currentProject);
const projects = computed(() => projectStore.projects);
const sortedProjects = computed(() =>
  [...projects.value].sort((a, b) => a.order - b.order),
);
const currentIndex = computed(() =>
  sortedProjects.value.findIndex((p) => p.slug === slug.value),
);

const prevProject = computed(() => {
  const list = sortedProjects.value;
  if (list.length === 0 || currentIndex.value === -1) return null;
  return list[(currentIndex.value - 1 + list.length) % list.length];
});

const nextProject = computed(() => {
  const list = sortedProjects.value;
  if (list.length === 0 || currentIndex.value === -1) return null;
  return list[(currentIndex.value + 1) % list.length];
});

// il loader è attivo finché l'API non risponde
const loading = ref(true);

// carica progetto dall'API; ricarica se lo slug cambia (navigazione tra progetti)
async function loadProject() {
  loading.value = true;
  await projectStore.fetchProjects();
  await projectStore.fetchProject(slug.value);
  loading.value = false;
}

function goToProject(targetSlug) {
  if (!targetSlug || targetSlug === slug.value) return;
  router
    .push({ name: "ProjectDetail", params: { slug: targetSlug } })
    .then(() => {
      window.scrollTo({ top: 0, behavior: "instant" });
    });
}

function scrollTop() {
  window.scrollTo({ top: 0, behavior: "smooth" });
}

onMounted(loadProject);

watch(slug, async () => {
  await loadProject();
  window.scrollTo({ top: 0, behavior: "instant" });
});
</script>

<template>
  <div v-bind="$attrs">
    <FullPageLoader v-model="loading" />
    <template v-if="project">
      <div class="project-detail__inner">
        <GridProject v-if="project.layout === 'grid'" :project="project" />
        <ColumnProject
          v-else-if="project.layout === 'column'"
          :project="project"
        />
        <div class="project-navigation" v-if="prevProject || nextProject">
          <button
            v-if="prevProject"
            class="nav-button nav-button--prev"
            @click="goToProject(prevProject.slug)"
            type="button"
            aria-label="Progetto precedente"
          >
            Progetto precedente
          </button>

          <button
            v-if="nextProject"
            class="nav-button nav-button--next"
            @click="goToProject(nextProject.slug)"
            type="button"
            aria-label="Progetto successivo"
          >
            Progetto successivo
          </button>
        </div>
      </div>
    </template>

    <button
      class="scroll-top"
      type="button"
      @click="scrollTop"
      aria-label="Torna in cima"
    >
      ↑
    </button>
  </div>
</template>

<style scoped lang="scss">
@use "../style/partials/variables" as *;
.project {
  //padding-bottom: 84px;
  //background-color: lighten($bg-about-me, 25%);
  background-color: $bg-light;
}
.project-detail__inner {
  max-width: 1300px;
  margin: 0 auto;
  padding: 0 10px;
}
.image-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 4px;
  padding: 0 10px;

  .image-item {
    text-align: center;
  }
}
.project-navigation {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  width: 100%;
  margin: 32px auto 0;
  margin-bottom: 5px;
}
.nav-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  min-height: 48px;
  padding: 0 18px;
  border-radius: 50px;
  border: 1px solid rgba(255, 255, 255, 0.6);
  background-color: var(--brand-violet);
  color: #fff;
  font-size: 0.95rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  line-height: 1;
  cursor: pointer;
  z-index: 40;
  transition:
    transform 0.2s ease,
    opacity 0.2s ease;
}
.nav-button:hover {
  transform: translateY(-2px);
  opacity: 0.95;
}
.scroll-top {
  position: fixed;
  right: 24px;
  bottom: 24px;
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.6);
  background-color: var(--brand-violet);
  color: #fff;
  font-size: 1.45rem;
  line-height: 1;
  cursor: pointer;
  z-index: 40;
  transition:
    transform 0.2s ease,
    opacity 0.2s ease;
}
.scroll-top:hover {
  transform: translateY(-2px);
  opacity: 0.95;
}
@media (max-width: 899px) {
  .scroll-top {
    display: none;
  }
}
</style>

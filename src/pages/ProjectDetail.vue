<script setup>
/*** DETTAGLIO PROGETTO — carica il progetto singolo dall'API tramite slug ***/
import { useProjectStore } from '../store';
import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from 'vue-router';
import GridProject from "../components/GridProject.vue";
import ColumnProject from "../components/ColumnProject.vue";
import FullPageLoader from "../components/FullPageLoader.vue";

defineOptions({ inheritAttrs: false })

const route = useRoute();
const projectStore = useProjectStore();

const slug = computed(() => route.params.slug);
const project = computed(() => projectStore.currentProject);
// il loader è attivo finché l'API non risponde
const loading = ref(true)

// carica progetto dall'API; ricarica se lo slug cambia (navigazione tra progetti)
async function loadProject() {
  loading.value = true
  await projectStore.fetchProject(slug.value)
  loading.value = false
}

onMounted(loadProject)

watch(slug, loadProject)
</script>

<template>
  <div v-bind="$attrs">
    <FullPageLoader v-model="loading" />
    <template v-if="project">
      <GridProject v-if="project.layout === 'grid'" :project="project" />
      <ColumnProject v-else-if="project.layout === 'column'" :project="project" />
    </template>
  </div>
</template>

<style scoped lang="scss">
@use "../style/partials/variables" as *;
.project{
  //padding-bottom: 84px;
  //background-color: lighten($bg-about-me, 25%);
  background-color: $bg-light;
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
</style>

<script setup lang="ts">
/*** PAGINA LISTA PROGETTI — carica i progetti dall'API e li ordina per campo 'order' ***/
import { useProjectStore } from '../store';
import { useRouter } from 'vue-router';
import FullPageLoader from "../components/FullPageLoader.vue";
import { computed, onMounted, ref } from "vue";

const projectStore = useProjectStore();
const router = useRouter();
// il loader è attivo finché l'API non risponde
const loading = ref(true)

onMounted(async () => {
  await projectStore.fetchProjects()
  loading.value = false
})

// ordina i progetti per il campo 'order' deciso nel backoffice
const filteredProjects = computed(() =>
  [...projectStore.projects].sort((a, b) => a.order - b.order)
)

function goToProject(slug: string) {
  router.push({ name: "ProjectDetail", params: { slug } });
}

// scroll fluido verso un elemento della pagina
const scrollToId = (id: string) => {
  const el = document.getElementById(id)
  if (!el) return
  const headerH = document.querySelector('nav._fix')?.clientHeight ?? 80
  const y = el.getBoundingClientRect().top + window.scrollY - headerH
  window.scrollTo({ top: y, behavior: 'smooth' })
}
</script>

<template>
  <FullPageLoader v-model="loading" />
  <div class="projects pt-5 header">
    <div class="row justify-content-center mb-5 w-75 d-flex flex-column align-items-center gap-4">
      <div>
        <img src="../assets/img/projectText/1_SCRITTA-19.svg" alt="text" class="text-img-1 d-none d-md-block"/>
        <img src="../assets/img/projectText/1_SCRITTA-20.svg" alt="text" class="text-img-2  d-md-none"/>
      </div>
      <div @click.prevent="scrollToId('projects-gallery')" class="col-3 col-md-2 col-xl-1">
        <img  src="../assets/img/projectText/1_SCRITTA-21.svg" alt="text" class="button-img"/>
      </div>
    </div>
    <div class="gallery" id="projects-gallery">
      <div class="gallery-item mb-1" v-for="project in filteredProjects" :key="project.id" @click="goToProject(project.slug)">
        <img :src="project.hero" :alt="project.title"/>
<!--        <div class="overlay">-->
<!--          <h2>{{ project.title }}</h2>-->
<!--        </div>-->
      <!-- mostriamo il titolo sotto l'immagine        -->
        <div class="text-start mt-2">
          <h5 class="project-title">{{ project.title.toUpperCase() }}</h5>
        </div>
      </div>
    </div>
    <div class="flower">
      <img src="../assets/img/backgrounds/4_FIORE_SINGOLO_04.png" alt="flower" class="flower-img"/>
    </div>
  </div>
</template>

<style scoped lang="scss">
@use "../style/partials/variables" as *;

/* Contenitore pagina */
.projects {
  padding-bottom: 135px;
  display: flex;
  flex-direction: column;
  align-items: center;
  background-color: var(--header-bg);
  color: $bg-light;
  position: relative;
  overflow-x: clip; // moderno, niente scrollbar
  overflow-y: clip;
  @supports not (overflow: clip) {  // fallback per browser vecchi
    overflow-x: hidden;
    overflow-y: hidden;
  }

  /* Galleria */
  .gallery {
    /* Mobile: 1 colonna */
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    width: 80%;
    max-width: 1200px;       /* limita l’ampiezza su desktop */
    padding: 0 16px;         /* respiro laterale */
    margin: 0 20px;

    /* Tablet+ : 3 colonne fisse */
    @media (min-width: 768px) {
      grid-template-columns: repeat(3, 1fr);
      width: 100%;
      gap: 20px;
      padding: 0 20px;
    }

    .gallery-item {
      position: relative;
      cursor: pointer;

      .project-title {
        color: $bg-light;
        font-weight: 300;
        letter-spacing: 1.6px;
      }

      img {
        display: block;
        width: 100%;
        height: auto;             /* evita deformazioni (tolto 100%) */
        object-fit: cover;        /* ok se l’immagine ha un box più alto */
        transition: transform .3s ease;
        cursor: pointer;
        border-radius: 10px;
      }

      &:hover img {
        transform: scale(0.98);
      }
    }
  }
  .flower{
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
}

/* opzionale: offset per scroll all’ancora */
#projects-gallery { scroll-margin-top: 90px; }
</style>

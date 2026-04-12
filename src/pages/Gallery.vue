<script setup>
/*** PAGINA GALLERIA — layout masonry a 3 colonne ***/
import { ref, onMounted } from 'vue'
import { useProjectStore } from '../store'
import MasonryGallery from "../components/MasonryGallery.vue";
import FullPageLoader from "../components/FullPageLoader.vue";

const store = useProjectStore()
const loading = ref(true)

onMounted(async () => {
  await store.fetchGallery()
  loading.value = false
})
</script>

<template>
  <FullPageLoader v-model="loading" />
  <template v-if="!loading">
    <div class="gallery header">
      <h1 class="gallery-title">Illustrazioni</h1>
    </div>
    <MasonryGallery />
  </template>
</template>

<style scoped lang="scss">
@use "../style/partials/variables" as *;

.gallery {
  padding: 32px 16px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  background-color: $bg-light;

  @media (min-width: 768px) {
    padding: 40px 24px 32px;
  }
}

.gallery-title {
  font-size: 2rem;
  color: $text-violet;
  margin-bottom: 0;

  @media (min-width: 768px) {
    font-size: 2.5rem;
  }
}
</style>

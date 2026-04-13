<script setup>
/*** GALLERIA COMPLETA — visualizzazione a 4 colonne con modale di ingrandimento ***/
import { ref, computed, onMounted } from 'vue';
import ImageModal from "./ImageModal.vue";
import { useProjectStore } from '../store';

const projectStore = useProjectStore();
// le 4 colonne di immagini caricate dall'API
const columns = computed(() => projectStore.columns);

onMounted(() => {
  projectStore.fetchGallery()
});

const isModalVisible = ref(false);
const selectedImage = ref({src: '', title: ''});

function getImageUrl(imgName) {
  return new URL(`../assets/img/${imgName}`, import.meta.url).href;
}

function openModal(image) {
  selectedImage.value = image;
  isModalVisible.value = true;
}

function closeModal() {
  isModalVisible.value = false;
  selectedImage.value = {src: '', title: ''};
}

function prevImage() {
  const colCount = columns.value.length;
  let currentColIndex = -1;
  let currentIndexInCol = -1;

  columns.value.forEach((col, colIndex) => {
    const imgIndex = col.indexOf(selectedImage.value);
    if (imgIndex !== -1) {
      currentColIndex = colIndex;
      currentIndexInCol = imgIndex;
    }
  });

  if (currentColIndex === 0 && currentIndexInCol === 0) {
    const lastColIndex = colCount - 1;
    selectedImage.value = columns.value[lastColIndex][columns.value[lastColIndex].length - 1];
  } else {
    if (currentColIndex === 0) {
      const prevIndex = currentIndexInCol - 1;
      if (prevIndex >= 0) {
        selectedImage.value = columns.value[colCount - 1][prevIndex];
      } else {
        selectedImage.value = columns.value[colCount - 1][0];
      }
    } else {
      const prevColIndex = (currentColIndex - 1 + colCount) % colCount;
      if (currentIndexInCol < columns.value[prevColIndex].length) {
        selectedImage.value = columns.value[prevColIndex][currentIndexInCol];
      } else {
        selectedImage.value = columns.value[prevColIndex][columns.value[prevColIndex].length - 1];
      }
    }
  }
}

function nextImage() {
  const colCount = columns.value.length;
  let currentColIndex = -1;
  let currentIndexInCol = -1;

  columns.value.forEach((col, colIndex) => {
    const imgIndex = col.indexOf(selectedImage.value);
    if (imgIndex !== -1) {
      currentColIndex = colIndex;
      currentIndexInCol = imgIndex;
    }
  });

  if (currentColIndex === colCount - 1 && currentIndexInCol === columns.value[currentColIndex].length - 1) {
    selectedImage.value = columns.value[0][0];
  } else {
    if (currentColIndex === colCount - 1) {
      const nextIndex = currentIndexInCol + 1;
      if (nextIndex < columns.value[0].length) {
        selectedImage.value = columns.value[0][nextIndex];
      } else {
        selectedImage.value = columns.value[0][columns.value[0].length - 1];
      }
    } else {
      const nextColIndex = (currentColIndex + 1) % colCount;
      if (currentIndexInCol < columns.value[nextColIndex].length) {
        selectedImage.value = columns.value[nextColIndex][currentIndexInCol];
      } else {
        selectedImage.value = columns.value[nextColIndex][columns.value[nextColIndex].length - 1];
      }
    }
  }
}
</script>

<template>
  <div style="width: 50%">
  </div>
  <div class="appGallery py-5">
    <header class="d-flex flex-column align-items-center mb-3">
      <h1>Letizia Amelia Ragione</h1>
      <ul class="d-flex p-0">
        <li><p>Illustrator -</p></li>
        <li><p>Graphic designer -</p></li>
        <li>
          <p>Handcrafter -</p>
        </li>
        <li><p>Stories maker</p></li>
      </ul>
    </header>
    <div class="row d-flex justify-content-center flex-wrap mx-5">
      <div class="column" v-for="(column, colIndex) in columns" :key="colIndex">
        <img v-for="(image, imgIndex) in column" :key="imgIndex" :src="image.src" :alt="image.title" loading="lazy" @click="openModal(image)" />
      </div>
    </div>
    <transition name="modal-fade">
      <ImageModal :visible="isModalVisible" :imageSrc="selectedImage" @close="closeModal" @prev="prevImage" @next="nextImage" />
    </transition>
  </div>
</template>

<style lang="scss" scoped>
@use "../style/partials/variables" as *;

.appGallery {
  background-color: $bg-light;
}

header {
  color: $text-color;
  h1 {
    letter-spacing: 4px;
  }
  li {
    padding: 0 3px;
  }
}

.column {
  flex: 25%;
  max-width: 25%;
  padding: 0 4px;
}

.column img {
  margin-top: 8px;
  vertical-align: middle;
  width: 100%;
}

@media screen and (max-width: 800px) {
  .column {
    flex: 50%;
    max-width: 50%;
  }
}

@media screen and (max-width: 600px) {
  .column {
    flex: 100%;
    max-width: 100%;
  }
}
video {
  cursor: pointer;
}
img {
  width: 100%;
  height: auto;
  display: block;
  cursor: pointer;
  transition: transform 0.4s ease;

  @media (hover: hover) {
    &:hover {
      transform: scale(.95);
    }
  }
}

// Animazione per il modal
.modal-fade-enter-active, .modal-fade-leave-active {
  transition: opacity 0.5s;
}
.modal-fade-enter, .modal-fade-leave-to {
  opacity: 0;
}

</style>

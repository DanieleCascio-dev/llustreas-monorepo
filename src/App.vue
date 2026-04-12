<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AppHeader from './components/Header.vue'
import AppFooter from './components/Footer.vue'
import LogoLoader from './components/LogoLoader.vue'
import CustomCursor from './components/CustomCursor.vue'

const router = useRouter()
const initialLoad = ref(true)

router.isReady().then(() => {
  requestAnimationFrame(() => {
    // router ready — loader will dismiss after its animation completes
  })
})

function onLoaderComplete() {
  initialLoad.value = false
}
</script>

<template>
  <!-- <CustomCursor /> -->
  <LogoLoader v-if="initialLoad" @complete="onLoaderComplete" />
  <AppHeader />
  <router-view />
  <AppFooter />
</template>

<style lang="scss">
@use "./style/general.scss" as *;
@import "/node_modules/@fortawesome/fontawesome-free/css/all.css";
</style>

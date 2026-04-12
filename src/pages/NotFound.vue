<script setup>
import { RouterLink } from "vue-router";
</script>

<template>
  <div class="not-found">
    <h1 class="not-found__title">Pagina non trovata</h1>
    <RouterLink to="/" class="not-found__link">Torna alla home</RouterLink>
  </div>
</template>

<style scoped lang="scss">
@use "../style/partials/variables" as *;

.not-found {
  min-height: 50vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 2rem 1.5rem;
  color: $text-violet;
  background-color: $bg-light;
}

.not-found__title {
  font-family: "Young Serif", serif;
  font-size: clamp(1.75rem, 4vw, $subtitle);
  margin: 0 0 1rem;
}

.not-found__link {
  margin-top: 0.5rem;
  color: $text-violet;
  font-weight: 600;
  text-decoration: underline;
  text-underline-offset: 0.2em;

  &:hover {
    color: $logo-color;
  }
}
</style>

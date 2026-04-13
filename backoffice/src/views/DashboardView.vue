<script setup>
import { ref, onMounted } from 'vue'
import { projects, gallery } from '../services/api'
import { useToast } from '../composables/useToast'

const toast = useToast()
const loading = ref(true)
const stats = ref({ projects: 0, images: 0, gallery: 0 })

onMounted(async () => {
  try {
    const [p, g] = await Promise.all([projects.list(), gallery.list()])
    stats.value.projects = p.data.length
    stats.value.images = p.data.reduce((sum, pr) => sum + (pr.images_count || 0), 0)
    stats.value.gallery = g.data.length
  } catch {
    toast.error('Errore caricamento dati')
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <div class="page-header">
      <h1>Dashboard</h1>
    </div>

    <div class="grid-3">
      <router-link to="/projects" class="stat-card card">
        <template v-if="loading">
          <div class="stat-skeleton"></div>
        </template>
        <template v-else>
          <div class="stat-value">{{ stats.projects }}</div>
        </template>
        <div class="stat-label">Progetti</div>
      </router-link>
      <div class="stat-card card">
        <template v-if="loading">
          <div class="stat-skeleton"></div>
        </template>
        <template v-else>
          <div class="stat-value">{{ stats.images }}</div>
        </template>
        <div class="stat-label">Immagini nei progetti</div>
      </div>
      <router-link to="/gallery" class="stat-card card">
        <template v-if="loading">
          <div class="stat-skeleton"></div>
        </template>
        <template v-else>
          <div class="stat-value">{{ stats.gallery }}</div>
        </template>
        <div class="stat-label">Immagini galleria</div>
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.stat-card{text-align:center;padding:32px;text-decoration:none;color:inherit;transition:box-shadow var(--transition)}
.stat-card:hover{box-shadow:var(--shadow-lg)}
.stat-value{font-size:48px;font-weight:800;color:var(--primary)}
.stat-label{font-size:14px;color:var(--text-light);margin-top:4px}
.stat-skeleton{width:64px;height:48px;margin:0 auto;border-radius:var(--radius);background:linear-gradient(90deg,var(--border) 25%,var(--bg-main) 50%,var(--border) 75%);background-size:200% 100%;animation:shimmer 1.5s infinite}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
</style>

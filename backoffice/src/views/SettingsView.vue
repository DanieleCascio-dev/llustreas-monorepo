<script setup>
import { ref, onMounted } from 'vue'
import { settings } from '../services/api'
import { useToast } from '../composables/useToast'

const toast = useToast()
const loading = ref(true)

const galleryPreviewMode = ref('carousel')

const modes = [
  { value: 'carousel', label: 'Carosello', desc: 'Scorrimento orizzontale continuo con effetto marquee' },
  { value: 'grid', label: 'Griglia', desc: 'Griglia statica con immagini disposte in colonne' },
]

onMounted(async () => {
  try {
    const { data } = await settings.get('gallery_preview_mode')
    if (data.value) galleryPreviewMode.value = data.value
  } catch {
    // se non esiste ancora il setting, resta il default
  } finally {
    loading.value = false
  }
})

async function save() {
  try {
    await settings.update('gallery_preview_mode', galleryPreviewMode.value)
    toast.success('Impostazione salvata')
  } catch {
    toast.error('Errore nel salvataggio')
  }
}
</script>

<template>
  <div>
    <div class="page-header">
      <h1>Impostazioni</h1>
    </div>

    <div v-if="loading" class="loading-text">Caricamento…</div>

    <div v-else class="settings-list">
      <div class="card setting-card">
        <h2 class="setting-title">Gallery Preview — Homepage</h2>
        <p class="setting-desc">Scegli il componente da utilizzare nella sezione "Illustrazioni" della homepage.</p>

        <div class="mode-options">
          <label
            v-for="mode in modes"
            :key="mode.value"
            class="mode-option"
            :class="{ 'mode-option--active': galleryPreviewMode === mode.value }"
          >
            <input
              type="radio"
              name="galleryPreviewMode"
              :value="mode.value"
              v-model="galleryPreviewMode"
            />
            <div class="mode-content">
              <span class="mode-label">{{ mode.label }}</span>
              <span class="mode-desc">{{ mode.desc }}</span>
            </div>
          </label>
        </div>

        <div class="setting-actions">
          <button class="btn btn--primary" @click="save">Salva</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.loading-text { color: var(--text-light); padding: 24px 0; }

.settings-list { display: flex; flex-direction: column; gap: 20px; }

.setting-card { padding: 28px; }
.setting-title { font-size: 18px; font-weight: 700; margin-bottom: 6px; }
.setting-desc { font-size: 14px; color: var(--text-light); margin-bottom: 20px; }

.mode-options { display: flex; flex-direction: column; gap: 10px; }

.mode-option {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  border: 2px solid var(--border);
  border-radius: 8px;
  cursor: pointer;
  transition: border-color var(--transition), background var(--transition);
}
.mode-option:hover { border-color: var(--primary-light, #9b84c0); }
.mode-option--active {
  border-color: var(--primary);
  background: rgba(91, 60, 136, 0.06);
}

.mode-option input[type="radio"] {
  margin-top: 3px;
  accent-color: var(--primary);
}

.mode-content { display: flex; flex-direction: column; gap: 2px; }
.mode-label { font-weight: 600; font-size: 15px; }
.mode-desc { font-size: 13px; color: var(--text-light); }

.setting-actions { margin-top: 20px; display: flex; justify-content: flex-end; }
</style>

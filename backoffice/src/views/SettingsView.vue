<script setup>
import { ref, onMounted } from 'vue'
import { settings } from '../services/api'
import { useToast } from '../composables/useToast'
import { useKeyboardSave } from '../composables/useKeyboardSave'

const toast = useToast()
const loading = ref(true)
const saving = ref(false)

const galleryPreviewMode = ref('carousel')
const galleryMarqueeSpeed = ref(50)
const instagramMarqueeSpeed = ref(80)

const modes = [
  { value: 'carousel', label: 'Carosello', desc: 'Scorrimento orizzontale continuo con effetto marquee' },
  { value: 'grid', label: 'Griglia', desc: 'Griglia statica con immagini disposte in colonne' },
]

onMounted(async () => {
  try {
    const results = await Promise.allSettled([
      settings.get('gallery_preview_mode'),
      settings.get('gallery_preview_marquee_speed'),
      settings.get('instagram_marquee_speed'),
    ])
    if (results[0].status === 'fulfilled' && results[0].value.data.value)
      galleryPreviewMode.value = results[0].value.data.value
    if (results[1].status === 'fulfilled' && results[1].value.data.value)
      galleryMarqueeSpeed.value = Number(results[1].value.data.value) || 50
    if (results[2].status === 'fulfilled' && results[2].value.data.value)
      instagramMarqueeSpeed.value = Number(results[2].value.data.value) || 80
  } catch { /* keep defaults */ }
  finally { loading.value = false }
})

async function save() {
  if (saving.value) return
  saving.value = true
  try {
    await Promise.all([
      settings.update('gallery_preview_mode', galleryPreviewMode.value),
      settings.update('gallery_preview_marquee_speed', galleryMarqueeSpeed.value),
      settings.update('instagram_marquee_speed', instagramMarqueeSpeed.value),
    ])
    toast.success('Impostazioni salvate')
  } catch {
    toast.error('Errore nel salvataggio')
  } finally {
    saving.value = false
  }
}

useKeyboardSave(save)
</script>

<template>
  <div>
    <div class="page-header">
      <h1>Impostazioni</h1>
      <p class="page-subtitle">Ctrl+S / ⌘S per salvare rapidamente</p>
    </div>

    <div v-if="loading" class="loading-text">Caricamento…</div>

    <div v-else class="settings-list">
      <!-- Gallery Preview Mode -->
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
      </div>

      <!-- Carousel Speeds -->
      <div class="card setting-card">
        <h2 class="setting-title">Velocità caroselli</h2>
        <p class="setting-desc">Controlla la velocità di scorrimento delle sezioni marquee nella homepage.</p>

        <div class="speed-grid">
          <label class="speed-field">
            <span class="speed-field-label">Gallery Preview</span>
            <div class="speed-field-row">
              <input type="range" v-model.number="galleryMarqueeSpeed" min="20" max="200" step="5" class="speed-range" />
              <span class="speed-field-value">{{ galleryMarqueeSpeed }}s</span>
            </div>
          </label>
          <label class="speed-field">
            <span class="speed-field-label">Instagram</span>
            <div class="speed-field-row">
              <input type="range" v-model.number="instagramMarqueeSpeed" min="20" max="200" step="5" class="speed-range" />
              <span class="speed-field-value">{{ instagramMarqueeSpeed }}s</span>
            </div>
          </label>
        </div>
      </div>

      <!-- Save -->
      <div class="setting-actions">
        <button class="btn btn--primary" :disabled="saving" @click="save">
          {{ saving ? 'Salvataggio…' : 'Salva tutto' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page-subtitle { font-size: 13px; color: var(--text-light); margin-top: 4px; }
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

.speed-grid { display: flex; flex-direction: column; gap: 16px; }
.speed-field { display: flex; flex-direction: column; gap: 4px; }
.speed-field-label { font-size: 13px; font-weight: 600; color: var(--text); }
.speed-field-row { display: flex; align-items: center; gap: 10px; }
.speed-range { flex: 1; accent-color: var(--primary); cursor: pointer; }
.speed-field-value { font-size: 13px; font-weight: 600; color: var(--text-light); min-width: 40px; text-align: right; }

.setting-actions { display: flex; justify-content: flex-end; }
</style>

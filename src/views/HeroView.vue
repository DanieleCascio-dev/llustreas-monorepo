<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { hero, heroImages } from '../services/api'
import { uploadImage } from '../services/cloudinary'
import { useToast } from '../composables/useToast'
import CloudinaryBrowser from '../components/CloudinaryBrowser.vue'

const toast = useToast()

const layers = ref([])
const loading = ref(true)
const expanded = ref({})
const saving = ref({})

// ── Anteprima ──
const previewMode = ref('desktop') // 'desktop' | 'mobile'
const previewScroll = ref(0)

// ── Cloudinary browser ──
const showCloudinary = ref(false)
const cloudinaryTarget = ref(null) // { layerId, imageId?, field: 'mobile_src'|'desktop_src', isNew: bool }

// ── Nuovo layer form ──
const showNewLayer = ref(false)
const newLayer = ref({ name: '', type: 'image', z_index: 0, parallax_speed: 0, breakpoint: 450 })

// ── DnD ──
const draggingLayerId = ref(null)
const dropTargetLayerId = ref(null)

// ── Confirm modals ──
const confirmDeleteLayer = ref({ show: false, layer: null })
const confirmDeleteImage = ref({ show: false, layerId: null, image: null })

async function load() {
  loading.value = true
  try {
    const { data } = await hero.list()
    layers.value = data
    if (!Object.keys(expanded.value).length && data.length) {
      expanded.value[data[0].id] = true
    }
  } catch (err) {
    toast.error('Errore caricamento layer hero')
  } finally {
    loading.value = false
  }
}

onMounted(load)

function toggleExpand(id) {
  expanded.value[id] = !expanded.value[id]
}

// ── Layer CRUD ──

async function createLayer() {
  try {
    const { data } = await hero.create(newLayer.value)
    layers.value.push(data)
    expanded.value[data.id] = true
    showNewLayer.value = false
    newLayer.value = { name: '', type: 'image', z_index: 0, parallax_speed: 0, breakpoint: 450 }
    toast.success('Layer creato')
  } catch (err) {
    toast.error('Errore creazione layer')
  }
}

async function saveLayer(layer) {
  saving.value[layer.id] = true
  try {
    await hero.update(layer.id, {
      name: layer.name,
      type: layer.type,
      z_index: layer.z_index,
      parallax_speed: layer.parallax_speed,
      breakpoint: layer.breakpoint,
      css_config: layer.css_config,
    })
    toast.success('Layer salvato')
  } catch (err) {
    toast.error('Errore salvataggio layer')
  } finally {
    saving.value[layer.id] = false
  }
}

function askDeleteLayer(layer) {
  confirmDeleteLayer.value = { show: true, layer }
}

async function doDeleteLayer() {
  const layer = confirmDeleteLayer.value.layer
  if (!layer) return
  try {
    await hero.delete(layer.id)
    layers.value = layers.value.filter(l => l.id !== layer.id)
    toast.success('Layer eliminato')
  } catch (err) {
    toast.error('Errore eliminazione layer')
  } finally {
    confirmDeleteLayer.value = { show: false, layer: null }
  }
}

// ── Layer DnD reorder ──

function onDragStart(e, layer) {
  draggingLayerId.value = layer.id
  e.dataTransfer.effectAllowed = 'move'
}

function onDragOver(e, layer) {
  e.preventDefault()
  dropTargetLayerId.value = layer.id
}

function onDragLeave() {
  dropTargetLayerId.value = null
}

async function onDrop(e, targetLayer) {
  e.preventDefault()
  const srcId = draggingLayerId.value
  const tgtId = targetLayer.id
  draggingLayerId.value = null
  dropTargetLayerId.value = null

  if (srcId === tgtId) return

  const srcIdx = layers.value.findIndex(l => l.id === srcId)
  const tgtIdx = layers.value.findIndex(l => l.id === tgtId)
  if (srcIdx === -1 || tgtIdx === -1) return

  // Swap
  const arr = [...layers.value]
  ;[arr[srcIdx], arr[tgtIdx]] = [arr[tgtIdx], arr[srcIdx]]
  layers.value = arr

  try {
    await hero.reorder({ order: layers.value.map((l, i) => ({ id: l.id, order: i })) })
  } catch (err) {
    toast.error('Errore riordino')
    await load()
  }
}

function onDragEnd() {
  draggingLayerId.value = null
  dropTargetLayerId.value = null
}

// ── Layer Image CRUD ──

function openCloudinaryForImage(layerId, field, imageId = null) {
  cloudinaryTarget.value = { layerId, imageId, field, isNew: !imageId }
  showCloudinary.value = true
}

function openCloudinaryNewSlide(layerId, field) {
  cloudinaryTarget.value = { layerId, imageId: null, field, isNew: true, newSlide: true }
  showCloudinary.value = true
}

async function onCloudinarySelect({ url }) {
  const t = cloudinaryTarget.value
  if (!t) return
  showCloudinary.value = false

  const layer = layers.value.find(l => l.id === t.layerId)
  if (!layer) return

  try {
    if (t.newSlide) {
      // Creiamo una nuova slide con l'immagine scelta nel campo corretto
      const payload = { mobile_src: '', desktop_src: '', alt: '' }
      payload[t.field] = url
      const { data } = await heroImages.create(layer.id, payload)
      layer.images.push(data)
      toast.success('Slide aggiunta')
    } else if (t.imageId) {
      // Aggiorna immagine esistente
      const payload = {}
      payload[t.field] = url
      await heroImages.update(layer.id, t.imageId, payload)
      const img = layer.images.find(i => i.id === t.imageId)
      if (img) img[t.field] = url
      toast.success('Immagine aggiornata')
    } else {
      // Nuova immagine per layer tipo image
      const payload = { mobile_src: '', desktop_src: '', alt: '' }
      payload[t.field] = url
      const { data } = await heroImages.create(layer.id, payload)
      layer.images.push(data)
      toast.success('Immagine aggiunta')
    }
  } catch (err) {
    toast.error('Errore salvataggio immagine')
  }
  cloudinaryTarget.value = null
}

function askDeleteImage(layerId, image) {
  confirmDeleteImage.value = { show: true, layerId, image }
}

async function doDeleteImage() {
  const { layerId, image } = confirmDeleteImage.value
  if (!layerId || !image) return
  try {
    await heroImages.delete(layerId, image.id)
    const layer = layers.value.find(l => l.id === layerId)
    if (layer) {
      layer.images = layer.images.filter(i => i.id !== image.id)
    }
    toast.success('Immagine rimossa')
  } catch (err) {
    toast.error('Errore rimozione immagine')
  } finally {
    confirmDeleteImage.value = { show: false, layerId: null, image: null }
  }
}

// ── Preview computed ──
const previewLayers = computed(() => {
  return [...layers.value].sort((a, b) => a.z_index - b.z_index)
})

function previewTransform(layer) {
  const offset = previewScroll.value * layer.parallax_speed
  return `translateY(${offset}px)`
}

function previewSlideshowTransform(layer) {
  const offset = previewScroll.value * layer.parallax_speed
  return `translate3d(-50%, ${offset}px, 0)`
}

function previewImageSrc(layer) {
  if (!layer.images.length) return null
  const img = layer.images[0]
  return previewMode.value === 'mobile' ? img.mobile_src : img.desktop_src
}

function previewSlides(layer) {
  return layer.images.map(img =>
    previewMode.value === 'mobile' ? img.mobile_src : img.desktop_src
  ).filter(Boolean)
}

function previewSlideDuration(layer) {
  const count = layer.images?.length || 1
  return (count * 4) + 's'
}

function isLastImageLayer(layer) {
  const imageLayers = previewLayers.value.filter(l => l.type === 'image' && l.images?.length)
  return imageLayers.length > 0 && imageLayers[imageLayers.length - 1].id === layer.id
}
</script>

<template>
  <div>
    <div class="page-header">
      <div>
        <h1>Hero</h1>
        <p class="page-subtitle">La sezione grande in cima alla homepage. Ogni livello viene sovrapposto per creare l'effetto di profondità.</p>
      </div>
    </div>

    <div v-if="loading" class="loading-text">Caricamento…</div>

    <div v-else class="hero-editor">
      <!-- Colonna sinistra: editor layer -->
      <div class="editor-col">
        <div
          v-for="layer in layers"
          :key="layer.id"
          class="layer-card"
          :class="{
            'layer-card--dragging': draggingLayerId === layer.id,
            'layer-card--drop-target': dropTargetLayerId === layer.id && draggingLayerId !== layer.id,
          }"
          draggable="true"
          @dragstart="onDragStart($event, layer)"
          @dragover="onDragOver($event, layer)"
          @dragleave="onDragLeave"
          @drop="onDrop($event, layer)"
          @dragend="onDragEnd"
        >
          <div class="layer-header" @click="toggleExpand(layer.id)">
            <span class="layer-drag-handle" title="Trascina per riordinare">⠿</span>
            <span class="layer-expand-icon">{{ expanded[layer.id] ? '▾' : '▸' }}</span>
            <span class="layer-name">{{ layer.name || 'Livello senza nome' }}</span>
            <span class="layer-type-badge">{{ layer.type === 'slideshow' ? 'Slideshow' : 'Immagine' }}</span>
            <button class="btn btn-sm btn-ghost btn-danger-text" @click.stop="askDeleteLayer(layer)" title="Elimina livello">✕</button>
          </div>

          <div v-if="expanded[layer.id]" class="layer-body">
            <!-- Sezione: Info generali -->
            <div class="section-block">
              <div class="section-title">Informazioni generali</div>
              <div class="field-row">
                <label>Nome del livello</label>
                <input v-model="layer.name" class="input" placeholder="Es. Sfondo, Scritte, Fiori…" />
              </div>
              <div class="field-row">
                <div class="label-with-info">
                  <label>Modalità</label>
                  <span class="info-tip" data-tip="Immagine singola: mostra una sola immagine fissa. Slideshow: alterna più immagini con una dissolvenza automatica.">?</span>
                </div>
                <select v-model="layer.type" class="input">
                  <option value="image">Immagine singola</option>
                  <option value="slideshow">Slideshow — alterna più immagini</option>
                </select>
              </div>
            </div>

            <!-- Sezione: Comportamento -->
            <div class="section-block">
              <div class="section-title">Comportamento</div>
              <div class="field-row-group">
                <div class="field-row">
                  <div class="label-with-info">
                    <label>Profondità</label>
                    <span class="info-tip" data-tip="Decide quale livello sta davanti o dietro agli altri. Numero più alto = più in primo piano. Es: sfondo 0, scritte 1, fiori 2.">?</span>
                  </div>
                  <input v-model.number="layer.z_index" type="number" min="0" class="input input--sm" />
                </div>
                <div class="field-row">
                  <div class="label-with-info">
                    <label>Velocità parallax</label>
                    <span class="info-tip" data-tip="Quanto si muove questo livello quando l'utente scorre la pagina. 0 = resta fermo, 0.25 = lento, 1 = molto veloce.">?</span>
                  </div>
                  <input v-model.number="layer.parallax_speed" type="number" min="0" max="1" step="0.05" class="input input--sm" />
                </div>
                <div class="field-row">
                  <div class="label-with-info">
                    <label>Breakpoint</label>
                    <span class="info-tip" data-tip="La larghezza in pixel che separa la versione mobile da quella desktop. Sotto questo valore viene usata l'immagine mobile, sopra quella desktop. Valore consigliato: 576.">?</span>
                  </div>
                  <div class="input-with-unit">
                    <input v-model.number="layer.breakpoint" type="number" min="0" class="input input--sm" />
                    <span class="input-unit">px</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sezione: Immagini -->
            <div class="section-block">
              <div class="section-title">
                {{ layer.type === 'slideshow' ? 'Slides' : 'Immagine' }}
              </div>
              <p class="section-hint" v-if="layer.type === 'slideshow'">
                Le immagini si alternano automaticamente con un effetto di dissolvenza.
              </p>

              <div
                v-for="img in layer.images"
                :key="img.id"
                class="img-row"
              >
                <div class="img-slot">
                  <div class="img-slot-label">
                    <span>Cellulare</span>
                    <span class="img-slot-hint">Schermo verticale</span>
                  </div>
                  <div
                    class="img-thumb"
                    :class="{ 'img-thumb--empty': !img.mobile_src }"
                    @click="openCloudinaryForImage(layer.id, 'mobile_src', img.id)"
                  >
                    <img v-if="img.mobile_src" :src="img.mobile_src" />
                    <div v-else class="img-thumb-empty-content">
                      <span class="img-thumb-plus">+</span>
                      <span class="img-thumb-label">Clicca per scegliere</span>
                    </div>
                  </div>
                </div>
                <div class="img-slot">
                  <div class="img-slot-label">
                    <span>Computer</span>
                    <span class="img-slot-hint">Schermo orizzontale</span>
                  </div>
                  <div
                    class="img-thumb"
                    :class="{ 'img-thumb--empty': !img.desktop_src }"
                    @click="openCloudinaryForImage(layer.id, 'desktop_src', img.id)"
                  >
                    <img v-if="img.desktop_src" :src="img.desktop_src" />
                    <div v-else class="img-thumb-empty-content">
                      <span class="img-thumb-plus">+</span>
                      <span class="img-thumb-label">Clicca per scegliere</span>
                    </div>
                  </div>
                </div>
                <button class="btn btn-sm btn-ghost btn-danger-text img-delete" @click="askDeleteImage(layer.id, img)" title="Elimina questa immagine">🗑</button>
              </div>

              <button
                v-if="layer.type === 'slideshow' || !layer.images.length"
                class="btn btn-sm btn-ghost add-img-btn"
                @click="openCloudinaryNewSlide(layer.id, 'mobile_src')"
              >
                + Aggiungi {{ layer.type === 'slideshow' ? 'slide' : 'immagine' }}
              </button>
            </div>

            <div class="layer-actions">
              <button
                class="btn btn--primary"
                :disabled="saving[layer.id]"
                @click="saveLayer(layer)"
              >
                {{ saving[layer.id] ? 'Salvataggio...' : 'Salva modifiche' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Aggiungi layer -->
        <div v-if="!showNewLayer" class="add-layer-wrap">
          <button class="btn btn-ghost add-layer-btn" @click="showNewLayer = true">+ Aggiungi un nuovo livello</button>
        </div>
        <div v-else class="layer-card new-layer-card">
          <div class="new-layer-header">Nuovo livello</div>
          <div class="layer-body">
            <div class="field-row">
              <label>Nome del livello</label>
              <input v-model="newLayer.name" class="input" placeholder="Es. Sfondo, Scritte, Fiori…" />
            </div>
            <div class="field-row">
              <div class="label-with-info">
                <label>Modalità</label>
                <span class="info-tip" data-tip="Immagine singola: mostra una sola immagine fissa. Slideshow: alterna più immagini con una dissolvenza automatica.">?</span>
              </div>
              <select v-model="newLayer.type" class="input">
                <option value="image">Immagine singola</option>
                <option value="slideshow">Slideshow — alterna più immagini</option>
              </select>
            </div>
            <div class="field-row-group">
              <div class="field-row">
                <div class="label-with-info">
                  <label>Profondità</label>
                  <span class="info-tip" data-tip="Decide quale livello sta davanti o dietro agli altri. Numero più alto = più in primo piano. Es: sfondo 0, scritte 1, fiori 2.">?</span>
                </div>
                <input v-model.number="newLayer.z_index" type="number" min="0" class="input input--sm" />
              </div>
              <div class="field-row">
                <div class="label-with-info">
                  <label>Velocità parallax</label>
                  <span class="info-tip" data-tip="Quanto si muove questo livello quando l'utente scorre la pagina. 0 = resta fermo, 0.25 = lento, 1 = molto veloce.">?</span>
                </div>
                <input v-model.number="newLayer.parallax_speed" type="number" min="0" max="1" step="0.05" class="input input--sm" />
              </div>
              <div class="field-row">
                <div class="label-with-info">
                  <label>Breakpoint</label>
                  <span class="info-tip" data-tip="La larghezza in pixel che separa la versione mobile da quella desktop. Sotto questo valore viene usata l'immagine mobile, sopra quella desktop. Valore consigliato: 576.">?</span>
                </div>
                <div class="input-with-unit">
                  <input v-model.number="newLayer.breakpoint" type="number" min="0" class="input input--sm" />
                  <span class="input-unit">px</span>
                </div>
              </div>
            </div>
            <div class="layer-actions">
              <button class="btn btn--primary" :disabled="!newLayer.name.trim()" @click="createLayer">Crea livello</button>
              <button class="btn btn-ghost" @click="showNewLayer = false">Annulla</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Colonna destra: anteprima live -->
      <div class="preview-col">
        <div class="preview-toolbar">
          <h2 class="preview-heading">Anteprima</h2>
          <div class="preview-mode-toggle">
            <button
              class="btn btn-sm"
              :class="previewMode === 'mobile' ? 'btn--primary' : 'btn-ghost'"
              @click="previewMode = 'mobile'"
            >Cellulare</button>
            <button
              class="btn btn-sm"
              :class="previewMode === 'desktop' ? 'btn--primary' : 'btn-ghost'"
              @click="previewMode = 'desktop'"
            >Computer</button>
          </div>
        </div>

        <div class="preview-box" :class="'preview-box--' + previewMode">
          <div class="preview-hero">
            <template v-for="layer in previewLayers" :key="layer.id">
              <div
                v-if="layer.type === 'image' && previewImageSrc(layer)"
                class="preview-layer"
                :class="{ 'preview-layer--last': isLastImageLayer(layer) }"
                :style="{ zIndex: layer.z_index, transform: previewTransform(layer) }"
              >
                <img :src="previewImageSrc(layer)" alt="" />
              </div>

              <div
                v-else-if="layer.type === 'slideshow' && previewSlides(layer).length"
                class="preview-titles"
                :style="{ zIndex: layer.z_index, transform: previewSlideshowTransform(layer) }"
              >
                <img
                  v-for="(src, si) in previewSlides(layer)"
                  :key="si"
                  :src="src"
                  alt=""
                  class="preview-slide"
                  :style="{
                    animationDelay: (si * 4) + 's',
                    animationDuration: previewSlideDuration(layer),
                  }"
                />
              </div>
            </template>
          </div>
        </div>

        <div class="preview-scroll-control">
          <label>Simula scroll della pagina</label>
          <input type="range" v-model.number="previewScroll" min="0" max="200" class="preview-slider" />
          <span class="preview-scroll-value">{{ previewScroll }}px</span>
        </div>
      </div>
    </div>

    <!-- Cloudinary browser modal -->
    <CloudinaryBrowser
      :visible="showCloudinary"
      @close="showCloudinary = false"
      @select="onCloudinarySelect"
    />

    <!-- Confirm delete layer -->
    <Teleport to="body">
      <div v-if="confirmDeleteLayer.show" class="confirm-backdrop" @click.self="confirmDeleteLayer = { show: false, layer: null }">
        <div class="confirm-modal">
          <h3>Elimina livello</h3>
          <p>Sei sicuro di voler eliminare <strong>{{ confirmDeleteLayer.layer?.name }}</strong> e tutte le sue immagini? Questa azione non si può annullare.</p>
          <div class="confirm-actions">
            <button class="btn btn-sm btn-ghost" @click="confirmDeleteLayer = { show: false, layer: null }">Annulla</button>
            <button class="btn btn-sm btn-danger" @click="doDeleteLayer">Elimina</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Confirm delete image -->
    <Teleport to="body">
      <div v-if="confirmDeleteImage.show" class="confirm-backdrop" @click.self="confirmDeleteImage = { show: false, layerId: null, image: null }">
        <div class="confirm-modal">
          <h3>Elimina immagine</h3>
          <p>Sei sicuro di voler eliminare questa immagine? Questa azione non si può annullare.</p>
          <div class="confirm-actions">
            <button class="btn btn-sm btn-ghost" @click="confirmDeleteImage = { show: false, layerId: null, image: null }">Annulla</button>
            <button class="btn btn-sm btn-danger" @click="doDeleteImage">Elimina</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
/* ── Layout ── */
.page-header { margin-bottom: 28px; }
.page-header h1 { font-size: 24px; font-weight: 800; margin: 0; }
.page-subtitle { font-size: 14px; color: var(--text-light); margin-top: 6px; line-height: 1.5; max-width: 480px; }
.loading-text { color: var(--text-light); padding: 24px 0; }

.hero-editor {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 28px;
  align-items: start;
}

/* ── Editor column ── */
.editor-col {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.layer-card {
  background: var(--bg-card);
  border: 2px solid var(--border);
  border-radius: var(--radius-lg);
  transition: border-color 0.2s, box-shadow 0.2s;
}
.layer-card--dragging {
  opacity: 0.5;
  border-color: var(--primary);
}
.layer-card--drop-target {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(88, 86, 214, 0.15);
}

/* ── Layer header ── */
.layer-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  cursor: pointer;
  user-select: none;
  transition: background 0.15s;
}
.layer-header:hover { background: rgba(0,0,0,0.02); }

.layer-drag-handle {
  cursor: grab;
  color: var(--text-light);
  font-size: 18px;
  line-height: 1;
  opacity: 0.5;
  transition: opacity 0.15s;
}
.layer-header:hover .layer-drag-handle { opacity: 1; }

.layer-expand-icon { font-size: 12px; color: var(--text-light); width: 14px; }
.layer-name { font-weight: 700; font-size: 15px; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.layer-type-badge {
  font-size: 11px;
  background: var(--primary);
  color: #fff;
  padding: 3px 10px;
  border-radius: 10px;
  font-weight: 600;
  white-space: nowrap;
}

/* ── Layer body ── */
.layer-body {
  padding: 0 16px 18px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* ── Section blocks ── */
.section-block {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-top: 4px;
}
.section-block + .section-block {
  border-top: 1px solid var(--border);
  padding-top: 16px;
}
.section-title {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--primary);
  margin-bottom: 2px;
}
.section-hint {
  font-size: 13px;
  color: var(--text-light);
  margin: -4px 0 4px;
  line-height: 1.4;
}

/* ── Fields ── */
.field-row { display: flex; flex-direction: column; gap: 4px; }
.field-row label { font-size: 12px; font-weight: 600; color: var(--text-light); }
.input {
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--bg-main);
  outline: none;
  transition: border-color 0.2s;
}
.input:focus { border-color: var(--primary); }
.input--sm { max-width: 140px; }

.field-row-group {
  display: flex;
  gap: 12px;
}
.field-row-group .field-row { flex: 1; }

/* ── Label con info tooltip ── */
.label-with-info {
  display: flex;
  align-items: center;
  gap: 6px;
}
.label-with-info label { margin: 0; }

.info-tip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: var(--border);
  color: var(--text-light);
  font-size: 10px;
  font-weight: 700;
  cursor: help;
  position: relative;
  flex-shrink: 0;
  transition: background 0.15s, color 0.15s;
}
.info-tip:hover {
  background: var(--primary);
  color: #fff;
}
.info-tip::after {
  content: attr(data-tip);
  position: absolute;
  top: calc(100% + 8px);
  left: -4px;
  background: #1d1d1f;
  color: #fff;
  font-size: 12px;
  font-weight: 400;
  line-height: 1.45;
  padding: 10px 14px;
  border-radius: 8px;
  width: 260px;
  text-align: left;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.15s;
  z-index: 200;
  box-shadow: 0 6px 20px rgba(0,0,0,0.25);
}
.info-tip::before {
  content: '';
  position: absolute;
  top: calc(100% + 2px);
  left: 3px;
  border: 5px solid transparent;
  border-bottom-color: #1d1d1f;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.15s;
  z-index: 200;
}
.info-tip:hover::after,
.info-tip:hover::before {
  opacity: 1;
}

/* ── Input con unità ── */
.input-with-unit {
  display: flex;
  align-items: center;
  gap: 6px;
}
.input-unit {
  font-size: 12px;
  color: var(--text-light);
  font-weight: 600;
}

/* ── Layer images ── */
.img-row {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid var(--border);
}
.img-row:last-of-type { border-bottom: none; }

.img-slot { display: flex; flex-direction: column; gap: 4px; flex: 1; }
.img-slot-label {
  display: flex;
  align-items: baseline;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text);
}
.img-slot-hint {
  font-size: 10px;
  font-weight: 400;
  color: var(--text-light);
}

.img-thumb {
  width: 100%;
  aspect-ratio: 16 / 9;
  border: 2px dashed var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  cursor: pointer;
  background: #2a2a2e;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.img-thumb:hover {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(88, 86, 214, 0.1);
}
.img-thumb img { width: 100%; height: 100%; object-fit: contain; }
.img-thumb--empty { border-style: dashed; background: var(--bg-main); }

.img-thumb-empty-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
}
.img-thumb-plus { font-size: 22px; color: var(--text-light); line-height: 1; }
.img-thumb-label { font-size: 10px; color: var(--text-light); }

.img-delete { flex-shrink: 0; margin-top: 24px; }

.add-img-btn { margin-top: 8px; }

.layer-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  margin-top: 4px;
}

/* ── Add layer ── */
.add-layer-wrap { display: flex; justify-content: center; padding: 8px 0; }
.add-layer-btn { width: 100%; border: 2px dashed var(--border); border-radius: var(--radius-lg); padding: 16px; font-weight: 500; }
.add-layer-btn:hover { border-color: var(--primary); color: var(--primary); }

.new-layer-card {
  border-style: dashed;
  border-color: var(--primary);
}
.new-layer-header {
  padding: 14px 16px 0;
  font-size: 15px;
  font-weight: 700;
  color: var(--primary);
}

/* ── Preview column ── */
.preview-col {
  position: sticky;
  top: 32px;
}

.preview-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.preview-heading { font-size: 16px; font-weight: 700; margin: 0; }

.preview-mode-toggle {
  display: flex;
  gap: 4px;
}

.preview-box {
  border: 2px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  background: #eee;
  transition: max-width 0.3s;
}
.preview-box--desktop { max-width: 100%; }
.preview-box--mobile { max-width: 320px; margin: 0 auto; }

.preview-hero {
  position: relative;
  width: 100%;
  overflow: hidden;
}
.preview-box--desktop .preview-hero {
  aspect-ratio: 16 / 9;
}
.preview-box--mobile .preview-hero {
  aspect-ratio: 9 / 16;
}

.preview-layer {
  position: absolute;
  inset: 0;
  will-change: transform;
  transition: transform 0.05s linear;
}
.preview-layer img {
  display: block;
  width: 100%;
  height: 120%;
  object-fit: cover;
  object-position: center bottom;
}

.preview-layer--last {
  top: auto;
  bottom: 0;
  height: auto;
}
.preview-layer--last img {
  height: auto;
}

.preview-titles {
  position: absolute;
  left: 50%;
  transform: translate3d(-50%, 0, 0);
  will-change: transform;
  pointer-events: none;
  transition: transform 0.05s linear;
}

.preview-box--mobile .preview-titles {
  top: -7%;
  width: 85%;
  height: 35%;
}

.preview-box--desktop .preview-titles {
  top: 10%;
  width: 65%;
}

.preview-slide {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  opacity: 0;
  animation: hero-preview-fade 12s infinite;
}

.preview-box--mobile .preview-slide {
  height: 100%;
}

.preview-box--desktop .preview-slide {
  height: auto;
}

@keyframes hero-preview-fade {
  0%   { opacity: 0; }
  10%  { opacity: 1; }
  30%  { opacity: 1; }
  40%  { opacity: 0; }
  100% { opacity: 0; }
}

/* ── Scroll slider ── */
.preview-scroll-control {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 12px;
  font-size: 13px;
  color: var(--text-light);
}
.preview-scroll-control label { font-weight: 600; white-space: nowrap; }
.preview-slider { flex: 1; }
.preview-scroll-value { width: 44px; text-align: right; font-variant-numeric: tabular-nums; }

/* ── Confirm modal ── */
.confirm-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
}
.confirm-modal {
  background: var(--bg-card);
  border-radius: var(--radius-lg);
  padding: 24px 28px;
  box-shadow: 0 12px 32px rgba(0,0,0,0.25);
  max-width: 400px;
  width: 100%;
}
.confirm-modal h3 { margin: 0 0 8px; font-size: 16px; font-weight: 700; }
.confirm-modal p { margin: 0 0 16px; font-size: 14px; line-height: 1.5; }
.confirm-actions { display: flex; gap: 8px; justify-content: flex-end; }
.btn-danger { background: #ef4444; color: #fff; border: none; border-radius: var(--radius); padding: 6px 16px; font-weight: 600; cursor: pointer; }
.btn-danger:hover { background: #dc2626; }
.btn-danger-text { color: #ef4444; }
.btn-danger-text:hover { color: #dc2626; }
</style>

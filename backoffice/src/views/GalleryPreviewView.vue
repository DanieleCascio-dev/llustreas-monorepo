<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { galleryPreview, gallery, settings } from '../services/api'
import { uploadImage } from '../services/cloudinary'
import { useToast } from '../composables/useToast'
import CloudinaryBrowser from '../components/CloudinaryBrowser.vue'
import { GalleryPreviewCarousel, GalleryPreviewGrid } from '@illustreas/shared-ui'

const toast = useToast()

const images = ref([])
const loading = ref(true)
const previewMode = ref('carousel')
const uploading = ref(false)
const recentlyRemoved = ref([])

const showPicker = ref(false)
const showCloudinary = ref(false)
const galleryImages = ref([])
const galleryLoading = ref(false)

const confirmModal = ref({ show: false, title: '', message: '', thumb: '', onConfirm: null })
const activeSlotMenu = ref(-1)
const targetSlot = ref(null)

const canAdd = computed(() => images.value.length < 9)
const previewSrcs = computed(() => new Set(images.value.map(i => i.src)))

const previewComponent = computed(() =>
  previewMode.value === 'grid' ? GalleryPreviewGrid : GalleryPreviewCarousel
)

const previewImages = computed(() =>
  images.value
    .slice()
    .sort((a, b) => a.order - b.order)
    .map((img) => ({ id: img.id, url: img.src, title: img.title }))
)

const slotMap = computed(() => {
  const map = Array(9).fill(null)
  images.value.forEach(img => {
    if (img.order >= 0 && img.order < 9) map[img.order] = img
  })
  return map
})

onMounted(async () => {
  load()
  document.addEventListener('click', closeSlotMenu)
  try {
    const { data } = await settings.get('gallery_preview_mode')
    if (data.value) previewMode.value = data.value
  } catch { /* keep default */ }
})
onBeforeUnmount(() => {
  document.removeEventListener('click', closeSlotMenu)
})

async function load() {
  loading.value = true
  try {
    const { data } = await galleryPreview.list()
    data.sort((a, b) => a.order - b.order)
    data.forEach((img, idx) => { img.order = idx })
    images.value = data
  } catch {
    toast.error('Errore caricamento gallery preview')
  } finally {
    loading.value = false
  }
}

function askConfirm({ title, message, thumb, onConfirm }) {
  confirmModal.value = { show: true, title, message, thumb: thumb || '', onConfirm }
}

function closeConfirm() {
  confirmModal.value = { show: false, title: '', message: '', thumb: '', onConfirm: null }
}

async function doConfirm() {
  const fn = confirmModal.value.onConfirm
  closeConfirm()
  if (fn) await fn()
}

// --- Slot menu ---

function toggleSlotMenu(slotIdx) {
  activeSlotMenu.value = activeSlotMenu.value === slotIdx ? -1 : slotIdx
}

function closeSlotMenu() {
  activeSlotMenu.value = -1
}

function slotPickGallery(slotIdx) {
  targetSlot.value = slotIdx
  closeSlotMenu()
  openPicker()
}

function slotPickCloudinary(slotIdx) {
  targetSlot.value = slotIdx
  closeSlotMenu()
  showCloudinary.value = true
}

function slotUploadFile(slotIdx) {
  targetSlot.value = slotIdx
  closeSlotMenu()
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*,image/gif'
  input.onchange = (e) => onFileUpload(e)
  input.click()
}

function buildPayload(base) {
  const payload = { ...base }
  if (targetSlot.value !== null) payload.order = targetSlot.value
  return payload
}

function clearTargetSlot() {
  targetSlot.value = null
}

// --- Picker ---

async function openPicker() {
  showPicker.value = true
  if (!galleryImages.value.length) {
    galleryLoading.value = true
    try {
      const { data } = await gallery.list()
      galleryImages.value = data
    } catch {
      toast.error('Errore caricamento galleria')
    } finally {
      galleryLoading.value = false
    }
  }
}

function closePicker() {
  showPicker.value = false
  clearTargetSlot()
}

async function pickFromGallery(gImg) {
  if (previewSrcs.value.has(gImg.src)) return
  if (!canAdd.value) return
  uploading.value = true
  try {
    const { data } = await galleryPreview.create(buildPayload({
      src: gImg.src,
      title: gImg.title || '',
    }))
    images.value.push(data)
    showPicker.value = false
    toast.success('Immagine aggiunta alla preview')
  } catch (err) {
    toast.error('Errore: ' + err.message)
  } finally {
    uploading.value = false
    clearTargetSlot()
  }
}

// --- Cloudinary browser ---

async function onCloudinarySelect({ url, title }) {
  if (!canAdd.value) return
  uploading.value = true
  try {
    const { data } = await galleryPreview.create(buildPayload({ src: url, title }))
    images.value.push(data)
    showCloudinary.value = false
    toast.success('Immagine aggiunta alla preview')
  } catch (err) {
    toast.error('Errore: ' + err.message)
  } finally {
    uploading.value = false
    clearTargetSlot()
  }
}

// --- Upload ---

async function onFileUpload(e) {
  const files = Array.from(e.target.files || [])
  if (!files.length) return
  const remaining = 9 - images.value.length
  const toUpload = files.slice(0, remaining)
  uploading.value = true
  try {
    for (const file of toUpload) {
      const url = await uploadImage(file, 'illustreas/gallery')
      const { data } = await galleryPreview.create(buildPayload({
        src: url,
        title: file.name.replace(/\.[^.]+$/, ''),
      }))
      images.value.push(data)
    }
    toast.success('Immagine caricata')
  } catch (err) {
    toast.error('Errore upload: ' + err.message)
  } finally {
    uploading.value = false
    clearTargetSlot()
    if (e.target?.value !== undefined) e.target.value = ''
  }
}

async function replaceImage(img) {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*,image/gif'
  input.onchange = async (e) => {
    const file = e.target.files?.[0]
    if (!file) return
    uploading.value = true
    try {
      const url = await uploadImage(file, 'illustreas/gallery')
      await galleryPreview.update(img.id, { src: url })
      img.src = url
      toast.success('Immagine sostituita')
    } catch (err) {
      toast.error('Errore upload: ' + err.message)
    } finally {
      uploading.value = false
    }
  }
  input.click()
}

// --- Edit / Remove ---

async function updateTitle(img) {
  try {
    await galleryPreview.update(img.id, { title: img.title })
  } catch {
    toast.error('Errore salvataggio titolo')
  }
}

function requestRemove(img) {
  askConfirm({
    title: 'Rimuovere immagine',
    message: `Vuoi rimuovere "${img.title || 'questa immagine'}" dalla preview?`,
    thumb: img.src,
    onConfirm: () => doRemove(img),
  })
}

async function doRemove(img) {
  try {
    await galleryPreview.delete(img.id)
    images.value = images.value.filter(x => x.id !== img.id)
    recentlyRemoved.value.unshift({ src: img.src, title: img.title, removedAt: Date.now() })
    if (recentlyRemoved.value.length > 20) recentlyRemoved.value.pop()
    toast.success('Immagine rimossa dalla preview')
  } catch {
    toast.error('Errore rimozione dalla preview')
  }
}

async function restoreImage(removed) {
  if (!canAdd.value) {
    toast.error('Preview piena (9/9). Rimuovi un\'immagine prima di ripristinare.')
    return
  }
  uploading.value = true
  try {
    const { data } = await galleryPreview.create({
      src: removed.src,
      title: removed.title || '',
    })
    images.value.push(data)
    recentlyRemoved.value = recentlyRemoved.value.filter(x => x !== removed)
    toast.success('Immagine ripristinata')
  } catch (err) {
    toast.error('Errore ripristino: ' + err.message)
  } finally {
    uploading.value = false
  }
}

function dismissRemoved(removed) {
  recentlyRemoved.value = recentlyRemoved.value.filter(x => x !== removed)
}

// --- Native HTML5 Drag & Drop (fixed 9-slot grid, no shifting) ---

const draggingSlot = ref(-1)
const dropTargetSlot = ref(-1)

function onSlotDragStart(e, slotIdx) {
  draggingSlot.value = slotIdx
  e.dataTransfer.effectAllowed = 'move'
  e.dataTransfer.setData('text/plain', String(slotIdx))
}

function onSlotDragEnter(e, slotIdx) {
  e.preventDefault()
  if (slotIdx !== draggingSlot.value) {
    dropTargetSlot.value = slotIdx
  }
}

function onSlotDragOver(e) {
  e.preventDefault()
}

async function onSlotDrop(e, targetSlot) {
  e.preventDefault()
  const sourceSlot = draggingSlot.value
  if (sourceSlot === -1 || sourceSlot === targetSlot) return

  const sourceImg = slotMap.value[sourceSlot]
  const targetImg = slotMap.value[targetSlot]
  if (!sourceImg) return

  if (targetImg) {
    targetImg.order = sourceSlot
    sourceImg.order = targetSlot
  } else {
    sourceImg.order = targetSlot
  }

  const order = images.value.map(img => ({ id: img.id, order: img.order }))
  try {
    await galleryPreview.reorder(order)
  } catch (e) {
    toast.error('Errore riordino: ' + (e.response?.data?.message || e.message))
    await load()
  } finally {
    draggingSlot.value = -1
    dropTargetSlot.value = -1
  }
}

function onSlotDragEnd() {
  draggingSlot.value = -1
  dropTargetSlot.value = -1
}
</script>

<template>
  <div>
    <div class="page-header">
      <h1>Gallery Preview</h1>
      <span class="counter">{{ images.length }} / 9</span>
    </div>
    <p class="page-desc">Le 9 immagini mostrate nella griglia della homepage. Trascina per riordinare.</p>

    <div v-if="uploading" class="card mb-16" style="text-align:center;padding:16px;color:var(--primary);font-weight:600">
      Caricamento in corso...
    </div>

    <div v-if="loading" class="card" style="text-align:center;padding:48px">Caricamento...</div>

    <template v-else>
      <!-- ===== FRONTOFFICE PREVIEW ===== -->
      <div v-if="previewImages.length" class="fo-preview-wrap">
        <h2 class="section-title">Anteprima frontoffice</h2>
        <component
          :is="previewComponent"
          :images="previewImages"
          section-radius="12px"
          marquee-speed="50s"
        />
      </div>

      <!-- ===== MANAGEMENT ===== -->
      <h2 v-if="previewImages.length" class="section-title mgmt-title">Gestione slot</h2>

      <!-- Fixed 9-slot grid -->
      <div class="preview-grid">
        <div
          v-for="slotIdx in 9"
          :key="slotIdx - 1"
          class="preview-slot"
          :class="{
            'slot--filled': slotMap[slotIdx - 1],
            'slot--empty': !slotMap[slotIdx - 1],
            'slot--dragging': draggingSlot === slotIdx - 1,
            'slot--drop-target': dropTargetSlot === slotIdx - 1 && draggingSlot !== -1,
          }"
          :draggable="!!slotMap[slotIdx - 1]"
          @dragstart="slotMap[slotIdx - 1] && onSlotDragStart($event, slotIdx - 1)"
          @dragenter="onSlotDragEnter($event, slotIdx - 1)"
          @dragover="onSlotDragOver"
          @drop="onSlotDrop($event, slotIdx - 1)"
          @dragend="onSlotDragEnd"
        >
          <!-- Filled slot -->
          <template v-if="slotMap[slotIdx - 1]">
            <div class="preview-img-wrap">
              <img :src="slotMap[slotIdx - 1].src" class="preview-img" draggable="false" />
              <div class="preview-overlay">
                <button class="btn btn-sm btn-ghost overlay-btn" @click="replaceImage(slotMap[slotIdx - 1])">Sostituisci</button>
                <button class="btn btn-sm btn-danger overlay-btn" @click="requestRemove(slotMap[slotIdx - 1])">&#10005;</button>
              </div>
            </div>
            <input
              class="preview-title-input"
              v-model="slotMap[slotIdx - 1].title"
              placeholder="Titolo..."
              @blur="updateTitle(slotMap[slotIdx - 1])"
            />
          </template>

          <!-- Empty slot -->
          <template v-else>
            <div class="slot-empty-inner" @click.stop="toggleSlotMenu(slotIdx - 1)">
              <span class="slot-empty-icon">+</span>
              <span class="slot-empty-label">Aggiungi</span>
            </div>
            <div v-if="activeSlotMenu === slotIdx - 1" class="slot-menu" @click.stop>
              <button class="slot-menu-btn" @click="slotPickGallery(slotIdx - 1)">
                <span class="slot-menu-icon">&#9707;</span> Dalla galleria
              </button>
              <button class="slot-menu-btn" @click="slotPickCloudinary(slotIdx - 1)">
                <span class="slot-menu-icon">&#9729;</span> Da Cloudinary
              </button>
              <button class="slot-menu-btn" @click="slotUploadFile(slotIdx - 1)">
                <span class="slot-menu-icon">+</span> Carica file
              </button>
            </div>
          </template>
        </div>
      </div>

      <div v-if="canAdd" class="add-actions">
        <button class="add-card" @click="openPicker">
          <span class="add-icon">&#9707;</span>
          <span class="add-text">Scegli dalla galleria</span>
        </button>
        <button class="add-card" @click="showCloudinary = true">
          <span class="add-icon">&#9729;</span>
          <span class="add-text">Scegli da Cloudinary</span>
        </button>
        <label class="add-card">
          <span class="add-icon">+</span>
          <span class="add-text">Carica nuova</span>
          <input type="file" accept="image/*,image/gif" multiple hidden @change="onFileUpload" />
        </label>
      </div>

      <!-- Recently removed -->
      <div v-if="recentlyRemoved.length" class="removed-section">
        <h3 class="section-title">Rimosse di recente</h3>
        <div class="removed-list">
          <div v-for="(r, i) in recentlyRemoved" :key="i" class="removed-item">
            <img :src="r.src" class="removed-thumb" />
            <div class="removed-info">
              <span class="removed-name">{{ r.title || 'Senza titolo' }}</span>
            </div>
            <button class="btn btn-sm btn-primary" @click="restoreImage(r)" :disabled="!canAdd">Ripristina</button>
            <button class="btn btn-sm btn-ghost" @click="dismissRemoved(r)" title="Rimuovi dallo storico">&#10005;</button>
          </div>
        </div>
      </div>
    </template>

    <!-- Gallery picker modal -->
    <Teleport to="body">
      <div v-if="showPicker" class="modal-backdrop" @click.self="closePicker">
        <div class="picker-modal">
          <div class="modal-header">
            <h2>Scegli dalla galleria</h2>
            <button class="btn btn-sm btn-ghost" @click="closePicker">&#10005;</button>
          </div>
          <div v-if="galleryLoading" class="modal-body-empty">Caricamento galleria...</div>
          <div v-else-if="!galleryImages.length" class="modal-body-empty">Nessuna immagine in galleria.</div>
          <div v-else class="picker-grid">
            <div
              v-for="gImg in galleryImages"
              :key="gImg.id"
              class="picker-item"
              :class="{ 'picker-item--used': previewSrcs.has(gImg.src), 'picker-item--disabled': !canAdd }"
              @click="pickFromGallery(gImg)"
            >
              <img :src="gImg.src" />
              <div class="picker-item-title">{{ gImg.title || '—' }}</div>
              <div v-if="previewSrcs.has(gImg.src)" class="picker-item-badge">In preview</div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Cloudinary browser -->
    <CloudinaryBrowser
      :visible="showCloudinary"
      @close="showCloudinary = false"
      @select="onCloudinarySelect"
    />

    <!-- Confirm modal -->
    <Teleport to="body">
      <div v-if="confirmModal.show" class="modal-backdrop" @click.self="closeConfirm">
        <div class="confirm-modal">
          <div class="modal-header">
            <h2>{{ confirmModal.title }}</h2>
          </div>
          <div class="confirm-body">
            <img v-if="confirmModal.thumb" :src="confirmModal.thumb" class="confirm-thumb" />
            <p>{{ confirmModal.message }}</p>
          </div>
          <div class="confirm-footer">
            <button class="btn btn-ghost" @click="closeConfirm">Annulla</button>
            <button class="btn btn-danger" @click="doConfirm">Rimuovi</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.fo-preview-wrap{margin-bottom:32px}
.mgmt-title{margin-bottom:16px}
.page-desc{color:var(--text-light);font-size:14px;margin-bottom:24px}
.counter{font-size:14px;font-weight:600;color:var(--text-light);background:var(--bg-card);padding:4px 12px;border-radius:var(--radius);border:1px solid var(--border)}

/* Fixed 9-slot grid — compact */
.preview-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px;max-width:480px}

.preview-slot{border-radius:var(--radius);overflow:hidden;border:2px solid transparent;transition:border-color .15s,box-shadow .15s,background .15s}
.slot--filled{background:var(--bg-card);box-shadow:var(--shadow);cursor:grab;border-color:var(--border)}
.slot--filled:active{cursor:grabbing}
.slot--empty{border:2px dashed var(--border);background:var(--bg-main)}

.slot--dragging{border-color:var(--primary) !important;opacity:.65}
.slot--drop-target{border-color:var(--primary) !important;box-shadow:0 0 0 2px rgba(99,102,241,.18);background:rgba(99,102,241,.04)}

.preview-img-wrap{position:relative;aspect-ratio:1;overflow:hidden}
.preview-img{width:100%;height:100%;object-fit:cover;display:block;pointer-events:none}
.preview-overlay{position:absolute;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;gap:4px;opacity:0;transition:opacity var(--transition)}
.slot--filled:hover .preview-overlay{opacity:1}
.overlay-btn{color:#fff;border-color:rgba(255,255,255,.4);font-size:11px;padding:3px 8px}
.overlay-btn:hover{border-color:#fff}

.preview-title-input{width:100%;border:0;padding:6px 8px;font-size:11px;background:transparent;outline:none;border-top:1px solid var(--border)}
.preview-title-input:focus{background:var(--bg-main)}

.slot-empty-inner{display:flex;flex-direction:column;align-items:center;justify-content:center;aspect-ratio:1;gap:2px;color:var(--text-light);user-select:none;cursor:pointer;transition:all .15s}
.slot-empty-inner:hover{color:var(--primary);background:rgba(99,102,241,.04)}
.slot-empty-icon{font-size:20px;font-weight:300;line-height:1;opacity:.6;transition:opacity .15s}
.slot-empty-inner:hover .slot-empty-icon{opacity:1}
.slot-empty-label{font-size:10px;opacity:.5;transition:opacity .15s}
.slot-empty-inner:hover .slot-empty-label{opacity:.8}

.slot-menu{display:flex;flex-direction:column;gap:1px;padding:4px;background:var(--bg-card);border-top:1px solid var(--border)}
.slot-menu-btn{display:flex;align-items:center;gap:6px;width:100%;padding:5px 8px;border:none;background:transparent;color:var(--text);font-size:11px;border-radius:var(--radius);cursor:pointer;transition:background .15s}
.slot-menu-btn:hover{background:rgba(99,102,241,.08);color:var(--primary)}
.slot-menu-icon{width:14px;text-align:center;font-size:12px;opacity:.6}

.add-actions{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;max-width:480px}
.add-card{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;min-height:72px;background:var(--bg-card);border:2px dashed var(--border);border-radius:var(--radius);cursor:pointer;transition:all var(--transition);color:var(--text-light)}
.add-card:hover{border-color:var(--primary);color:var(--primary);background:rgba(99,102,241,.04)}
.add-icon{font-size:18px;font-weight:300;line-height:1}
.add-text{font-size:11px;font-weight:500}

/* Recently removed — compact */
.removed-section{margin-top:20px}
.section-title{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-light);margin-bottom:8px}
.removed-list{display:flex;flex-direction:column;gap:4px}
.removed-item{display:flex;align-items:center;gap:8px;padding:6px 10px;background:var(--bg-card);border-radius:var(--radius);border:1px solid var(--border)}
.removed-thumb{width:32px;height:32px;object-fit:cover;border-radius:var(--radius);flex-shrink:0}
.removed-info{flex:1;min-width:0}
.removed-name{font-size:12px;font-weight:500;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

/* Shared modal styles */
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:flex;align-items:center;justify-content:center;padding:24px}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--border)}
.modal-header h2{font-size:18px;font-weight:700;margin:0}
.modal-body-empty{padding:48px;text-align:center;color:var(--text-light);font-size:14px}

/* Picker modal */
.picker-modal{background:var(--bg-card);border-radius:var(--radius-lg);box-shadow:0 24px 48px rgba(0,0,0,.2);width:100%;max-width:780px;max-height:80vh;display:flex;flex-direction:column;overflow:hidden}
.picker-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;padding:20px 24px;overflow-y:auto}
.picker-item{position:relative;border-radius:var(--radius);overflow:hidden;cursor:pointer;border:2px solid transparent;transition:all var(--transition)}
.picker-item:hover{border-color:var(--primary);transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.1)}
.picker-item--used{opacity:.4;cursor:default;pointer-events:none}
.picker-item--disabled:not(.picker-item--used){opacity:.5;cursor:not-allowed}
.picker-item img{width:100%;aspect-ratio:1;object-fit:cover;display:block}
.picker-item-title{padding:6px 8px;font-size:11px;color:var(--text-light);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;background:var(--bg-main)}
.picker-item-badge{position:absolute;top:6px;right:6px;background:rgba(0,0,0,.6);color:#fff;font-size:10px;padding:2px 6px;border-radius:4px}

/* Confirm modal */
.confirm-modal{background:var(--bg-card);border-radius:var(--radius-lg);box-shadow:0 24px 48px rgba(0,0,0,.2);width:100%;max-width:400px;overflow:hidden}
.confirm-body{padding:24px;display:flex;flex-direction:column;align-items:center;gap:16px;text-align:center}
.confirm-body p{margin:0;font-size:14px;color:var(--text);line-height:1.5}
.confirm-thumb{width:100px;height:100px;object-fit:cover;border-radius:var(--radius)}
.confirm-footer{display:flex;justify-content:flex-end;gap:8px;padding:16px 24px;border-top:1px solid var(--border)}
</style>

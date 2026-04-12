<script setup>
import { ref, computed, onMounted } from 'vue'
import { gallery } from '../services/api'
import { uploadImage } from '../services/cloudinary'
import { useToast } from '../composables/useToast'
import CloudinaryBrowser from '../components/CloudinaryBrowser.vue'

const toast = useToast()

const images = ref([])
const loading = ref(true)
const uploading = ref(false)
const showCloudinary = ref(false)
const cloudinaryTargetCol = ref(0)

const columns = computed(() => {
  const cols = [[], [], []]
  const sorted = Array.from(images.value)
    .sort((a, b) => a.column_index === b.column_index ? a.order - b.order : a.column_index - b.column_index)
  sorted.forEach(img => {
    if (img.column_index >= 0 && img.column_index <= 2) {
      cols[img.column_index].push(img)
    }
  })
  return cols
})

onMounted(load)

async function load() {
  loading.value = true
  try {
    const { data } = await gallery.list()
    images.value = data
  } catch {
    toast.error('Errore caricamento galleria')
  } finally {
    loading.value = false
  }
}

async function onFileUpload(e, colIndex) {
  const files = Array.from(e.target.files || [])
  if (!files.length) return
  uploading.value = true
  try {
    for (const file of files) {
      const url = await uploadImage(file, 'illustreas/gallery')
      const { data } = await gallery.create({
        src: url,
        title: file.name.replace(/\.[^.]+$/, ''),
        column_index: colIndex,
        is_preview: false,
      })
      images.value.push(data)
    }
    toast.success('Immagine caricata')
  } catch (err) {
    toast.error('Errore upload: ' + err.message)
  } finally {
    uploading.value = false
    e.target.value = ''
  }
}

function openCloudinary(colIndex) {
  cloudinaryTargetCol.value = colIndex
  showCloudinary.value = true
}

async function onCloudinarySelect({ url, title }) {
  uploading.value = true
  try {
    const { data } = await gallery.create({
      src: url,
      title: title || '',
      column_index: cloudinaryTargetCol.value,
      is_preview: false,
    })
    images.value.push(data)
    showCloudinary.value = false
    toast.success('Immagine aggiunta')
  } catch (err) {
    toast.error('Errore: ' + err.message)
  } finally {
    uploading.value = false
  }
}

// --- Modal di conferma eliminazione ---

const confirmModal = ref({ show: false, thumb: '', title: '', onConfirm: null })

function requestRemove(img) {
  confirmModal.value = {
    show: true,
    thumb: img.src,
    title: img.title || 'questa immagine',
    onConfirm: () => doRemove(img),
  }
}

function closeConfirm() {
  confirmModal.value = { show: false, thumb: '', title: '', onConfirm: null }
}

async function doConfirm() {
  const fn = confirmModal.value.onConfirm
  closeConfirm()
  if (fn) await fn()
}

async function doRemove(img) {
  try {
    await gallery.delete(img.id)
    images.value = images.value.filter(x => x.id !== img.id)
    recentlyRemoved.value.unshift({
      src: img.src,
      title: img.title,
      column_index: img.column_index,
      removedAt: Date.now(),
    })
    if (recentlyRemoved.value.length > 20) recentlyRemoved.value.pop()
    toast.success('Immagine rimossa')
  } catch {
    toast.error('Errore eliminazione immagine')
  }
}

// --- Ripristino immagini rimosse ---

const recentlyRemoved = ref([])

async function restoreImage(removed) {
  uploading.value = true
  try {
    const { data } = await gallery.create({
      src: removed.src,
      title: removed.title || '',
      column_index: removed.column_index,
      is_preview: false,
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

// --- Native HTML5 Drag & Drop (swap, purple borders) ---

const draggingId = ref(null)
const dropTargetId = ref(null)

function onItemDragStart(e, img) {
  draggingId.value = img.id
  e.dataTransfer.effectAllowed = 'move'
  e.dataTransfer.setData('text/plain', String(img.id))
}

function onItemDragEnter(e, img) {
  e.stopPropagation()
  if (draggingId.value && img.id !== draggingId.value) {
    dropTargetId.value = img.id
  }
}

// Quando il cursore entra nello spazio vuoto del container, rimuove l'highlight
function onContainerDragEnter(e) {
  if (e.target === e.currentTarget) {
    dropTargetId.value = null
  }
}

async function performSwap(srcId, tgtId) {
  if (!srcId || !tgtId || srcId === tgtId) return

  const srcImg = images.value.find(i => i.id === srcId)
  const tgtImg = images.value.find(i => i.id === tgtId)
  if (!srcImg || !tgtImg) return

  const srcCol = srcImg.column_index
  const srcOrder = srcImg.order
  const tgtCol = tgtImg.column_index
  const tgtOrder = tgtImg.order

  srcImg.column_index = tgtCol
  srcImg.order = tgtOrder
  tgtImg.column_index = srcCol
  tgtImg.order = srcOrder

  // Ri-sequenzia localmente e persisti su API
  const affectedCols = new Set([srcCol, tgtCol])
  try {
    for (const ci of affectedCols) {
      const col = images.value
        .filter(i => i.column_index === ci)
        .sort((a, b) => a.order - b.order)
      col.forEach((img, i) => { img.order = i })
      const order = col.map((img, i) => ({ id: img.id, column_index: ci, order: i }))
      if (order.length) await gallery.reorder(order)
    }
  } catch (e) {
    toast.error('Errore riordino: ' + (e.response?.data?.message || e.message))
    await load()
  }
}

async function handleDrop(e, directTarget = null) {
  e.preventDefault()
  e.stopPropagation()

  const srcId = draggingId.value
  const tgtId = directTarget ? directTarget.id : dropTargetId.value

  draggingId.value = null
  dropTargetId.value = null
  dropZoneCol.value = -1

  if (srcId && tgtId && srcId !== tgtId) {
    await performSwap(srcId, tgtId)
  }
}

// --- Drop zone "+" in fondo alla colonna: sposta immagine in coda ---

const dropZoneCol = ref(-1)

function onZoneDragEnter(e, colIndex) {
  e.stopPropagation()
  if (draggingId.value) {
    dropTargetId.value = null
    dropZoneCol.value = colIndex
  }
}

async function handleZoneDrop(e, colIndex) {
  e.preventDefault()
  e.stopPropagation()

  const srcId = draggingId.value
  draggingId.value = null
  dropTargetId.value = null
  dropZoneCol.value = -1

  if (!srcId) return

  const srcImg = images.value.find(i => i.id === srcId)
  if (!srcImg) return

  // Se è già in fondo a questa colonna, niente da fare
  if (srcImg.column_index === colIndex) {
    const colImgs = images.value.filter(i => i.column_index === colIndex)
    const maxOrder = Math.max(...colImgs.map(i => i.order))
    if (srcImg.order === maxOrder) return
  }

  const oldCol = srcImg.column_index
  const newOrder = images.value
    .filter(i => i.column_index === colIndex)
    .length

  srcImg.column_index = colIndex
  srcImg.order = newOrder

  // Ri-sequenzia colonne coinvolte
  const affectedCols = new Set([oldCol, colIndex])
  try {
    for (const ci of affectedCols) {
      const col = images.value
        .filter(i => i.column_index === ci)
        .sort((a, b) => a.order - b.order)
      col.forEach((img, i) => { img.order = i })
      const order = col.map((img, i) => ({ id: img.id, column_index: ci, order: i }))
      if (order.length) await gallery.reorder(order)
    }
  } catch (e) {
    toast.error('Errore riordino: ' + (e.response?.data?.message || e.message))
    await load()
  }
}

function onItemDragEnd() {
  draggingId.value = null
  dropTargetId.value = null
  dropZoneCol.value = -1
}
</script>

<template>
  <div>
    <div class="page-header">
      <h1>Galleria</h1>
    </div>

    <div v-if="uploading" class="card mb-16" style="text-align:center;padding:16px;color:var(--primary);font-weight:600">
      Caricamento in corso...
    </div>

    <div v-if="loading" class="card" style="text-align:center;padding:48px">Caricamento...</div>

    <div v-else class="gallery-grid">
      <div v-for="colIndex in [0, 1, 2]" :key="colIndex" class="gallery-col">
        <div class="col-header">
          <span class="col-title">Colonna {{ colIndex + 1 }}</span>
          <div class="col-actions">
            <button class="btn btn-sm btn-ghost" @click="openCloudinary(colIndex)" title="Scegli da Cloudinary">&#9729;</button>
            <label class="btn btn-sm btn-ghost">
              +
              <input type="file" accept="image/*" multiple hidden @change="e => onFileUpload(e, colIndex)" />
            </label>
          </div>
        </div>

        <div
          class="col-images"
          @dragover.prevent
          @dragenter="onContainerDragEnter"
          @drop.prevent="handleDrop"
        >
          <div
            v-for="img in columns[colIndex]"
            :key="img.id"
            class="gallery-item"
            :class="{
              'item--dragging': draggingId === img.id,
              'item--drop-target': dropTargetId === img.id && draggingId,
            }"
            draggable="true"
            @dragstart="onItemDragStart($event, img)"
            @dragenter.prevent="onItemDragEnter($event, img)"
            @dragover.prevent
            @drop.prevent.stop="handleDrop($event, img)"
            @dragend="onItemDragEnd"
          >
            <img :src="img.src" class="gallery-thumb" draggable="false" />
            <div class="gallery-item-overlay">
              <span class="gallery-item-title">{{ img.title }}</span>
              <div class="gallery-item-actions">
                <button class="btn btn-sm btn-danger" @click="requestRemove(img)">&#10005;</button>
              </div>
            </div>
          </div>

          <!-- Drop zone / upload in fondo alla colonna -->
          <label
            class="col-add-zone"
            :class="{ 'zone--active': dropZoneCol === colIndex && draggingId }"
            @dragenter.prevent="onZoneDragEnter($event, colIndex)"
            @dragover.prevent
            @drop.prevent.stop="handleZoneDrop($event, colIndex)"
          >
            <span class="zone-icon">+</span>
            <span class="zone-text">Aggiungi</span>
            <input type="file" accept="image/*" multiple hidden @change="e => onFileUpload(e, colIndex)" />
          </label>
        </div>
      </div>
    </div>

    <!-- Rimosse di recente -->
    <div v-if="recentlyRemoved.length" class="removed-section">
      <h3 class="section-title">Rimosse di recente</h3>
      <div class="removed-list">
        <div v-for="(r, i) in recentlyRemoved" :key="i" class="removed-item">
          <img :src="r.src" class="removed-thumb" />
          <div class="removed-info">
            <span class="removed-name">{{ r.title || 'Senza titolo' }}</span>
            <span class="removed-col">Colonna {{ r.column_index + 1 }}</span>
          </div>
          <button class="btn btn-sm btn-primary" @click="restoreImage(r)">Ripristina</button>
          <button class="btn btn-sm btn-ghost" @click="dismissRemoved(r)" title="Rimuovi dallo storico">&#10005;</button>
        </div>
      </div>
    </div>

    <CloudinaryBrowser
      :visible="showCloudinary"
      @close="showCloudinary = false"
      @select="onCloudinarySelect"
    />

    <!-- Modal conferma eliminazione -->
    <Teleport to="body">
      <div v-if="confirmModal.show" class="modal-backdrop" @click.self="closeConfirm">
        <div class="confirm-modal">
          <div class="confirm-body">
            <img v-if="confirmModal.thumb" :src="confirmModal.thumb" class="confirm-thumb" />
            <p>Sei sicuro di voler eliminare <strong>{{ confirmModal.title }}</strong>?</p>
          </div>
          <div class="confirm-footer">
            <button class="btn btn-ghost" @click="closeConfirm">No</button>
            <button class="btn btn-danger" @click="doConfirm">Sì</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.gallery-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;align-items:start}
.gallery-col{background:var(--bg-card);border-radius:var(--radius-lg);box-shadow:var(--shadow);overflow:hidden}
.col-header{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--border)}
.col-title{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-light)}
.col-actions{display:flex;gap:4px}
.col-images{min-height:100px;padding:8px;display:flex;flex-direction:column;gap:8px}

.gallery-item{position:relative;border-radius:var(--radius);overflow:hidden;cursor:grab;border:2px solid var(--border);transition:border-color .15s,box-shadow .15s,opacity .15s}
.gallery-item:active{cursor:grabbing}

.item--dragging{border-color:var(--primary) !important;opacity:.6}
.item--drop-target{border-color:var(--primary) !important;box-shadow:0 0 0 3px rgba(99,102,241,.18);background:rgba(99,102,241,.04)}

.gallery-thumb{width:100%;height:auto;object-fit:cover;display:block;pointer-events:none}
.gallery-item-overlay{position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,.7));padding:8px;display:flex;align-items:end;justify-content:space-between;opacity:0;transition:opacity var(--transition);pointer-events:none}
.gallery-item:hover .gallery-item-overlay{opacity:1}
.gallery-item-title{color:#fff;font-size:12px;font-weight:500;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.gallery-item-actions{display:flex;gap:4px;pointer-events:auto}

.col-add-zone{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;min-height:72px;border:2px dashed var(--border);border-radius:var(--radius);cursor:pointer;color:var(--text-light);transition:all .15s;user-select:none}
.col-add-zone:hover{border-color:var(--primary);color:var(--primary);background:rgba(99,102,241,.04)}
.zone--active{border-color:var(--primary) !important;color:var(--primary) !important;background:rgba(99,102,241,.08) !important;box-shadow:0 0 0 3px rgba(99,102,241,.18)}
.zone-icon{font-size:22px;font-weight:300;line-height:1}
.zone-text{font-size:11px;font-weight:500}

/* Rimosse di recente */
.removed-section{margin-top:32px}
.section-title{font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-light);margin-bottom:12px}
.removed-list{display:flex;flex-direction:column;gap:8px}
.removed-item{display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--bg-card);border-radius:var(--radius);border:1px solid var(--border)}
.removed-thumb{width:48px;height:48px;object-fit:cover;border-radius:var(--radius);flex-shrink:0}
.removed-info{flex:1;min-width:0;display:flex;flex-direction:column;gap:2px}
.removed-name{font-size:13px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.removed-col{font-size:11px;color:var(--text-light)}

/* Modal conferma */
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:flex;align-items:center;justify-content:center;padding:24px}
.confirm-modal{background:var(--bg-card);border-radius:var(--radius-lg);box-shadow:0 24px 48px rgba(0,0,0,.2);width:100%;max-width:400px;overflow:hidden}
.confirm-body{padding:24px;display:flex;flex-direction:column;align-items:center;gap:16px;text-align:center}
.confirm-body p{margin:0;font-size:14px;color:var(--text);line-height:1.5}
.confirm-thumb{width:100px;height:100px;object-fit:cover;border-radius:var(--radius)}
.confirm-footer{display:flex;justify-content:flex-end;gap:8px;padding:16px 24px;border-top:1px solid var(--border)}
</style>

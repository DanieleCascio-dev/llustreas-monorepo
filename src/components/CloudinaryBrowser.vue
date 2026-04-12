<script setup>
import { ref, computed, watch } from 'vue'
import { cloudinaryBrowser } from '../services/api'
import { uploadImage } from '../services/cloudinary'
import { useToast } from '../composables/useToast'

const toast = useToast()

const props = defineProps({
  visible: Boolean,
})
const emit = defineEmits(['close', 'select'])

const currentPath = ref('illustreas')
const subFolders = ref([])
const images = ref([])
const foldersLoading = ref(false)
const imagesLoading = ref(false)
const nextCursor = ref(null)
const loadingMore = ref(false)
const showNewFolder = ref(false)
const newFolderName = ref('')
const creatingFolder = ref(false)
const confirmDelete = ref({ show: false, folder: null })
const deletingFolder = ref(false)

const protectedFolders = ['illustreas', 'illustreas/gallery', 'illustreas/projects', 'illustreas/covers']

const breadcrumb = computed(() => {
  const parts = currentPath.value.split('/')
  return parts.map((part, i) => ({
    name: part,
    path: parts.slice(0, i + 1).join('/'),
  }))
})

watch(() => props.visible, async (val) => {
  if (val) {
    await navigateTo('illustreas')
  }
})

async function navigateTo(path) {
  currentPath.value = path
  subFolders.value = []
  images.value = []
  nextCursor.value = null

  foldersLoading.value = true
  imagesLoading.value = true

  try {
    const [foldersRes, imagesRes] = await Promise.all([
      cloudinaryBrowser.folders(path),
      cloudinaryBrowser.images(path),
    ])
    subFolders.value = foldersRes.data.folders || []
    images.value = imagesRes.data.images || []
    nextCursor.value = imagesRes.data.next_cursor || null
  } finally {
    foldersLoading.value = false
    imagesLoading.value = false
  }
}

async function loadMoreImages() {
  if (!nextCursor.value) return
  loadingMore.value = true
  try {
    const { data } = await cloudinaryBrowser.images(currentPath.value, nextCursor.value)
    images.value.push(...(data.images || []))
    nextCursor.value = data.next_cursor || null
  } finally {
    loadingMore.value = false
  }
}

async function createFolder() {
  const name = newFolderName.value.trim().replace(/[^a-zA-Z0-9_-]/g, '_')
  if (!name) return
  creatingFolder.value = true
  try {
    const fullPath = currentPath.value + '/' + name
    await cloudinaryBrowser.createFolder(fullPath)
    showNewFolder.value = false
    newFolderName.value = ''
    await navigateTo(currentPath.value)
  } catch (err) {
    toast.error('Errore: ' + (err.response?.data?.message || err.message))
  } finally {
    creatingFolder.value = false
  }
}

function cancelNewFolder() {
  showNewFolder.value = false
  newFolderName.value = ''
}

function askDeleteFolder(folder) {
  confirmDelete.value = { show: true, folder }
}

function cancelDelete() {
  confirmDelete.value = { show: false, folder: null }
}

async function doDeleteFolder() {
  const folder = confirmDelete.value.folder
  if (!folder) return
  deletingFolder.value = true
  try {
    await cloudinaryBrowser.deleteFolder(folder.path)
    cancelDelete()
    await navigateTo(currentPath.value)
  } catch (err) {
    toast.error(err.response?.data?.message || err.message)
  } finally {
    deletingFolder.value = false
  }
}

const uploadingHere = ref(false)

async function uploadToCurrentFolder(e) {
  const file = e.target.files?.[0]
  if (!file || !file.type.startsWith('image/')) return
  uploadingHere.value = true
  try {
    const url = await uploadImage(file, currentPath.value)
    emit('select', { url, title: file.name.replace(/\.[^.]+$/, '') })
  } catch (err) {
    toast.error('Errore upload: ' + (err.response?.data?.message || err.message))
  } finally {
    uploadingHere.value = false
    e.target.value = ''
  }
}

function selectImage(img) {
  emit('select', { url: img.url, title: img.public_id.split('/').pop() })
}

function close() {
  emit('close')
}

function formatSize(bytes) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}
</script>

<template>
  <Teleport to="body">
    <div v-if="visible" class="cb-backdrop" @click.self="close">
      <div class="cb-modal">
        <div class="cb-header">
          <h2>Cloudinary</h2>
          <button class="btn btn-sm btn-ghost" @click="close">✕</button>
        </div>

        <!-- Breadcrumb -->
        <div class="cb-breadcrumb">
          <div class="cb-breadcrumb-path">
            <span
              v-for="(crumb, i) in breadcrumb"
              :key="crumb.path"
              class="cb-crumb"
              :class="{ 'cb-crumb--active': i === breadcrumb.length - 1 }"
              @click="i < breadcrumb.length - 1 && navigateTo(crumb.path)"
            >
              {{ crumb.name }}<span v-if="i < breadcrumb.length - 1" class="cb-crumb-sep">/</span>
            </span>
          </div>
          <div class="cb-breadcrumb-actions">
            <label class="btn btn-sm btn-primary cb-upload-here-btn">
              {{ uploadingHere ? 'Caricamento...' : '⬆ Carica qui' }}
              <input type="file" accept="image/*" hidden :disabled="uploadingHere" @change="uploadToCurrentFolder" />
            </label>
            <button v-if="!showNewFolder" class="btn btn-sm btn-ghost" @click="showNewFolder = true">+ Nuova cartella</button>
          </div>
        </div>
        <div v-if="showNewFolder" class="cb-new-folder">
          <span class="cb-new-folder-prefix">{{ currentPath }}/</span>
          <input
            v-model="newFolderName"
            class="cb-new-folder-input"
            placeholder="nome_cartella"
            autofocus
            @keyup.enter="createFolder"
            @keyup.escape="cancelNewFolder"
          />
          <button class="btn btn-sm btn-primary" :disabled="creatingFolder || !newFolderName.trim()" @click="createFolder">
            {{ creatingFolder ? '...' : 'Crea' }}
          </button>
          <button class="btn btn-sm btn-ghost" @click="cancelNewFolder">Annulla</button>
        </div>

        <div class="cb-body">
          <!-- Sub-folders -->
          <div v-if="foldersLoading && !subFolders.length" class="cb-section-loading">Caricamento cartelle...</div>
          <div v-if="subFolders.length" class="cb-folders-grid">
            <div
              v-for="f in subFolders"
              :key="f.path"
              class="cb-folder-card"
              @click="navigateTo(f.path)"
            >
              <div class="cb-folder-icon">📁</div>
              <div class="cb-folder-name">{{ f.name }}</div>
              <button
                v-if="!protectedFolders.includes(f.path)"
                class="cb-folder-delete"
                title="Elimina cartella"
                @click.stop="askDeleteFolder(f)"
              >🗑</button>
            </div>
          </div>

          <!-- Images -->
          <div v-if="imagesLoading && !images.length" class="cb-section-loading">Caricamento immagini...</div>
          <div v-else-if="!images.length && !subFolders.length && !foldersLoading && !imagesLoading" class="cb-empty">Cartella vuota.</div>
          <template v-if="images.length">
            <div v-if="subFolders.length" class="cb-divider"></div>
            <div class="cb-grid">
              <div
                v-for="img in images"
                :key="img.public_id"
                class="cb-item"
                @click="selectImage(img)"
              >
                <img :src="img.url" loading="lazy" />
                <div class="cb-item-info">
                  <span class="cb-item-name">{{ img.public_id.split('/').pop() }}</span>
                  <span class="cb-item-meta">{{ img.width }}×{{ img.height }} · {{ formatSize(img.bytes) }}</span>
                </div>
              </div>
            </div>
            <div v-if="nextCursor" class="cb-load-more">
              <button class="btn btn-ghost" :disabled="loadingMore" @click="loadMoreImages">
                {{ loadingMore ? 'Caricamento...' : 'Carica altre immagini' }}
              </button>
            </div>
          </template>
        </div>
      </div>

      <!-- Confirm delete modal -->
      <div v-if="confirmDelete.show" class="cb-confirm-backdrop" @click.self="cancelDelete">
        <div class="cb-confirm-modal">
          <h3>Elimina cartella</h3>
          <p>Vuoi eliminare la cartella <strong>{{ confirmDelete.folder?.name }}</strong>?</p>
          <p class="cb-confirm-note">La cartella deve essere vuota per poter essere eliminata.</p>
          <div class="cb-confirm-actions">
            <button class="btn btn-sm btn-ghost" @click="cancelDelete">Annulla</button>
            <button class="btn btn-sm btn-danger" :disabled="deletingFolder" @click="doDeleteFolder">
              {{ deletingFolder ? 'Eliminazione...' : 'Elimina' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.cb-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:flex;align-items:center;justify-content:center;padding:24px}
.cb-modal{background:var(--bg-card);border-radius:var(--radius-lg);box-shadow:0 24px 48px rgba(0,0,0,.25);width:100%;max-width:900px;max-height:85vh;display:flex;flex-direction:column;overflow:hidden}

.cb-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--border)}
.cb-header h2{font-size:18px;font-weight:700;margin:0}

.cb-breadcrumb{display:flex;align-items:center;justify-content:space-between;padding:12px 24px;border-bottom:1px solid var(--border);font-size:14px;flex-shrink:0;gap:12px}
.cb-breadcrumb-actions{display:flex;gap:6px;flex-shrink:0}
.cb-upload-here-btn{cursor:pointer}
.cb-breadcrumb-path{display:flex;align-items:center;overflow-x:auto;flex:1;min-width:0}
.cb-crumb{cursor:pointer;color:var(--primary);font-weight:500;white-space:nowrap;transition:color var(--transition)}
.cb-crumb:hover{text-decoration:underline}
.cb-crumb--active{color:var(--text);cursor:default;font-weight:700}
.cb-crumb--active:hover{text-decoration:none}
.cb-crumb-sep{margin:0 6px;color:var(--text-light);font-weight:400}

.cb-new-folder{display:flex;align-items:center;gap:8px;padding:10px 24px;border-bottom:1px solid var(--border);background:var(--bg-main);flex-shrink:0}
.cb-new-folder-prefix{font-size:13px;color:var(--text-light);white-space:nowrap;font-family:monospace}
.cb-new-folder-input{flex:1;padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;font-family:monospace;outline:none;min-width:120px}
.cb-new-folder-input:focus{border-color:var(--primary)}

.cb-body{flex:1;overflow-y:auto;padding:16px 24px}
.cb-section-loading{text-align:center;padding:24px 0;color:var(--text-light);font-size:13px}
.cb-empty{text-align:center;padding:48px 0;color:var(--text-light);font-size:14px}
.cb-divider{border-top:1px solid var(--border);margin:16px 0}

.cb-folders-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:8px}
.cb-folder-card{display:flex;align-items:center;gap:10px;padding:12px 14px;background:var(--bg-main);border:1px solid var(--border);border-radius:var(--radius);cursor:pointer;transition:all var(--transition)}
.cb-folder-card:hover{border-color:var(--primary);background:rgba(99,102,241,.04)}
.cb-folder-icon{font-size:20px;flex-shrink:0}
.cb-folder-name{font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

.cb-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.cb-item{border-radius:var(--radius);overflow:hidden;cursor:pointer;border:2px solid transparent;transition:all var(--transition);background:var(--bg-main)}
.cb-item:hover{border-color:var(--primary);transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.1)}
.cb-item img{width:100%;aspect-ratio:1;object-fit:cover;display:block}
.cb-item-info{padding:8px 10px}
.cb-item-name{display:block;font-size:11px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cb-item-meta{display:block;font-size:10px;color:var(--text-light);margin-top:2px}

.cb-load-more{text-align:center;padding:16px 0}

.cb-folder-card{position:relative}
.cb-folder-delete{position:absolute;top:6px;right:6px;background:none;border:none;cursor:pointer;font-size:13px;opacity:0;transition:opacity var(--transition);padding:2px 4px;border-radius:4px}
.cb-folder-card:hover .cb-folder-delete{opacity:.6}
.cb-folder-delete:hover{opacity:1!important;background:rgba(239,68,68,.1)}

.cb-confirm-backdrop{position:absolute;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;z-index:10;border-radius:var(--radius-lg)}
.cb-confirm-modal{background:var(--bg-card);border-radius:var(--radius-lg);padding:24px 28px;box-shadow:0 12px 32px rgba(0,0,0,.25);max-width:380px;width:100%}
.cb-confirm-modal h3{margin:0 0 8px;font-size:16px;font-weight:700}
.cb-confirm-modal p{margin:0 0 6px;font-size:14px;color:var(--text)}
.cb-confirm-note{font-size:12px;color:var(--text-light);margin-bottom:16px!important}
.cb-confirm-actions{display:flex;gap:8px;justify-content:flex-end}
.btn-danger{background:#ef4444;color:#fff;border:none;border-radius:var(--radius);padding:6px 16px;font-weight:600;cursor:pointer}
.btn-danger:hover{background:#dc2626}
.btn-danger:disabled{opacity:.5;cursor:not-allowed}
</style>

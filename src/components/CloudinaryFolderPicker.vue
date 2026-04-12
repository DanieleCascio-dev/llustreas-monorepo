<script setup>
/**
 * Modale per selezionare una cartella Cloudinary di destinazione prima dell'upload.
 * Mostra le sotto-cartelle del rootPath e permette di crearne di nuove.
 */
import { ref, watch } from 'vue'
import { cloudinaryBrowser } from '../services/api'
import { useToast } from '../composables/useToast'

const toast = useToast()

const props = defineProps({
  visible: Boolean,
  rootPath: { type: String, default: 'illustreas/projects' },
})
const emit = defineEmits(['close', 'select'])

const folders = ref([])
const loading = ref(false)
const showNewFolder = ref(false)
const newFolderName = ref('')
const creating = ref(false)

watch(() => props.visible, async (val) => {
  if (val) await loadFolders()
})

async function loadFolders() {
  loading.value = true
  try {
    const { data } = await cloudinaryBrowser.folders(props.rootPath)
    folders.value = data.folders || []
  } finally {
    loading.value = false
  }
}

function selectFolder(folderPath) {
  emit('select', folderPath)
}

function selectRoot() {
  emit('select', props.rootPath)
}

async function createFolder() {
  const name = newFolderName.value.trim().replace(/[^a-zA-Z0-9_-]/g, '_')
  if (!name) return
  creating.value = true
  try {
    const fullPath = props.rootPath + '/' + name
    await cloudinaryBrowser.createFolder(fullPath)
    showNewFolder.value = false
    newFolderName.value = ''
    await loadFolders()
    emit('select', fullPath)
  } catch (err) {
    toast.error('Errore: ' + (err.response?.data?.message || err.message))
  } finally {
    creating.value = false
  }
}

function cancelNewFolder() {
  showNewFolder.value = false
  newFolderName.value = ''
}

function close() {
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <div v-if="visible" class="fp-backdrop" @click.self="close">
      <div class="fp-modal">
        <div class="fp-header">
          <h3>Dove vuoi salvare l'immagine?</h3>
          <button class="btn btn-sm btn-ghost" @click="close">✕</button>
        </div>

        <div class="fp-root-info">
          Cartella base: <strong>{{ rootPath }}</strong>
        </div>

        <div class="fp-body">
          <div v-if="loading" class="fp-loading">Caricamento cartelle...</div>

          <template v-else>
            <!-- Salva nella cartella root -->
            <div class="fp-folder-card fp-folder-card--root" @click="selectRoot">
              <span class="fp-folder-icon">📁</span>
              <span class="fp-folder-name">Salva qui ({{ rootPath.split('/').pop() }})</span>
            </div>

            <!-- Sotto-cartelle esistenti -->
            <div
              v-for="f in folders"
              :key="f.path"
              class="fp-folder-card"
              @click="selectFolder(f.path)"
            >
              <span class="fp-folder-icon">📂</span>
              <span class="fp-folder-name">{{ f.name }}</span>
            </div>

            <!-- Crea nuova cartella -->
            <div v-if="!showNewFolder" class="fp-folder-card fp-folder-card--new" @click="showNewFolder = true">
              <span class="fp-folder-icon">➕</span>
              <span class="fp-folder-name">Crea nuova cartella</span>
            </div>

            <div v-if="showNewFolder" class="fp-new-folder">
              <span class="fp-new-prefix">{{ rootPath }}/</span>
              <input
                v-model="newFolderName"
                class="fp-new-input"
                placeholder="nome_cartella"
                autofocus
                @keyup.enter="createFolder"
                @keyup.escape="cancelNewFolder"
              />
              <button class="btn btn-sm btn-primary" :disabled="creating || !newFolderName.trim()" @click="createFolder">
                {{ creating ? '...' : 'Crea e seleziona' }}
              </button>
              <button class="btn btn-sm btn-ghost" @click="cancelNewFolder">Annulla</button>
            </div>
          </template>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.fp-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:flex;align-items:center;justify-content:center;padding:24px}
.fp-modal{background:var(--bg-card);border-radius:var(--radius-lg);box-shadow:0 24px 48px rgba(0,0,0,.25);width:100%;max-width:500px;display:flex;flex-direction:column;overflow:hidden}

.fp-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--border)}
.fp-header h3{font-size:16px;font-weight:700;margin:0}

.fp-root-info{padding:12px 24px;font-size:13px;color:var(--text-light);border-bottom:1px solid var(--border);background:var(--bg-main)}

.fp-body{padding:16px 24px;display:flex;flex-direction:column;gap:8px;max-height:400px;overflow-y:auto}
.fp-loading{text-align:center;padding:24px 0;color:var(--text-light);font-size:13px}

.fp-folder-card{display:flex;align-items:center;gap:12px;padding:12px 16px;background:var(--bg-main);border:1px solid var(--border);border-radius:var(--radius);cursor:pointer;transition:all .15s}
.fp-folder-card:hover{border-color:var(--primary);background:rgba(99,102,241,.04)}
.fp-folder-card--root{border-color:var(--primary);background:rgba(99,102,241,.03);font-weight:600}
.fp-folder-card--new{border-style:dashed;color:var(--primary);font-weight:500}
.fp-folder-icon{font-size:18px;flex-shrink:0}
.fp-folder-name{font-size:14px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

.fp-new-folder{display:flex;align-items:center;gap:8px;padding:12px 0;flex-wrap:wrap}
.fp-new-prefix{font-size:12px;color:var(--text-light);white-space:nowrap;font-family:monospace}
.fp-new-input{flex:1;padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;font-family:monospace;outline:none;min-width:120px}
.fp-new-input:focus{border-color:var(--primary)}
</style>

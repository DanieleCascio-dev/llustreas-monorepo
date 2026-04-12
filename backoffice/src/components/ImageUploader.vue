<script setup>
/**
 * Componente per caricare immagini su Cloudinary.
 * Se askFolder è true, mostra un picker cartella prima dell'upload.
 */
import { ref } from 'vue'
import { uploadImage } from '../services/cloudinary'
import { useToast } from '../composables/useToast'
import CloudinaryBrowser from './CloudinaryBrowser.vue'
import CloudinaryFolderPicker from './CloudinaryFolderPicker.vue'

const toast = useToast()

const props = defineProps({
  modelValue: String,
  folder: { type: String, default: 'illustreas' },
  // se true, chiede all'utente dove salvare (sotto folderRoot)
  askFolder: { type: Boolean, default: false },
  // cartella base per il picker (es. 'illustreas/projects')
  folderRoot: { type: String, default: 'illustreas/projects' },
})
const emit = defineEmits(['update:modelValue'])

const uploading = ref(false)
const dragover = ref(false)
const showBrowser = ref(false)
const showFolderPicker = ref(false)
const pendingFile = ref(null)

async function handleFile(file) {
  if (!file || !file.type.startsWith('image/')) return

  if (props.askFolder) {
    pendingFile.value = file
    showFolderPicker.value = true
    return
  }

  await doUpload(file, props.folder)
}

async function onFolderSelected(folderPath) {
  showFolderPicker.value = false
  if (pendingFile.value) {
    await doUpload(pendingFile.value, folderPath)
    pendingFile.value = null
  }
}

function onFolderPickerClose() {
  showFolderPicker.value = false
  pendingFile.value = null
}

async function doUpload(file, targetFolder) {
  uploading.value = true
  try {
    const url = await uploadImage(file, targetFolder)
    emit('update:modelValue', url)
  } catch (e) {
    toast.error('Errore upload: ' + (e.response?.data?.message || e.message))
  } finally {
    uploading.value = false
  }
}

function onDrop(e) {
  dragover.value = false
  const file = e.dataTransfer?.files[0]
  if (file) handleFile(file)
}

function onFileInput(e) {
  handleFile(e.target.files[0])
}

function onCloudinarySelect({ url }) {
  emit('update:modelValue', url)
  showBrowser.value = false
}

function remove() {
  emit('update:modelValue', '')
}
</script>

<template>
  <div
    class="uploader"
    :class="{ 'uploader--dragover': dragover, 'uploader--has-image': modelValue }"
    @dragover.prevent="dragover = true"
    @dragleave="dragover = false"
    @drop.prevent="onDrop"
  >
    <template v-if="modelValue && !uploading">
      <img :src="modelValue" class="uploader-preview" />
      <div class="uploader-actions">
        <label class="btn btn-sm btn-ghost">
          Cambia
          <input type="file" accept="image/*" hidden @change="onFileInput" />
        </label>
        <button class="btn btn-sm btn-ghost" @click="showBrowser = true">☁ Cloudinary</button>
        <button class="btn btn-sm btn-danger" @click="remove">Rimuovi</button>
      </div>
    </template>
    <template v-else>
      <div v-if="uploading" class="uploader-loading">Caricamento...</div>
      <template v-else>
        <div class="uploader-empty">
          <div class="uploader-icon">+</div>
          <div class="uploader-text">Trascina un'immagine o clicca per caricare</div>
          <input type="file" accept="image/*" class="uploader-input" @change="onFileInput" />
        </div>
        <button class="btn btn-sm btn-ghost uploader-cloud-btn" @click.stop="showBrowser = true">☁ Scegli da Cloudinary</button>
      </template>
    </template>
  </div>

  <CloudinaryBrowser
    :visible="showBrowser"
    @close="showBrowser = false"
    @select="onCloudinarySelect"
  />

  <CloudinaryFolderPicker
    :visible="showFolderPicker"
    :root-path="folderRoot"
    @close="onFolderPickerClose"
    @select="onFolderSelected"
  />
</template>

<style scoped>
.uploader{position:relative;border:2px dashed var(--border);border-radius:var(--radius-lg);padding:24px;text-align:center;transition:all var(--transition);min-height:120px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px}
.uploader--dragover{border-color:var(--primary);background:var(--primary-light)}
.uploader--has-image{border-style:solid;padding:8px}
.uploader-preview{max-height:200px;border-radius:var(--radius);object-fit:contain;margin:0 auto}
.uploader-actions{display:flex;gap:8px;margin-top:8px}
.uploader-empty{position:relative;display:flex;flex-direction:column;align-items:center;gap:8px}
.uploader-icon{font-size:32px;color:var(--text-light)}
.uploader-text{font-size:13px;color:var(--text-light)}
.uploader-input{position:absolute;inset:0;opacity:0;cursor:pointer}
.uploader-loading{color:var(--primary);font-weight:600}
.uploader-cloud-btn{margin-top:4px;position:relative;z-index:1}
</style>

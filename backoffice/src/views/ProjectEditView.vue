<script setup>
import { ref, onMounted, computed, watch, nextTick, onBeforeUnmount } from 'vue'
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'
import { projects, projectImages } from '../services/api'
import { useToast } from '../composables/useToast'
import { useKeyboardSave } from '../composables/useKeyboardSave'
import ImageUploader from '../components/ImageUploader.vue'
import RichEditor from '../components/RichEditor.vue'
import Sortable from 'sortablejs'

const frontofficeUrl = import.meta.env.VITE_FRONTOFFICE_URL || 'http://localhost:5173'

const toast = useToast()
useKeyboardSave(() => save())
const route = useRoute()
const router = useRouter()
const isEdit = computed(() => !!route.params.id)
const saving = ref(false)
const loading = ref(false)

const form = ref({
  title: '',
  slug: '',
  hero_url: '',
  gif_url: '',
  layout: 'column',
  description: '',
  info: '',
  is_published: false,
})

const images = ref([])

// Snapshot dello stato salvato per rilevare modifiche non salvate
let savedSnapshot = ''
const dirty = computed(() => {
  if (loading.value) return false
  return JSON.stringify(form.value) !== savedSnapshot
})

function takeSnapshot() {
  savedSnapshot = JSON.stringify(form.value)
}

async function load() {
  if (!isEdit.value) return
  loading.value = true
    try {
      const { data } = await projects.get(route.params.id)
      form.value = {
        title: data.title,
        slug: data.slug,
        hero_url: data.hero_url,
        gif_url: data.gif_url || '',
        layout: data.layout,
        description: data.description || '',
        info: data.info || '',
        is_published: data.is_published,
      }
      images.value = (data.images || []).map(normalizeImage)
      takeSnapshot()
    } catch {
      toast.error('Errore caricamento progetto')
    } finally {
      loading.value = false
    }
}

onMounted(async () => {
  if (isEdit.value) {
    await load()
  } else {
    takeSnapshot()
  }
})

// --- Guard: avvisa se ci sono modifiche non salvate ---

onBeforeRouteLeave(() => {
  if (dirty.value) {
    return window.confirm('Hai modifiche non salvate. Vuoi davvero uscire?')
  }
})

// --- Auto-slug dal titolo ---

const slugManuallyEdited = ref(false)

function toSlug(str) {
  return str
    .toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '')
}

watch(() => form.value.title, (newTitle) => {
  if (!slugManuallyEdited.value && !isEdit.value) {
    form.value.slug = toSlug(newTitle)
  }
})

function onSlugInput() {
  slugManuallyEdited.value = true
}

// --- Anteprima frontoffice ---

function openPreview() {
  const slug = form.value.slug
  if (!slug) {
    toast.error('Inserisci uno slug prima di visualizzare l\'anteprima')
    return
  }
  window.open(`${frontofficeUrl}/project/${slug}`, '_blank')
}

function normalizeImage(img) {
  if (img.type === 'text_image_block') {
    const tb = img.text_block || {}
    return {
      id: img.id,
      type: 'text_image_block',
      src: img.src || '',
      order: img.order,
      text_block: {
        subtitle: tb.subtitle || '',
        title: tb.title || '',
        text_color: tb.text_color || '#000000',
        background_color: tb.background_color || '#ffffff',
        layout: tb.layout || 'text-left',
        image_position: tb.image_position || '',
        paragraphs: (tb.paragraphs || []).map(p => ({
          title: p.title || '',
          text: p.text || '',
          text_html: p.text_html || '',
        })),
      },
    }
  }
  return { id: img.id, type: 'image', src: img.src, order: img.order }
}

async function save() {
  saving.value = true
  try {
    if (isEdit.value) {
      await projects.update(route.params.id, form.value)
      takeSnapshot()
      toast.success('Progetto salvato')
    } else {
      const { data } = await projects.create(form.value)
      takeSnapshot()
      toast.success('Progetto creato')
      router.replace(`/projects/${data.id}`)
    }
  } catch (e) {
    toast.error('Errore: ' + (e.response?.data?.message || e.message))
  } finally {
    saving.value = false
  }
}

async function addImage() {
  if (!isEdit.value) return
  try {
    const { data } = await projectImages.create(route.params.id, { type: 'image', src: '' })
    images.value.push(normalizeImage(data))
    toast.success('Immagine aggiunta')
  } catch (e) {
    toast.error('Errore: ' + (e.response?.data?.message || e.message))
  }
}

async function addTextBlock() {
  if (!isEdit.value) return
  const payload = {
    type: 'text_image_block',
    src: '',
    text_block: {
      title: 'Nuovo blocco',
      subtitle: '',
      text_color: '#ffffff',
      background_color: '#5856d6',
      layout: 'text-left',
      paragraphs: [{ title: '', text: 'Testo del paragrafo', text_html: '' }],
    },
  }
  try {
    const { data } = await projectImages.create(route.params.id, payload)
    images.value.push(normalizeImage(data))
    toast.success('Blocco testo aggiunto')
  } catch (e) {
    toast.error('Errore: ' + (e.response?.data?.message || e.message))
  }
}

async function updateImage(img, index) {
  if (!img.id) return
  const payload = { src: img.src }
  if (img.type === 'text_image_block') {
    payload.text_block = img.text_block
  }
  try {
    await projectImages.update(route.params.id, img.id, payload)
    toast.success('Contenuto salvato')
  } catch (e) {
    toast.error('Errore salvataggio: ' + (e.response?.data?.message || e.message))
  }
}

// --- Modal conferma eliminazione contenuto ---

const confirmModal = ref({ show: false, message: '', onConfirm: null })

function requestRemoveImage(img, index) {
  const label = img.type === 'text_image_block' ? 'questo blocco testo + immagine' : 'questa immagine'
  confirmModal.value = {
    show: true,
    message: `Sei sicuro di voler eliminare ${label}?`,
    onConfirm: () => doRemoveImage(img, index),
  }
}

function closeConfirm() {
  confirmModal.value = { show: false, message: '', onConfirm: null }
}

async function doConfirm() {
  const fn = confirmModal.value.onConfirm
  closeConfirm()
  if (fn) await fn()
}

async function doRemoveImage(img, index) {
  try {
    if (img.id) await projectImages.delete(route.params.id, img.id)
    images.value.splice(index, 1)
    toast.success('Elemento rimosso')
  } catch (e) {
    toast.error('Errore eliminazione: ' + (e.response?.data?.message || e.message))
  }
}

// --- Drag (swap via SortableJS Swap plugin) ---
const imagesListRef = ref(null)
let sortableInstance = null

function initSortable() {
  if (sortableInstance) { sortableInstance.destroy(); sortableInstance = null }
  if (!imagesListRef.value) return
  sortableInstance = Sortable.create(imagesListRef.value, {
    swap: true,
    swapClass: 'swap-highlight',
    animation: 150,
    ghostClass: 'sortable-ghost',
    handle: '.drag-handle',
    async onEnd(evt) {
      const { oldIndex, newIndex } = evt
      if (oldIndex === newIndex) return
      const temp = images.value[oldIndex]
      images.value[oldIndex] = images.value[newIndex]
      images.value[newIndex] = temp
      const order = images.value.map((img, i) => ({ id: img.id, order: i }))
      try {
        await projectImages.reorder(route.params.id, order)
        toast.success('Ordine aggiornato')
      } catch (e) {
        toast.error('Errore riordino: ' + (e.response?.data?.message || e.message))
        await load()
      }
    },
  })
}

watch(loading, (val) => { if (!val && isEdit.value) nextTick(initSortable) })
onBeforeUnmount(() => { if (sortableInstance) sortableInstance.destroy() })

function addParagraph(textBlock) {
  textBlock.paragraphs.push({ title: '', text: '', text_html: '' })
}

function removeParagraph(textBlock, pi) {
  textBlock.paragraphs.splice(pi, 1)
}
</script>

<template>
  <div>
    <div class="page-header">
      <h1>{{ isEdit ? 'Modifica progetto' : 'Nuovo progetto' }}</h1>
      <div class="row">
        <router-link to="/projects" class="btn btn-ghost">&larr; Indietro</router-link>
        <button v-if="isEdit" class="btn btn-ghost" @click="openPreview" title="Apri anteprima frontoffice">&#9654; Anteprima</button>
        <button class="btn btn-primary" :disabled="saving" @click="save">
          {{ saving ? 'Salvataggio...' : 'Salva progetto' }}
        </button>
        <span v-if="dirty" class="unsaved-badge">Modifiche non salvate</span>
      </div>
    </div>

    <div v-if="loading" class="card" style="text-align:center;padding:48px">Caricamento...</div>

    <template v-else>
      <!-- Project info -->
      <div class="card mb-16">
        <h2 class="section-title">Informazioni progetto</h2>
        <div class="grid-2 mt-16">
          <div>
            <label class="label">Titolo</label>
            <input v-model="form.title" class="input" placeholder="Nome progetto" />
          </div>
          <div>
            <label class="label">Slug (URL)</label>
            <input v-model="form.slug" class="input" placeholder="nome-progetto" @input="onSlugInput" />
          </div>
          <div>
            <label class="label">Info (tags)</label>
            <input v-model="form.info" class="input" placeholder="Branding - Graphic design" />
          </div>
          <div>
            <label class="label">Layout</label>
            <select v-model="form.layout" class="input">
              <option value="column">Colonna</option>
              <option value="grid">Griglia</option>
            </select>
          </div>
        </div>
        <div class="mt-16">
          <label class="label">Descrizione</label>
          <textarea v-model="form.description" class="input" rows="3" placeholder="Descrizione del progetto..."></textarea>
        </div>
        <div class="grid-2 mt-16">
          <div>
            <label class="label">Copertina (Hero)</label>
            <ImageUploader v-model="form.hero_url" folder="illustreas/projects" ask-folder folder-root="illustreas/projects" />
          </div>
          <div>
            <label class="label">GIF / Anteprima animata</label>
            <ImageUploader v-model="form.gif_url" folder="illustreas/projects" ask-folder folder-root="illustreas/projects" />
          </div>
        </div>
        <div class="mt-16 row">
          <label class="toggle-row">
            <input type="checkbox" v-model="form.is_published" />
            <span>Pubblicato</span>
          </label>
        </div>
      </div>

      <!-- Images & text blocks (only in edit mode) -->
      <div v-if="isEdit" class="card">
        <div class="row mb-16">
          <h2 class="section-title">Contenuti progetto</h2>
          <div class="ml-auto row">
            <button class="btn btn-ghost btn-sm" @click="addImage">+ Immagine</button>
            <button class="btn btn-primary btn-sm" @click="addTextBlock">+ Blocco testo</button>
          </div>
        </div>

        <div ref="imagesListRef" class="stack">
          <div v-for="(img, index) in images" :key="img.id" class="content-block" :class="`content-block--${img.type}`">
            <div class="content-block-header">
              <span class="drag-handle" title="Trascina">⠿</span>
              <span class="content-type-badge">{{ img.type === 'text_image_block' ? 'Testo + Immagine' : 'Immagine' }}</span>
              <div class="ml-auto row">
                <button class="btn btn-sm btn-danger" @click="requestRemoveImage(img, index)">&#10005;</button>
              </div>
            </div>

            <!-- Immagine semplice -->
            <div v-if="img.type === 'image'" class="content-block-body">
              <ImageUploader v-model="img.src" folder="illustreas/projects" ask-folder folder-root="illustreas/projects" />
              <div class="content-block-footer">
                <button v-if="img.id" class="btn btn-sm btn-primary" @click="updateImage(img, index)">Salva</button>
              </div>
            </div>

            <!-- Text + image block -->
            <div v-else class="content-block-body">
              <div class="grid-2">
                <div class="stack">
                  <div>
                    <label class="label">Sottotitolo</label>
                    <input v-model="img.text_block.subtitle" class="input" />
                  </div>
                  <div>
                    <label class="label">Titolo blocco</label>
                    <input v-model="img.text_block.title" class="input" />
                  </div>
                  <div class="grid-2">
                    <div>
                      <label class="label">Colore testo</label>
                      <div class="color-row">
                        <input type="color" v-model="img.text_block.text_color" />
                        <input v-model="img.text_block.text_color" class="input" style="flex:1" />
                      </div>
                    </div>
                    <div>
                      <label class="label">Colore sfondo</label>
                      <div class="color-row">
                        <input type="color" v-model="img.text_block.background_color" />
                        <input v-model="img.text_block.background_color" class="input" style="flex:1" />
                      </div>
                    </div>
                  </div>
                  <div class="grid-2">
                    <div>
                      <label class="label">Layout</label>
                      <select v-model="img.text_block.layout" class="input">
                        <option value="text-left">Testo a sinistra</option>
                        <option value="text-right">Testo a destra</option>
                      </select>
                    </div>
                    <div>
                      <label class="label">Posizione immagine</label>
                      <select v-model="img.text_block.image_position" class="input">
                        <option value="">Default</option>
                        <option value="bottom-right">Basso destra</option>
                      </select>
                    </div>
                  </div>
                </div>
                <div>
                  <label class="label">Immagine</label>
                  <ImageUploader v-model="img.src" folder="illustreas/projects" ask-folder folder-root="illustreas/projects" />
                </div>
              </div>

              <!-- Paragraphs -->
              <div class="mt-16">
                <div class="row mb-8">
                  <label class="label" style="margin:0">Paragrafi</label>
                  <button class="btn btn-sm btn-ghost ml-auto" @click="addParagraph(img.text_block)">+ Paragrafo</button>
                </div>
                <div v-for="(para, pi) in img.text_block.paragraphs" :key="pi" class="paragraph-block">
                  <div class="paragraph-header">
                    <input v-model="para.title" class="input" placeholder="Titolo paragrafo (opzionale)" style="max-width:300px" />
                    <button class="btn btn-sm btn-danger ml-auto" @click="removeParagraph(img.text_block, pi)">✕</button>
                  </div>
                  <div class="grid-2 mt-8">
                    <div>
                      <label class="label">Testo semplice</label>
                      <textarea v-model="para.text" class="input" rows="3"></textarea>
                    </div>
                    <div>
                      <label class="label">Testo HTML (rich)</label>
                      <RichEditor v-model="para.text_html" />
                    </div>
                  </div>
                </div>
              </div>
              <div class="content-block-footer">
                <button v-if="img.id" class="btn btn-sm btn-primary" @click="updateImage(img, index)">Salva</button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="images.length === 0" class="empty-state">
          Nessun contenuto. Aggiungi immagini o blocchi testo.
        </div>

        <div class="add-buttons-bottom">
          <button class="btn btn-ghost" @click="addImage">+ Immagine</button>
          <button class="btn btn-primary" @click="addTextBlock">+ Blocco testo</button>
        </div>
      </div>

      <div v-if="!isEdit" class="card mt-16" style="text-align:center;padding:32px;color:var(--text-light)">
        Salva il progetto per aggiungere immagini e contenuti.
      </div>
    </template>

    <!-- Modal conferma eliminazione -->
    <Teleport to="body">
      <div v-if="confirmModal.show" class="modal-backdrop" @click.self="closeConfirm">
        <div class="confirm-modal">
          <div class="confirm-body">
            <p>{{ confirmModal.message }}</p>
          </div>
          <div class="confirm-footer">
            <button class="btn btn-ghost" @click="closeConfirm">No</button>
            <button class="btn btn-danger" @click="doConfirm">S&igrave;</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.section-title{font-size:16px;font-weight:700}
.toggle-row{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;font-weight:500}
.toggle-row input{width:18px;height:18px;accent-color:var(--primary)}

.content-block{border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;background:#fff}
.content-block--text_image_block{border-color:var(--primary);border-width:2px}
.content-block-header{display:flex;align-items:center;gap:8px;padding:10px 16px;background:#fafafa;border-bottom:1px solid var(--border)}
.content-type-badge{font-size:12px;font-weight:600;color:var(--primary);text-transform:uppercase;letter-spacing:.5px}
.content-block-body{padding:16px}

.color-row{display:flex;align-items:center;gap:8px}
.color-row input[type="color"]{width:36px;height:36px;border:1px solid var(--border);border-radius:var(--radius);padding:2px;cursor:pointer}

.paragraph-block{border:1px solid var(--border);border-radius:var(--radius);padding:12px;margin-bottom:12px;background:#fafafa}
.paragraph-header{display:flex;align-items:center;gap:8px}

.content-block-footer{display:flex;justify-content:flex-end;padding-top:12px;margin-top:12px;border-top:1px solid var(--border)}

.add-buttons-bottom{display:flex;justify-content:center;gap:12px;padding:20px 0 4px}

.empty-state{text-align:center;padding:48px;color:var(--text-light);font-size:14px}

.sortable-ghost{opacity:.4}
.swap-highlight{outline:2px dashed var(--primary);outline-offset:-2px;background:rgba(99,102,241,.06);border-radius:var(--radius)}

.unsaved-badge{font-size:12px;font-weight:600;color:#f59e0b;background:#fef3c7;padding:4px 10px;border-radius:var(--radius);white-space:nowrap}

.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:flex;align-items:center;justify-content:center;padding:24px}
.confirm-modal{background:var(--bg-card);border-radius:var(--radius-lg);box-shadow:0 24px 48px rgba(0,0,0,.2);width:100%;max-width:400px;overflow:hidden}
.confirm-body{padding:24px;text-align:center}
.confirm-body p{margin:0;font-size:14px;color:var(--text);line-height:1.5}
.confirm-footer{display:flex;justify-content:flex-end;gap:8px;padding:16px 24px;border-top:1px solid var(--border)}
</style>

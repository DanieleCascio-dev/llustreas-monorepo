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

function mapParagraph(p) {
  const id = p.id != null ? p.id : undefined
  return {
    id,
    clientKey: id ? undefined : crypto.randomUUID(),
    title: p.title || '',
    text: p.text || '',
    text_html: p.text_html || '',
  }
}

function paragraphRowKey(p) {
  return p.id ?? p.clientKey
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
        paragraphs: (tb.paragraphs || []).map(mapParagraph),
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

async function persistOrder() {
  const order = images.value.map((im, i) => ({ id: im.id, order: i }))
  await projectImages.reorder(route.params.id, order)
}

async function addImage() {
  await addImageAt(images.value.length)
}

async function addTextBlock() {
  await addTextBlockAt(images.value.length)
}

async function addImageAt(insertIndex) {
  if (!isEdit.value) return
  try {
    const { data } = await projectImages.create(route.params.id, { type: 'image', src: '' })
    const normalized = normalizeImage(data)
    const idx = Math.max(0, Math.min(insertIndex, images.value.length))
    images.value.splice(idx, 0, normalized)
    await persistOrder()
    openActionsMenuIndex.value = null
    toast.success('Immagine aggiunta')
  } catch (e) {
    toast.error('Errore: ' + (e.response?.data?.message || e.message))
    await load()
  }
}

async function addTextBlockAt(insertIndex) {
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
    const normalized = normalizeImage(data)
    const idx = Math.max(0, Math.min(insertIndex, images.value.length))
    images.value.splice(idx, 0, normalized)
    await persistOrder()
    openActionsMenuIndex.value = null
    toast.success('Blocco testo aggiunto')
  } catch (e) {
    toast.error('Errore: ' + (e.response?.data?.message || e.message))
    await load()
  }
}

function textBlockPayload(tb) {
  return {
    subtitle: tb.subtitle || '',
    title: tb.title || '',
    text_color: tb.text_color || '#000000',
    background_color: tb.background_color || '#ffffff',
    layout: tb.layout || 'text-left',
    image_position: tb.image_position || '',
    paragraphs: (tb.paragraphs || []).map((p) => ({
      title: p.title || '',
      text: p.text || '',
      text_html: p.text_html || '',
    })),
  }
}

async function updateImage(img, index) {
  if (!img.id) return
  const payload = { src: img.src }
  if (img.type === 'text_image_block') {
    payload.text_block = textBlockPayload(img.text_block)
  }
  try {
    const { data } = await projectImages.update(route.params.id, img.id, payload)
    const refreshed = normalizeImage(data)
    const i = images.value.findIndex((x) => x.id === img.id)
    if (i !== -1) images.value[i] = refreshed
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

// --- Riordino paragrafi (Sortable per blocco testo) ---
const paragraphSortables = new Map()

function destroyParagraphSortable(imageId) {
  const inst = paragraphSortables.get(imageId)
  if (inst) {
    inst.destroy()
    paragraphSortables.delete(imageId)
  }
}

function initParagraphSortable(imageId, el) {
  destroyParagraphSortable(imageId)
  const inst = Sortable.create(el, {
    animation: 150,
    ghostClass: 'sortable-ghost',
    handle: '.paragraph-drag-handle',
    onEnd(evt) {
      const { oldIndex, newIndex } = evt
      if (oldIndex === newIndex || oldIndex == null || newIndex == null) return
      const img = images.value.find((i) => i.id === imageId)
      if (!img?.text_block) return
      const arr = img.text_block.paragraphs
      const moved = arr.splice(oldIndex, 1)[0]
      arr.splice(newIndex, 0, moved)
    },
  })
  paragraphSortables.set(imageId, inst)
}

function onParagraphListEl(imageId, el) {
  if (!el) {
    destroyParagraphSortable(imageId)
    return
  }
  nextTick(() => initParagraphSortable(imageId, el))
}

// --- Modale riordino contenuti ---
const reorderModalOpen = ref(false)
const reorderModalItems = ref([])
const reorderModalListRef = ref(null)
let reorderModalSortable = null

function destroyReorderModalSortable() {
  if (reorderModalSortable) {
    reorderModalSortable.destroy()
    reorderModalSortable = null
  }
}

function openReorderModal() {
  if (!images.value.length) {
    toast.error('Nessun contenuto da riordinare')
    return
  }
  reorderModalItems.value = [...images.value]
  reorderModalOpen.value = true
  openActionsMenuIndex.value = null
  nextTick(() => {
    destroyReorderModalSortable()
    if (!reorderModalListRef.value) return
    reorderModalSortable = Sortable.create(reorderModalListRef.value, {
      animation: 150,
      ghostClass: 'sortable-ghost',
      handle: '.reorder-modal-drag-handle',
      onEnd(evt) {
        const { oldIndex, newIndex } = evt
        if (oldIndex === newIndex || oldIndex == null || newIndex == null) return
        const arr = reorderModalItems.value
        const moved = arr.splice(oldIndex, 1)[0]
        arr.splice(newIndex, 0, moved)
      },
    })
  })
}

function closeReorderModal() {
  destroyReorderModalSortable()
  reorderModalOpen.value = false
  reorderModalItems.value = []
}

watch(reorderModalOpen, (open) => {
  if (!open) destroyReorderModalSortable()
})

async function applyReorderModal() {
  try {
    images.value = [...reorderModalItems.value]
    await persistOrder()
    toast.success('Ordine aggiornato')
    closeReorderModal()
  } catch (e) {
    toast.error('Errore riordino: ' + (e.response?.data?.message || e.message))
    await load()
    closeReorderModal()
  }
}

function reorderPreviewLabel(item) {
  if (item.type === 'text_image_block') {
    return item.text_block?.title || 'Blocco testo'
  }
  return 'Immagine'
}

const openActionsMenuIndex = ref(null)

function toggleActionsMenu(index) {
  openActionsMenuIndex.value = openActionsMenuIndex.value === index ? null : index
}

watch(loading, (val) => {
  if (val) {
    paragraphSortables.forEach((inst) => inst.destroy())
    paragraphSortables.clear()
    openActionsMenuIndex.value = null
  }
})

onBeforeUnmount(() => {
  paragraphSortables.forEach((inst) => inst.destroy())
  paragraphSortables.clear()
  destroyReorderModalSortable()
})

function addParagraph(textBlock) {
  textBlock.paragraphs.push({
    clientKey: crypto.randomUUID(),
    title: '',
    text: '',
    text_html: '',
  })
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
        <div class="row mb-16 flex-wrap">
          <h2 class="section-title">Contenuti progetto</h2>
          <div class="ml-auto row flex-wrap gap-sm">
            <button type="button" class="btn btn-ghost btn-sm" :disabled="!images.length" @click="openReorderModal">Riordina</button>
            <button type="button" class="btn btn-ghost btn-sm" @click="addImage">+ Immagine</button>
            <button type="button" class="btn btn-primary btn-sm" @click="addTextBlock">+ Blocco testo</button>
          </div>
        </div>

        <div class="stack">
          <div v-for="(img, index) in images" :key="img.id" class="content-block" :class="`content-block--${img.type}`">
            <div class="content-block-header">
              <span class="content-type-badge">{{ img.type === 'text_image_block' ? 'Testo + Immagine' : 'Immagine' }}</span>
              <div class="ml-auto row gap-sm">
                <button type="button" class="btn btn-sm btn-ghost" @click="toggleActionsMenu(index)">
                  {{ openActionsMenuIndex === index ? 'Chiudi' : 'Azioni' }}
                </button>
                <button type="button" class="btn btn-sm btn-danger" @click="requestRemoveImage(img, index)">&#10005;</button>
              </div>
            </div>

            <div v-if="openActionsMenuIndex === index" class="content-actions-panel">
              <button type="button" class="btn btn-sm btn-ghost content-actions-panel__btn" @click="openReorderModal">Riordina</button>
              <button type="button" class="btn btn-sm btn-ghost content-actions-panel__btn" @click="addImageAt(index)">+ Immagine prima</button>
              <button type="button" class="btn btn-sm btn-ghost content-actions-panel__btn" @click="addImageAt(index + 1)">+ Immagine dopo</button>
              <button type="button" class="btn btn-sm btn-ghost content-actions-panel__btn" @click="addTextBlockAt(index)">+ Blocco testo prima</button>
              <button type="button" class="btn btn-sm btn-ghost content-actions-panel__btn" @click="addTextBlockAt(index + 1)">+ Blocco testo dopo</button>
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
                  <button type="button" class="btn btn-sm btn-ghost ml-auto" @click="addParagraph(img.text_block)">+ Paragrafo</button>
                </div>
                <div :ref="(el) => onParagraphListEl(img.id, el)" class="paragraphs-dnd-list">
                  <div v-for="(para, pi) in img.text_block.paragraphs" :key="paragraphRowKey(para)" class="paragraph-block">
                    <div class="paragraph-header">
                      <span class="paragraph-drag-handle" title="Trascina per riordinare">⠿</span>
                      <input v-model="para.title" class="input" placeholder="Titolo paragrafo (opzionale)" style="max-width:280px" />
                      <button type="button" class="btn btn-sm btn-danger ml-auto" @click="removeParagraph(img.text_block, pi)">✕</button>
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
              </div>
              <div class="content-block-footer">
                <button v-if="img.id" type="button" class="btn btn-sm btn-primary" @click="updateImage(img, index)">Salva</button>
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
            <button type="button" class="btn btn-ghost" @click="closeConfirm">No</button>
            <button type="button" class="btn btn-danger" @click="doConfirm">S&igrave;</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modale riordino contenuti -->
    <Teleport to="body">
      <div v-if="reorderModalOpen" class="modal-backdrop modal-backdrop--reorder" @click.self="closeReorderModal">
        <div class="reorder-modal" role="dialog" aria-labelledby="reorder-modal-title" @click.stop>
          <div class="reorder-modal__head">
            <h3 id="reorder-modal-title" class="reorder-modal__title">Riordina contenuti</h3>
            <p class="reorder-modal__hint">Trascina le righe usando l&apos;icona a sinistra. Conferma per salvare l&apos;ordine sul server.</p>
          </div>
          <div ref="reorderModalListRef" class="reorder-modal-list">
            <div
              v-for="item in reorderModalItems"
              :key="item.id"
              class="reorder-modal-row"
            >
              <span class="reorder-modal-drag-handle" title="Trascina">⠿</span>
              <div class="reorder-modal-preview">
                <img v-if="item.src" :src="item.src" alt="" class="reorder-modal-preview__img" />
                <span v-else class="reorder-modal-placeholder">Anteprima</span>
              </div>
              <div class="reorder-modal-meta">
                <span class="reorder-modal-badge">{{ item.type === 'text_image_block' ? 'Testo + immagine' : 'Immagine' }}</span>
                <span class="reorder-modal-label">{{ reorderPreviewLabel(item) }}</span>
              </div>
            </div>
          </div>
          <div class="reorder-modal__footer">
            <button type="button" class="btn btn-ghost" @click="closeReorderModal">Annulla</button>
            <button type="button" class="btn btn-primary" @click="applyReorderModal">Applica ordine</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.flex-wrap{flex-wrap:wrap}
.gap-sm{gap:8px}

.section-title{font-size:16px;font-weight:700}
.toggle-row{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;font-weight:500}
.toggle-row input{width:18px;height:18px;accent-color:var(--primary)}

.content-block{border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;background:#fff}
.content-block--text_image_block{border-color:var(--primary);border-width:2px}
.content-block-header{display:flex;align-items:center;gap:8px;padding:10px 16px;background:#fafafa;border-bottom:1px solid var(--border)}
.content-actions-panel{display:flex;flex-wrap:wrap;gap:8px;padding:10px 16px;background:hsl(0 0% 97%);border-bottom:1px solid var(--border)}
.content-actions-panel__btn{white-space:normal;text-align:left}
.content-type-badge{font-size:12px;font-weight:600;color:var(--primary);text-transform:uppercase;letter-spacing:.5px}
.content-block-body{padding:16px}

.color-row{display:flex;align-items:center;gap:8px}
.color-row input[type="color"]{width:36px;height:36px;border:1px solid var(--border);border-radius:var(--radius);padding:2px;cursor:pointer}

.paragraphs-dnd-list{min-height:8px}
.paragraph-block{border:1px solid var(--border);border-radius:var(--radius);padding:12px;margin-bottom:12px;background:#fafafa}
.paragraph-header{display:flex;align-items:center;gap:8px}
.paragraph-drag-handle{cursor:grab;user-select:none;color:var(--text-light);font-size:14px;line-height:1;padding:4px}
.paragraph-drag-handle:active{cursor:grabbing}

.content-block-footer{display:flex;justify-content:flex-end;padding-top:12px;margin-top:12px;border-top:1px solid var(--border)}

.add-buttons-bottom{display:flex;justify-content:center;gap:12px;padding:20px 0 4px}

.empty-state{text-align:center;padding:48px;color:var(--text-light);font-size:14px}

.sortable-ghost{opacity:.4}

.modal-backdrop--reorder{z-index:1001}
.reorder-modal{background:var(--bg-card);border-radius:var(--radius-lg);box-shadow:0 24px 48px rgba(0,0,0,.2);width:100%;max-width:520px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden}
.reorder-modal__head{padding:16px 20px;border-bottom:1px solid var(--border)}
.reorder-modal__title{margin:0 0 8px;font-size:18px;font-weight:700}
.reorder-modal__hint{margin:0;font-size:13px;color:var(--text-light);line-height:1.45}
.reorder-modal-list{padding:12px 16px;overflow-y:auto;flex:1;min-height:120px}
.reorder-modal-row{display:flex;align-items:center;gap:12px;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius);margin-bottom:8px;background:#fff}
.reorder-modal-row:last-child{margin-bottom:0}
.reorder-modal-drag-handle{cursor:grab;user-select:none;color:var(--text-light);font-size:16px;line-height:1;flex-shrink:0}
.reorder-modal-drag-handle:active{cursor:grabbing}
.reorder-modal-preview{width:56px;height:56px;flex-shrink:0;border-radius:var(--radius);overflow:hidden;border:1px solid var(--border);background:hsl(0 0% 96%);display:flex;align-items:center;justify-content:center}
.reorder-modal-preview__img{width:100%;height:100%;object-fit:cover;display:block}
.reorder-modal-placeholder{font-size:10px;color:var(--text-light);text-align:center;padding:4px;line-height:1.2}
.reorder-modal-meta{display:flex;flex-direction:column;gap:4px;min-width:0;flex:1}
.reorder-modal-badge{font-size:11px;font-weight:600;color:var(--primary);text-transform:uppercase;letter-spacing:.4px}
.reorder-modal-label{font-size:14px;font-weight:500;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.reorder-modal__footer{display:flex;justify-content:flex-end;gap:8px;padding:14px 20px;border-top:1px solid var(--border)}

.unsaved-badge{font-size:12px;font-weight:600;color:#f59e0b;background:#fef3c7;padding:4px 10px;border-radius:var(--radius);white-space:nowrap}

.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:flex;align-items:center;justify-content:center;padding:24px}
.confirm-modal{background:var(--bg-card);border-radius:var(--radius-lg);box-shadow:0 24px 48px rgba(0,0,0,.2);width:100%;max-width:400px;overflow:hidden}
.confirm-body{padding:24px;text-align:center}
.confirm-body p{margin:0;font-size:14px;color:var(--text);line-height:1.5}
.confirm-footer{display:flex;justify-content:flex-end;gap:8px;padding:16px 24px;border-top:1px solid var(--border)}
</style>

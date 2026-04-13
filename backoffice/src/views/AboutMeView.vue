<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { settings } from '../services/api'
import { useToast } from '../composables/useToast'
import { useConfirmModal } from '../composables/useConfirmModal'
import { useKeyboardSave } from '../composables/useKeyboardSave'
import CloudinaryBrowser from '../components/CloudinaryBrowser.vue'
import { AboutMePreview } from '@illustreas/shared-ui'

const toast = useToast()
const modal = useConfirmModal()
useKeyboardSave(() => save())

const loading = ref(true)
const saving = ref(false)

const image = ref('')
const title = ref('')
const paragraphs = ref([])
const tags = ref([])
const newTag = ref('')
const divider = ref({ thickness: 1.5, color: '#5b3c88', opacity: 25, height: 100 })
const titleColor = ref('#5b3c88')
const textColor = ref('#5b3c88')

const showCloudinary = ref(false)
const previewMode = ref('desktop')

const paragraphRefs = ref([])
const activeParagraphIdx = ref(null)

async function load() {
  loading.value = true
  try {
    const { data } = await settings.get('about_me')
    const v = data.value || {}
    image.value = v.image || ''
    title.value = v.title || ''
    paragraphs.value = Array.isArray(v.paragraphs) ? [...v.paragraphs] : []
    tags.value = Array.isArray(v.tags) ? [...v.tags] : []
    if (v.divider) divider.value = { ...divider.value, ...v.divider }
    if (v.titleColor) titleColor.value = v.titleColor
    if (v.textColor) textColor.value = v.textColor
  } catch {
    /* setting non ancora creato */
  } finally {
    loading.value = false
  }
}

onMounted(load)

function sanitizeHtml(html) {
  const tmp = document.createElement('div')
  tmp.innerHTML = html
  const walk = (node) => {
    const children = [...node.childNodes]
    for (const child of children) {
      if (child.nodeType === Node.TEXT_NODE) continue
      if (child.nodeType === Node.ELEMENT_NODE) {
        const tag = child.tagName.toLowerCase()
        if (['strong', 'b', 'em', 'i', 'br'].includes(tag)) {
          walk(child)
        } else {
          while (child.firstChild) child.parentNode.insertBefore(child.firstChild, child)
          child.remove()
        }
      } else {
        child.remove()
      }
    }
  }
  walk(tmp)
  return tmp.innerHTML
}

async function save() {
  syncAllParagraphs()
  saving.value = true
  try {
    const cleanParagraphs = paragraphs.value.map(p => sanitizeHtml(p))
    await settings.update('about_me', {
      image: image.value,
      title: title.value,
      paragraphs: cleanParagraphs,
      tags: tags.value,
      divider: divider.value,
      titleColor: titleColor.value,
      textColor: textColor.value,
    })
    toast.success('Sezione About Me salvata')
  } catch {
    toast.error('Errore nel salvataggio')
  } finally {
    saving.value = false
  }
}

function onCloudinarySelect({ url }) {
  image.value = url
  showCloudinary.value = false
}

function addParagraph() {
  paragraphs.value.push('')
  nextTick(() => {
    const el = paragraphRefs.value[paragraphs.value.length - 1]
    if (el) el.focus()
  })
}

async function removeParagraph(idx) {
  const ok = await modal.open({
    title: 'Rimuovi paragrafo',
    message: `Vuoi eliminare il paragrafo ${idx + 1}?`,
  })
  if (!ok) return
  paragraphs.value.splice(idx, 1)
  if (activeParagraphIdx.value === idx) activeParagraphIdx.value = null
}

function syncParagraph(idx) {
  const el = paragraphRefs.value[idx]
  if (el) paragraphs.value[idx] = el.innerHTML
}

function syncAllParagraphs() {
  paragraphRefs.value.forEach((el, idx) => {
    if (el) paragraphs.value[idx] = el.innerHTML
  })
}

function onParagraphFocus(idx) {
  activeParagraphIdx.value = idx
}

function onParagraphBlur(idx) {
  syncParagraph(idx)
  if (activeParagraphIdx.value === idx) activeParagraphIdx.value = null
}

function toggleBold(idx) {
  const el = paragraphRefs.value[idx]
  if (!el) return
  el.focus()
  document.execCommand('bold', false, null)
  syncParagraph(idx)
}

function onParagraphKeydown(e, idx) {
  if (e.key === 'b' && (e.ctrlKey || e.metaKey)) {
    e.preventDefault()
    document.execCommand('bold', false, null)
    syncParagraph(idx)
  }
}

// ── Paragrafi DnD ──
const draggingPIdx = ref(null)
const dropTargetPIdx = ref(null)

function onPDragStart(e, idx) {
  syncParagraph(idx)
  draggingPIdx.value = idx
  e.dataTransfer.effectAllowed = 'move'
}
function onPDragOver(e, idx) {
  e.preventDefault()
  dropTargetPIdx.value = idx
}
function onPDragLeave() { dropTargetPIdx.value = null }
function onPDrop(e, tgtIdx) {
  e.preventDefault()
  const srcIdx = draggingPIdx.value
  draggingPIdx.value = null
  dropTargetPIdx.value = null
  if (srcIdx === null || srcIdx === tgtIdx) return
  const arr = [...paragraphs.value]
  const [moved] = arr.splice(srcIdx, 1)
  arr.splice(tgtIdx, 0, moved)
  paragraphs.value = arr
}
function onPDragEnd() { draggingPIdx.value = null; dropTargetPIdx.value = null }

// ── Tag ──
function addTag() {
  const t = newTag.value.trim()
  if (!t || tags.value.includes(t)) return
  tags.value.push(t)
  newTag.value = ''
}
async function removeTag(idx) {
  const ok = await modal.open({
    title: 'Rimuovi tag',
    message: `Vuoi eliminare il tag "${tags.value[idx]}"?`,
  })
  if (!ok) return
  tags.value.splice(idx, 1)
}

const previewDivider = computed(() => ({
  thickness: divider.value.thickness,
  color: divider.value.color,
  opacity: divider.value.opacity,
  height: divider.value.height,
}))
</script>

<template>
  <div>
    <div class="page-header">
      <h1>Chi Sono</h1>
      <p class="page-subtitle">Gestisci la sezione About Me della homepage</p>
    </div>

    <div v-if="loading" class="loading-text">Caricamento…</div>

    <template v-else>
      <!-- ===== ANTEPRIMA FRONTOFFICE ===== -->
      <div class="preview-wrap">
        <div class="preview-header">
          <h2 class="section-title" style="margin-bottom:0">Anteprima frontoffice</h2>
          <div class="device-toggle">
            <button
              class="device-btn"
              :class="{ active: previewMode === 'desktop' }"
              @click="previewMode = 'desktop'"
              title="Desktop" aria-label="Vista desktop"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            </button>
            <button
              class="device-btn"
              :class="{ active: previewMode === 'mobile' }"
              @click="previewMode = 'mobile'"
              title="Mobile" aria-label="Vista mobile"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
            </button>
          </div>
        </div>
        <div class="preview-frame" :class="'frame--' + previewMode">
          <AboutMePreview
            :image="image"
            :title="title"
            :paragraphs="paragraphs"
            :tags="tags"
            :divider="previewDivider"
            :title-color="titleColor"
            :text-color="textColor"
            :force-mode="previewMode"
            section-radius="0"
          />
        </div>
      </div>

      <!-- ===== EDITOR ===== -->
      <div class="editor-section-wrap">
        <h2 class="section-title mgmt-title">Gestione contenuto</h2>

        <!-- Immagine + Titolo -->
        <div class="card editor-section">
          <div class="img-title-row">
            <div class="image-picker">
              <h2 class="section-title">Immagine</h2>
              <div
                class="image-thumb"
                :class="{ 'image-thumb--empty': !image }"
                @click="showCloudinary = true"
              >
                <img v-if="image" :src="image" alt="About me" />
                <span v-else class="image-thumb-plus">+ Scegli</span>
              </div>
              <button v-if="image" class="btn btn-sm btn-ghost" @click="showCloudinary = true">Cambia</button>
            </div>
            <div class="title-field">
              <h2 class="section-title">Titolo</h2>
              <input v-model="title" class="input input--lg" placeholder="Es. Hey!" />
            </div>
          </div>
        </div>

        <!-- Stile (colori + separatore unificati) -->
        <div class="card editor-section">
          <h2 class="section-title">Stile</h2>

          <div class="style-grid">
            <label class="control-group">
              <span class="control-label">Colore titolo</span>
              <div class="control-row">
                <input type="color" v-model="titleColor" class="color-input" />
                <span class="control-value">{{ titleColor }}</span>
              </div>
            </label>
            <label class="control-group">
              <span class="control-label">Colore testo</span>
              <div class="control-row">
                <input type="color" v-model="textColor" class="color-input" />
                <span class="control-value">{{ textColor }}</span>
              </div>
            </label>

            <label class="control-group">
              <span class="control-label">Separatore colore</span>
              <div class="control-row">
                <input type="color" v-model="divider.color" class="color-input" />
                <span class="control-value">{{ divider.color }}</span>
              </div>
            </label>
            <label class="control-group">
              <span class="control-label">Separatore spessore</span>
              <div class="control-row">
                <input type="range" v-model.number="divider.thickness" min="0.5" max="6" step="0.5" class="range-input" />
                <span class="control-value">{{ divider.thickness }}px</span>
              </div>
            </label>
            <label class="control-group">
              <span class="control-label">Separatore opacità</span>
              <div class="control-row">
                <input type="range" v-model.number="divider.opacity" min="0" max="100" step="5" class="range-input" />
                <span class="control-value">{{ divider.opacity }}%</span>
              </div>
            </label>
            <label class="control-group">
              <span class="control-label">Separatore altezza</span>
              <div class="control-row">
                <input type="range" v-model.number="divider.height" min="10" max="100" step="5" class="range-input" />
                <span class="control-value">{{ divider.height }}%</span>
              </div>
            </label>
          </div>
        </div>

        <!-- Paragrafi -->
        <div class="card editor-section">
          <div class="section-header-row">
            <h2 class="section-title">Paragrafi</h2>
            <span class="section-hint-inline">⠿ trascina · <strong>B</strong> grassetto · Ctrl+B</span>
          </div>
          <div class="paragraphs-list">
            <div
              v-for="(p, idx) in paragraphs"
              :key="idx"
              class="paragraph-card"
              :class="{
                'paragraph-card--dragging': draggingPIdx === idx,
                'paragraph-card--drop-target': dropTargetPIdx === idx && draggingPIdx !== idx,
              }"
              draggable="true"
              @dragstart="onPDragStart($event, idx)"
              @dragover="onPDragOver($event, idx)"
              @dragleave="onPDragLeave"
              @drop="onPDrop($event, idx)"
              @dragend="onPDragEnd"
            >
              <div class="paragraph-header">
                <span class="paragraph-drag" title="Trascina per riordinare" aria-label="Trascina per riordinare">⠿</span>
                <span class="paragraph-label">Paragrafo {{ idx + 1 }}</span>
                <button
                  class="paragraph-bold-btn"
                  :class="{ 'paragraph-bold-btn--active': activeParagraphIdx === idx }"
                  title="Grassetto (Ctrl+B)" aria-label="Grassetto"
                  @mousedown.prevent="toggleBold(idx)"
                ><strong>B</strong></button>
                <button class="paragraph-remove-btn" @click="removeParagraph(idx)" title="Rimuovi paragrafo" aria-label="Rimuovi paragrafo">✕</button>
              </div>
              <div
                :ref="el => paragraphRefs[idx] = el"
                class="paragraph-editable"
                contenteditable="true"
                :data-placeholder="'Scrivi il testo del paragrafo…'"
                v-html="p"
                @focus="onParagraphFocus(idx)"
                @blur="onParagraphBlur(idx)"
                @input="syncParagraph(idx)"
                @keydown="onParagraphKeydown($event, idx)"
              ></div>
            </div>
          </div>
          <button class="btn btn-sm btn-ghost add-btn" @click="addParagraph">+ Aggiungi paragrafo</button>
        </div>

        <!-- Tag -->
        <div class="card editor-section">
          <h2 class="section-title">Tag marquee</h2>
          <p class="section-hint">Scorrono sotto la foto nella homepage.</p>
          <div class="tags-list">
            <span v-for="(tag, idx) in tags" :key="idx" class="tag-chip">
              {{ tag }}
              <button class="tag-chip-remove" @click="removeTag(idx)">✕</button>
            </span>
            <span v-if="!tags.length" class="tags-empty">Nessun tag</span>
          </div>
          <div class="tag-input-row">
            <input
              v-model="newTag"
              class="input"
              placeholder="Nuovo tag…"
              @keyup.enter="addTag"
            />
            <button class="btn btn-sm btn--primary" :disabled="!newTag.trim()" @click="addTag">+</button>
          </div>
        </div>

        <!-- Salva -->
        <div class="save-row">
          <button class="btn btn--primary" :disabled="saving" @click="save">
            {{ saving ? 'Salvataggio...' : 'Salva tutto' }}
          </button>
        </div>
      </div>
    </template>

    <CloudinaryBrowser
      :visible="showCloudinary"
      @close="showCloudinary = false"
      @select="onCloudinarySelect"
    />
  </div>
</template>

<style scoped>
/* ── Page ── */
.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 24px; font-weight: 800; margin: 0; }
.page-subtitle { font-size: 14px; color: var(--text-light); margin-top: 4px; }
.loading-text { color: var(--text-light); padding: 24px 0; }

/* ── Preview ── */
.preview-wrap { margin-bottom: 32px; }

.preview-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.preview-frame {
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  transition: max-width 0.3s;
  contain: inline-size;
}
.frame--desktop { max-width: 100%; }
.frame--mobile { max-width: 390px; margin: 0 auto; }

/* ── Editor ── */
.editor-section-wrap { display: flex; flex-direction: column; gap: 14px; }
.mgmt-title { margin-bottom: 4px; }

.card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 18px 20px;
}

.editor-section { display: flex; flex-direction: column; gap: 10px; }
.section-title { font-size: 14px; font-weight: 700; margin: 0; }
.section-hint { font-size: 12px; color: var(--text-light); margin: -2px 0 2px; }

.section-header-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
}
.section-hint-inline {
  font-size: 11px;
  color: var(--text-light);
  white-space: nowrap;
}

.input {
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--bg-main);
  outline: none;
  transition: border-color 0.2s;
  width: 100%;
  box-sizing: border-box;
}
.input:focus { border-color: var(--primary); }
.input--lg { font-size: 20px; font-weight: 700; padding: 10px 14px; }

/* ── Image + Title row ── */
.img-title-row {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}
.image-picker {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex-shrink: 0;
  width: 120px;
}
.image-thumb {
  width: 120px;
  height: 150px;
  border: 2px dashed var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-main);
  transition: border-color 0.2s;
}
.image-thumb:hover { border-color: var(--primary); }
.image-thumb img { width: 100%; height: 100%; object-fit: cover; }
.image-thumb--empty { border-style: dashed; }
.image-thumb-plus { font-size: 12px; color: var(--text-light); font-weight: 500; }
.title-field { flex: 1; display: flex; flex-direction: column; gap: 8px; }

/* ── Style grid (colors + divider unified) ── */
.style-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

/* ── Paragraphs ── */
.paragraphs-list { display: flex; flex-direction: column; gap: 8px; }

.paragraph-card {
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--bg-main);
  padding: 10px;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.paragraph-card--dragging { opacity: 0.45; border-color: var(--primary); }
.paragraph-card--drop-target { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }

.paragraph-header {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 6px;
}
.paragraph-drag { cursor: grab; color: var(--text-light); font-size: 14px; line-height: 1; user-select: none; }
.paragraph-label { font-size: 11px; font-weight: 700; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em; flex: 1; }

/* ── Bold button ── */
.paragraph-bold-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  padding: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: transparent;
  cursor: pointer;
  font-size: 13px;
  line-height: 1;
  color: var(--text);
  transition: background 0.15s, color 0.15s, border-color 0.15s;
}
.paragraph-bold-btn:hover,
.paragraph-bold-btn--active {
  background: var(--primary);
  color: #fff;
  border-color: var(--primary);
}

.paragraph-remove-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  padding: 0;
  border: none;
  border-radius: var(--radius);
  background: transparent;
  cursor: pointer;
  font-size: 13px;
  line-height: 1;
  color: #ef4444;
  transition: background 0.15s;
}
.paragraph-remove-btn:hover { background: rgba(239,68,68,0.08); }

.paragraph-editable {
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  line-height: 1.6;
  min-height: 72px;
  background: var(--bg-card);
  outline: none;
  transition: border-color 0.2s;
  overflow-wrap: break-word;
  word-break: break-word;
}
.paragraph-editable:focus { border-color: var(--primary); }
.paragraph-editable:empty::before {
  content: attr(data-placeholder);
  color: var(--text-light);
  pointer-events: none;
}

.add-btn { align-self: flex-start; margin-top: 2px; }

/* ── Tags ── */
.tags-list { display: flex; flex-wrap: wrap; gap: 6px; min-height: 28px; }
.tags-empty { font-size: 12px; color: var(--text-light); font-style: italic; }
.tag-chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 12px;
  background: var(--primary);
  color: #fff;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.tag-chip-remove {
  background: none;
  border: none;
  color: rgba(255,255,255,0.7);
  cursor: pointer;
  font-size: 11px;
  padding: 0;
  line-height: 1;
}
.tag-chip-remove:hover { color: #fff; }

.tag-input-row { display: flex; gap: 6px; }
.tag-input-row .input { flex: 1; }

/* ── Controls shared ── */
.control-group {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.control-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--text-light);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.control-row {
  display: flex;
  align-items: center;
  gap: 8px;
}
.range-input {
  flex: 1;
  accent-color: var(--primary);
  height: 4px;
}
.color-input {
  width: 28px;
  height: 24px;
  padding: 0;
  border: 1px solid var(--border);
  border-radius: 4px;
  cursor: pointer;
  background: none;
}
.control-value {
  font-size: 11px;
  color: var(--text-light);
  min-width: 40px;
  text-align: right;
  font-variant-numeric: tabular-nums;
}

/* ── Save ── */
.save-row { display: flex; justify-content: flex-end; }

/* ── Device toggle ── */
.device-toggle {
  display: flex;
  gap: 2px;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 2px;
}
.device-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 28px;
  border: none;
  background: transparent;
  color: var(--text-light);
  border-radius: calc(var(--radius) - 2px);
  cursor: pointer;
  transition: all .15s;
}
.device-btn:hover {
  color: var(--text);
  background: var(--bg-main);
}
.device-btn.active {
  color: var(--primary);
  background: rgba(99,102,241,.1);
}
</style>

<script setup>
import { ref, computed, onMounted, watch, nextTick, onBeforeUnmount } from 'vue'
import { projectsPreview } from '../services/api'
import { useToast } from '../composables/useToast'
import Sortable from 'sortablejs'
import { ProjectsPreview } from '@illustreas/shared-ui'

const toast = useToast()
const featured = ref([])
const available = ref([])
const loading = ref(true)
const previewDevice = ref('desktop')

const previewProjects = computed(() =>
  featured.value.map(p => ({
    id: p.id,
    title: p.title,
    slug: p.slug,
    gif: p.gif_url || p.hero_url || '',
    info: p.info || '',
  }))
)

onMounted(load)

async function load() {
  loading.value = true
  try {
    const [f, a] = await Promise.all([
      projectsPreview.featured(),
      projectsPreview.available(),
    ])
    featured.value = f.data.sort((a, b) => a.featured_order - b.featured_order)
    available.value = a.data
  } catch {
    toast.error('Errore caricamento preview progetti')
  } finally {
    loading.value = false
  }
}

async function addProject(project) {
  try {
    await projectsPreview.toggle(project.id)
    toast.success(`"${project.title}" aggiunto alla preview`)
    await load()
  } catch {
    toast.error('Errore aggiunta alla preview')
  }
}

// --- Modal conferma rimozione ---

const confirmModal = ref({ show: false, title: '', onConfirm: null })

function requestRemove(project) {
  confirmModal.value = {
    show: true,
    title: project.title,
    onConfirm: () => doRemove(project),
  }
}

function closeConfirm() {
  confirmModal.value = { show: false, title: '', onConfirm: null }
}

async function doConfirm() {
  const fn = confirmModal.value.onConfirm
  closeConfirm()
  if (fn) await fn()
}

async function doRemove(project) {
  try {
    await projectsPreview.toggle(project.id)
    toast.success(`"${project.title}" rimosso dalla preview`)
    await load()
  } catch {
    toast.error('Errore rimozione dalla preview')
  }
}

// --- Drag (swap via SortableJS Swap plugin) ---
const featuredListRef = ref(null)
let sortableInstance = null

function initSortable() {
  if (sortableInstance) { sortableInstance.destroy(); sortableInstance = null }
  if (!featuredListRef.value) return
  sortableInstance = Sortable.create(featuredListRef.value, {
    swap: true,
    swapClass: 'swap-highlight',
    animation: 150,
    ghostClass: 'sortable-ghost',
    handle: '.drag-handle',
    async onEnd(evt) {
      const { oldIndex, newIndex } = evt
      if (oldIndex === newIndex) return
      const temp = featured.value[oldIndex]
      featured.value[oldIndex] = featured.value[newIndex]
      featured.value[newIndex] = temp
      const order = featured.value.map((p, i) => ({ id: p.id, featured_order: i }))
      try {
        await projectsPreview.reorder(order)
        toast.success('Ordine aggiornato')
      } catch (e) {
        toast.error('Errore riordino: ' + (e.response?.data?.message || e.message))
        await load()
      }
    },
  })
}

watch(loading, (val) => { if (!val) nextTick(initSortable) })
onBeforeUnmount(() => { if (sortableInstance) sortableInstance.destroy() })
</script>

<template>
  <div>
    <div class="page-header">
      <h1>Projects Preview</h1>
      <span class="counter">{{ featured.length }} in homepage</span>
    </div>
    <p class="page-desc">Progetti mostrati nella homepage. Trascina per riordinare, usa i pulsanti per aggiungere o rimuovere.</p>

    <div v-if="loading" class="card" style="text-align:center;padding:48px">Caricamento...</div>

    <template v-else>
      <!-- ===== FRONTOFFICE PREVIEW ===== -->
      <div v-if="previewProjects.length" class="fo-preview-wrap">
        <div class="fo-preview-header">
          <h2 class="section-title" style="margin-bottom:0">Anteprima frontoffice</h2>
          <div class="device-toggle">
            <button
              class="device-btn"
              :class="{ active: previewDevice === 'desktop' }"
              @click="previewDevice = 'desktop'"
              title="Desktop" aria-label="Vista desktop"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            </button>
            <button
              class="device-btn"
              :class="{ active: previewDevice === 'mobile' }"
              @click="previewDevice = 'mobile'"
              title="Mobile" aria-label="Vista mobile"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
            </button>
          </div>
        </div>
        <div class="fo-preview-frame" :class="'frame--' + previewDevice">
          <ProjectsPreview
            :projects="previewProjects"
            :force-mode="previewDevice"
            section-radius="0"
          />
        </div>
      </div>

      <!-- ===== MANAGEMENT ===== -->
      <h3 class="section-title mgmt-title">In Homepage</h3>

      <div v-if="!featured.length" class="card empty-state">
        Nessun progetto nella preview. Aggiungine uno dalla lista qui sotto.
      </div>

      <div v-else ref="featuredListRef" class="stack">
        <div v-for="p in featured" :key="p.id" class="preview-row card">
          <span class="drag-handle" title="Trascina per riordinare">&#10495;</span>
          <div class="preview-thumb-wrap">
            <img v-if="p.gif_url" :src="p.gif_url" class="preview-thumb" />
            <img v-else-if="p.hero_url" :src="p.hero_url" class="preview-thumb" />
            <div v-else class="preview-thumb preview-thumb--empty">?</div>
          </div>
          <div class="preview-info">
            <div class="preview-title">{{ p.title }}</div>
            <div class="preview-meta">{{ p.info || '—' }}</div>
          </div>
          <button class="btn btn-sm btn-danger" @click="requestRemove(p)">Rimuovi</button>
        </div>
      </div>

      <h3 class="section-title mt-32">Progetti disponibili</h3>

      <div v-if="!available.length" class="card empty-state">
        Tutti i progetti pubblicati sono gi&agrave; nella preview.
      </div>

      <div v-else class="stack">
        <div v-for="p in available" :key="p.id" class="preview-row card">
          <div class="preview-thumb-wrap">
            <img v-if="p.gif_url" :src="p.gif_url" class="preview-thumb" />
            <img v-else-if="p.hero_url" :src="p.hero_url" class="preview-thumb" />
            <div v-else class="preview-thumb preview-thumb--empty">?</div>
          </div>
          <div class="preview-info">
            <div class="preview-title">{{ p.title }}</div>
            <div class="preview-meta">{{ p.info || '—' }}</div>
          </div>
          <button class="btn btn-sm btn-primary" @click="addProject(p)">+ Aggiungi</button>
        </div>
      </div>
    </template>

    <!-- Modal conferma rimozione -->
    <Teleport to="body">
      <div v-if="confirmModal.show" class="modal-backdrop" @click.self="closeConfirm">
        <div class="confirm-modal">
          <div class="confirm-body">
            <p>Sei sicuro di voler rimuovere <strong>{{ confirmModal.title }}</strong> dalla preview?</p>
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
.page-desc{color:var(--text-light);font-size:14px;margin-bottom:24px}
.counter{font-size:14px;font-weight:600;color:var(--text-light);background:var(--bg-card);padding:4px 12px;border-radius:var(--radius);border:1px solid var(--border)}
.section-title{font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-light);margin-bottom:12px}
.mgmt-title{margin-top:8px}
.mt-32{margin-top:32px}

/* ── Preview ── */
.fo-preview-wrap{margin-bottom:32px}
.fo-preview-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}

.device-toggle{display:flex;gap:2px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:2px}
.device-btn{display:flex;align-items:center;justify-content:center;width:32px;height:28px;border:none;background:transparent;color:var(--text-light);border-radius:calc(var(--radius) - 2px);cursor:pointer;transition:all .15s}
.device-btn:hover{color:var(--text);background:var(--bg-main)}
.device-btn.active{color:var(--primary);background:rgba(99,102,241,.1)}

.fo-preview-frame{border:1px solid var(--border);border-radius:12px;overflow:hidden;background:#f8f8fa;transition:max-width .3s ease;contain:inline-size}
.frame--desktop{max-width:100%}
.frame--mobile{max-width:390px;margin:0 auto}
.empty-state{text-align:center;padding:32px;color:var(--text-light);font-size:14px}

.preview-row{display:flex;align-items:center;gap:16px;padding:12px 16px}

.preview-thumb-wrap{flex-shrink:0;width:120px;height:80px;border-radius:var(--radius);overflow:hidden;background:#f0f0f0}
.preview-thumb{width:100%;height:100%;object-fit:cover;display:block}
.preview-thumb--empty{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--text-light);background:var(--bg-main)}

.preview-info{flex:1;min-width:0}
.preview-title{font-weight:600;font-size:15px}
.preview-meta{font-size:12px;color:var(--text-light);margin-top:2px}

.sortable-ghost{opacity:.4}
.swap-highlight{outline:2px dashed var(--primary);outline-offset:-2px;background:rgba(99,102,241,.06);border-radius:var(--radius)}

.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:flex;align-items:center;justify-content:center;padding:24px}
.confirm-modal{background:var(--bg-card);border-radius:var(--radius-lg);box-shadow:0 24px 48px rgba(0,0,0,.2);width:100%;max-width:400px;overflow:hidden}
.confirm-body{padding:24px;text-align:center}
.confirm-body p{margin:0;font-size:14px;color:var(--text);line-height:1.5}
.confirm-footer{display:flex;justify-content:flex-end;gap:8px;padding:16px 24px;border-top:1px solid var(--border)}
</style>

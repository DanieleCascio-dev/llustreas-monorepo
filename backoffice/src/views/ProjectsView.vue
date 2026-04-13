<script setup>
import { ref, computed, onMounted, watch, nextTick, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { projects } from '../services/api'
import { useToast } from '../composables/useToast'
import { useConfirmModal } from '../composables/useConfirmModal'
import Sortable from 'sortablejs'
import { ProjectsGrid } from '@illustreas/shared-ui'

const toast = useToast()
const modal = useConfirmModal()
const router = useRouter()
const list = ref([])
const loading = ref(true)
const previewDevice = ref('desktop')
const searchQuery = ref('')

const previewProjects = computed(() =>
  list.value
    .filter(p => p.is_published)
    .map(p => ({
      id: p.id,
      title: p.title,
      slug: p.slug,
      hero: p.hero_url || '',
      order: p.order,
    }))
)

onMounted(async () => {
  await load()
})

async function load() {
  loading.value = true
  try {
    const { data } = await projects.list()
    list.value = data.sort((a, b) => a.order - b.order)
  } catch {
    toast.error('Errore caricamento progetti')
  } finally {
    loading.value = false
  }
}

// --- Drag (swap via SortableJS Swap plugin) ---
const listRef = ref(null)
let sortableInstance = null

function initSortable() {
  if (sortableInstance) { sortableInstance.destroy(); sortableInstance = null }
  if (!listRef.value) return
  sortableInstance = Sortable.create(listRef.value, {
    swap: true,
    swapClass: 'swap-highlight',
    animation: 150,
    ghostClass: 'sortable-ghost',
    handle: '.drag-handle',
    async onEnd(evt) {
      const { oldIndex, newIndex } = evt
      if (oldIndex === newIndex) return
      const temp = list.value[oldIndex]
      list.value[oldIndex] = list.value[newIndex]
      list.value[newIndex] = temp
      const order = list.value.map((p, i) => ({ id: p.id, order: i }))
      try {
        await projects.reorder(order)
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

const filteredList = computed(() => {
  const q = searchQuery.value.toLowerCase().trim()
  if (!q) return list.value
  return list.value.filter(p =>
    p.title.toLowerCase().includes(q) || p.slug?.toLowerCase().includes(q)
  )
})

async function requestDelete(p) {
  const ok = await modal.open({
    title: `Elimina "${p.title}"?`,
    message: 'Questa azione non può essere annullata.',
  })
  if (!ok) return
  try {
    await projects.delete(p.id)
    list.value = list.value.filter(x => x.id !== p.id)
    toast.success(`"${p.title}" eliminato`)
  } catch (e) {
    toast.error('Errore eliminazione: ' + (e.response?.data?.message || e.message))
  }
}

async function duplicateProject(p) {
  const ok = await modal.open({
    title: `Duplica "${p.title}"?`,
    message: 'Verrà creata una copia in bozza.',
    confirmLabel: 'Duplica',
    variant: 'primary',
  })
  if (!ok) return
  try {
    const { data } = await projects.duplicate(p.id)
    toast.success(`"${p.title}" duplicato`)
    await load()
  } catch (e) {
    toast.error('Errore duplicazione: ' + (e.response?.data?.message || e.message))
  }
}
</script>

<template>
  <div>
    <div class="page-header">
      <h1>Progetti</h1>
      <router-link to="/projects/new" class="btn btn-primary">+ Nuovo progetto</router-link>
    </div>

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
          <ProjectsGrid
            :projects="previewProjects"
            :force-mode="previewDevice"
          />
        </div>
      </div>

      <!-- ===== MANAGEMENT ===== -->
      <div class="mgmt-header">
        <h3 class="section-title mgmt-title">Lista progetti</h3>
        <input
          v-model="searchQuery"
          class="search-input"
          type="search"
          placeholder="Cerca progetti…"
        />
      </div>

      <div ref="listRef" class="stack">
        <div v-for="p in filteredList" :key="p.id" class="project-row card">
          <span class="drag-handle" title="Trascina per riordinare" aria-label="Trascina per riordinare">&#10495;</span>
          <img v-if="p.hero_url" :src="p.hero_url" class="project-thumb" />
          <div class="project-info">
            <div class="project-title">{{ p.title }}</div>
            <div class="project-meta">
              <span class="badge" :class="p.is_published ? 'badge--published' : 'badge--draft'">
                {{ p.is_published ? 'Pubblicato' : 'Bozza' }}
              </span>
              <span class="meta-text">{{ p.images_count || 0 }} immagini</span>
              <span class="meta-text">{{ p.layout }}</span>
            </div>
          </div>
          <div class="project-actions">
            <button class="btn btn-sm btn-ghost" @click="duplicateProject(p)" title="Duplica progetto" aria-label="Duplica progetto">⧉</button>
            <router-link :to="`/projects/${p.id}`" class="btn btn-sm btn-ghost">Modifica</router-link>
            <button class="btn btn-sm btn-danger" @click="requestDelete(p)">Elimina</button>
          </div>
        </div>
      </div>
      <p v-if="searchQuery && !filteredList.length" class="no-results">Nessun progetto trovato.</p>
    </template>
  </div>
</template>

<style scoped>
.section-title{font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-light);margin-bottom:12px}
.mgmt-title{margin-top:8px}

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

/* ── Project list ── */
.project-row{display:flex;align-items:center;gap:16px;padding:16px}
.project-thumb{width:80px;height:56px;object-fit:cover;border-radius:var(--radius);background:#f0f0f0}
.project-info{flex:1;min-width:0}
.project-title{font-weight:600;font-size:15px}
.project-meta{display:flex;align-items:center;gap:8px;margin-top:4px}
.meta-text{font-size:12px;color:var(--text-light)}
.project-actions{display:flex;gap:8px}

.badge{display:inline-block;font-size:11px;font-weight:600;padding:2px 8px;border-radius:10px;text-transform:uppercase;letter-spacing:.3px}
.badge--published{background:#d1fae5;color:#065f46}
.badge--draft{background:#f3f4f6;color:#6b7280}

.sortable-ghost{opacity:.4}
.swap-highlight{outline:2px dashed var(--primary);outline-offset:-2px;background:rgba(99,102,241,.06);border-radius:var(--radius)}

.mgmt-header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:8px;margin-bottom:12px}
.mgmt-header .mgmt-title{margin:0}
.search-input{padding:6px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;background:var(--bg-card);outline:none;width:220px;transition:border-color .2s}
.search-input:focus{border-color:var(--primary)}
.no-results{font-size:13px;color:var(--text-light);text-align:center;padding:24px 0}
</style>

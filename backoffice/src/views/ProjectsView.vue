<script setup>
import { ref, onMounted, watch, nextTick, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { projects } from '../services/api'
import { useToast } from '../composables/useToast'
import Sortable from 'sortablejs'

const toast = useToast()
const router = useRouter()
const list = ref([])
const loading = ref(true)

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

// --- Modal conferma eliminazione ---

const confirmModal = ref({ show: false, title: '', onConfirm: null })

function requestDelete(p) {
  confirmModal.value = {
    show: true,
    title: p.title,
    onConfirm: () => doDelete(p),
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

async function doDelete(p) {
  try {
    await projects.delete(p.id)
    list.value = list.value.filter(x => x.id !== p.id)
    toast.success(`"${p.title}" eliminato`)
  } catch (e) {
    toast.error('Errore eliminazione: ' + (e.response?.data?.message || e.message))
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

    <div v-else ref="listRef" class="stack">
      <div v-for="p in list" :key="p.id" class="project-row card">
        <span class="drag-handle" title="Trascina per riordinare">&#10495;</span>
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
          <router-link :to="`/projects/${p.id}`" class="btn btn-sm btn-ghost">Modifica</router-link>
          <button class="btn btn-sm btn-danger" @click="requestDelete(p)">Elimina</button>
        </div>
      </div>
    </div>

    <!-- Modal conferma eliminazione -->
    <Teleport to="body">
      <div v-if="confirmModal.show" class="modal-backdrop" @click.self="closeConfirm">
        <div class="confirm-modal">
          <div class="confirm-body">
            <p>Sei sicuro di voler eliminare <strong>{{ confirmModal.title }}</strong>?</p>
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

.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:flex;align-items:center;justify-content:center;padding:24px}
.confirm-modal{background:var(--bg-card);border-radius:var(--radius-lg);box-shadow:0 24px 48px rgba(0,0,0,.2);width:100%;max-width:400px;overflow:hidden}
.confirm-body{padding:24px;text-align:center}
.confirm-body p{margin:0;font-size:14px;color:var(--text);line-height:1.5}
.confirm-footer{display:flex;justify-content:flex-end;gap:8px;padding:16px 24px;border-top:1px solid var(--border)}
</style>

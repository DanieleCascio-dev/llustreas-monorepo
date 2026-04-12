<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { instagram } from '../services/api'
import { useToast } from '../composables/useToast'
import InstagramPreview from '../components/InstagramPreview.vue'

const toast = useToast()

const posts = ref([])
const loading = ref(true)
const newUrl = ref('')
const adding = ref(false)

const expandedPosts = ref(new Set())
const embedKeys = ref({})

const confirmModal = ref({ show: false, post: null })

const activePosts = computed(() => posts.value.filter(p => p.active))

async function load() {
  loading.value = true
  try {
    const { data } = await instagram.list()
    posts.value = data
  } catch {
    toast.error('Errore caricamento post Instagram')
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await load()
  loadEmbedScript()
})

function extractPostId(url) {
  const match = url.match(/instagram\.com\/(?:p|reel)\/([A-Za-z0-9_-]+)/)
  return match ? match[1] : null
}

async function addPost() {
  const url = newUrl.value.trim()
  if (!url) return

  if (!extractPostId(url)) {
    toast.error('URL Instagram non valido. Usa un link tipo https://www.instagram.com/p/...')
    return
  }

  adding.value = true
  try {
    const { data } = await instagram.create({ url })
    posts.value.push(data)
    newUrl.value = ''
    const s = new Set(expandedPosts.value)
    s.add(data.id)
    expandedPosts.value = s
    embedKeys.value[data.id] = 1
    toast.success('Post aggiunto!')
    await nextTick()
    scheduleEmbed()
  } catch {
    toast.error('Errore durante l\'aggiunta del post')
  } finally {
    adding.value = false
  }
}

async function toggleActive(post) {
  try {
    const { data } = await instagram.update(post.id, { active: !post.active })
    const idx = posts.value.findIndex(p => p.id === post.id)
    if (idx >= 0) posts.value[idx] = data
  } catch {
    toast.error('Errore aggiornamento stato')
  }
}

function askRemovePost(post) {
  confirmModal.value = { show: true, post }
}

function closeConfirm() {
  confirmModal.value = { show: false, post: null }
}

async function confirmRemovePost() {
  const post = confirmModal.value.post
  if (!post) return
  closeConfirm()
  try {
    await instagram.delete(post.id)
    posts.value = posts.value.filter(p => p.id !== post.id)
    toast.success('Post rimosso')
  } catch {
    toast.error('Errore durante la rimozione')
  }
}

function toggleExpand(postId) {
  const s = new Set(expandedPosts.value)
  if (s.has(postId)) {
    s.delete(postId)
  } else {
    s.add(postId)
    embedKeys.value[postId] = (embedKeys.value[postId] || 0) + 1
  }
  expandedPosts.value = s
}

function expandAll() {
  posts.value.forEach(p => {
    embedKeys.value[p.id] = (embedKeys.value[p.id] || 0) + 1
  })
  expandedPosts.value = new Set(posts.value.map(p => p.id))
  nextTick(() => scheduleEmbed())
}

function collapseAll() {
  expandedPosts.value = new Set()
}

async function onPreviewReorder(fromId, toId) {
  const fromIdx = posts.value.findIndex(p => p.id === fromId)
  const toIdx = posts.value.findIndex(p => p.id === toId)
  if (fromIdx < 0 || toIdx < 0) return

  const temp = posts.value[fromIdx]
  posts.value[fromIdx] = posts.value[toIdx]
  posts.value[toIdx] = temp

  const order = posts.value.map((p, i) => ({ id: p.id, order: i }))
  try {
    await instagram.reorder(order)
  } catch {
    toast.error('Errore riordinamento')
    await load()
  }
}

// --- Accordion drag & drop (swap) ---

const listDragging = ref(null)
const listDropTarget = ref(null)

function onListDragStart(id) { listDragging.value = id }
function onListDragOver(e, id) { e.preventDefault(); listDropTarget.value = id }
function onListDragLeave() { listDropTarget.value = null }
async function onListDrop(targetId) {
  listDropTarget.value = null
  if (listDragging.value === null || listDragging.value === targetId) return

  const fromIdx = posts.value.findIndex(p => p.id === listDragging.value)
  const toIdx = posts.value.findIndex(p => p.id === targetId)
  if (fromIdx < 0 || toIdx < 0) return

  const temp = posts.value[fromIdx]
  posts.value[fromIdx] = posts.value[toIdx]
  posts.value[toIdx] = temp

  const order = posts.value.map((p, i) => ({ id: p.id, order: i }))
  try {
    await instagram.reorder(order)
  } catch {
    toast.error('Errore riordinamento')
    await load()
  }
  listDragging.value = null
}
function onListDragEnd() { listDragging.value = null; listDropTarget.value = null }

// --- Embed script ---

function loadEmbedScript() {
  if (document.querySelector('script[src*="instagram.com/embed.js"]')) {
    reloadEmbed()
    return
  }
  const script = document.createElement('script')
  script.src = '//www.instagram.com/embed.js'
  script.async = true
  script.onload = () => reloadEmbed()
  document.head.appendChild(script)
}

function reloadEmbed() {
  if (window.instgrm) window.instgrm.Embeds.process()
}

function scheduleEmbed() {
  nextTick(() => setTimeout(reloadEmbed, 150))
}

watch(posts, scheduleEmbed, { deep: true })
watch(expandedPosts, scheduleEmbed)
</script>

<template>
  <div class="ig-view">
    <h1 class="page-title">Instagram</h1>
    <p class="page-subtitle">Gestisci i post Instagram da mostrare sul sito. Trascina le card nella preview per riordinare.</p>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">Caricamento...</div>

    <template v-else>
      <!-- ===== FRONTOFFICE PREVIEW ===== -->
      <div v-if="activePosts.length" class="preview-wrap">
        <h2 class="section-title">Anteprima frontoffice</h2>
        <InstagramPreview
          :posts="activePosts"
          @reorder="onPreviewReorder"
        />
      </div>

      <!-- ===== MANAGEMENT SECTION ===== -->
      <div class="mgmt-section">
        <h2 class="section-title">Gestione post</h2>

        <div class="add-form">
          <input
            v-model="newUrl"
            type="url"
            class="input"
            placeholder="https://www.instagram.com/p/ABC123/"
            @keydown.enter="addPost"
          />
          <button class="btn btn-primary" :disabled="adding || !newUrl.trim()" @click="addPost">
            {{ adding ? 'Aggiungendo...' : '+ Aggiungi post' }}
          </button>
        </div>

        <div v-if="!posts.length" class="empty-state">
          Nessun post Instagram aggiunto. Incolla un link qui sopra per iniziare.
        </div>

        <template v-else>
          <div class="bulk-actions">
            <button class="btn btn-sm btn-ghost" @click="expandAll">▼ Espandi tutti</button>
            <button class="btn btn-sm btn-ghost" @click="collapseAll">▲ Comprimi tutti</button>
            <span class="post-count">{{ posts.length }} post</span>
          </div>

          <div class="posts-list">
            <div
              v-for="post in posts"
              :key="post.id"
              class="post-card"
              :class="{
                'post-card--dragging': listDragging === post.id,
                'post-card--drop': listDropTarget === post.id,
                inactive: !post.active,
                expanded: expandedPosts.has(post.id),
              }"
              draggable="true"
              @dragstart="onListDragStart(post.id)"
              @dragover="(e) => onListDragOver(e, post.id)"
              @dragleave="onListDragLeave"
              @drop="onListDrop(post.id)"
              @dragend="onListDragEnd"
            >
              <div class="post-header" @click="toggleExpand(post.id)">
                <span class="drag-handle" title="Trascina per riordinare" @click.stop>⠿</span>
                <span class="accordion-arrow" :class="{ open: expandedPosts.has(post.id) }">▶</span>
                <span class="post-url" :title="post.url">{{ post.url }}</span>
                <div class="post-actions" @click.stop>
                  <button
                    class="btn btn-sm"
                    :class="post.active ? 'btn-ghost' : 'btn-outline'"
                    @click="toggleActive(post)"
                    :title="post.active ? 'Disattiva' : 'Attiva'"
                  >
                    {{ post.active ? '👁' : '👁‍🗨' }}
                  </button>
                  <button class="btn btn-sm btn-danger" @click="askRemovePost(post)" title="Rimuovi">✕</button>
                </div>
              </div>

              <div v-if="expandedPosts.has(post.id)" class="post-embed" :key="'embed-' + post.id + '-' + (embedKeys[post.id] || 0)">
                <blockquote
                  class="instagram-media"
                  :data-instgrm-permalink="post.url"
                  data-instgrm-version="14"
                  data-instgrm-captioned
                  style="max-width:100%;width:100%"
                />
              </div>
            </div>
          </div>
        </template>
      </div>
    </template>

    <Teleport to="body">
      <div v-if="confirmModal.show" class="modal-backdrop" @click.self="closeConfirm">
        <div class="confirm-modal">
          <div class="confirm-body">
            <p>Sei sicuro di voler rimuovere questo post Instagram?</p>
            <span class="confirm-url">{{ confirmModal.post?.url }}</span>
          </div>
          <div class="confirm-footer">
            <button class="btn btn-ghost" @click="closeConfirm">Annulla</button>
            <button class="btn btn-danger" @click="confirmRemovePost">Rimuovi</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.ig-view {
  max-width: 1200px;
}

.page-title {
  font-size: 24px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 4px;
}
.page-subtitle {
  color: var(--text-secondary);
  font-size: 14px;
  margin-bottom: 24px;
}

.loading-state,
.empty-state {
  text-align: center;
  color: var(--text-secondary);
  padding: 60px 20px;
  font-size: 15px;
}

.preview-wrap {
  margin-bottom: 32px;
}

/* ===== MANAGEMENT SECTION ===== */
.mgmt-section {
  margin-top: 0;
}

.section-title {
  font-size: 18px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 16px;
}

.add-form {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
}
.add-form .input {
  flex: 1;
  padding: 10px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--bg-input);
  color: var(--text-primary);
  font-size: 14px;
}
.add-form .input:focus {
  outline: none;
  border-color: var(--primary);
}

.bulk-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
}
.post-count {
  margin-left: auto;
  font-size: 13px;
  color: var(--text-secondary);
}

.posts-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-width: 700px;
}

.post-card {
  background: var(--bg-card);
  border: 2px solid var(--border);
  border-radius: var(--radius-lg, 12px);
  overflow: hidden;
  transition: border-color 0.2s, opacity 0.2s, box-shadow 0.2s;
}
.post-card--dragging { opacity: 0.5; }
.post-card--drop {
  border-color: var(--primary);
  box-shadow: 0 0 0 2px var(--primary);
}
.post-card.inactive { opacity: 0.5; }

.post-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  cursor: pointer;
  user-select: none;
  transition: background 0.15s;
}
.post-header:hover {
  background: rgba(255,255,255,0.03);
}
.post-card.expanded .post-header {
  border-bottom: 1px solid var(--border);
}

.accordion-arrow {
  font-size: 10px;
  color: var(--text-secondary);
  transition: transform 0.2s;
  flex-shrink: 0;
}
.accordion-arrow.open {
  transform: rotate(90deg);
}
.drag-handle {
  cursor: grab;
  font-size: 18px;
  color: var(--text-secondary);
  user-select: none;
}
.post-url {
  flex: 1;
  font-size: 12px;
  color: var(--text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.post-actions {
  display: flex;
  gap: 6px;
  flex-shrink: 0;
}

.post-embed {
  padding: 12px;
  min-height: 200px;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  overflow: hidden;
}
.post-embed :deep(iframe) {
  border-radius: 8px !important;
}

.btn-danger {
  background: transparent;
  border: 1px solid var(--danger, #e53e3e);
  color: var(--danger, #e53e3e);
}
.btn-danger:hover {
  background: var(--danger, #e53e3e);
  color: white;
}
</style>

<style>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.45);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}
.confirm-modal {
  background: var(--bg-card);
  border-radius: var(--radius-lg, 12px);
  box-shadow: 0 24px 48px rgba(0,0,0,.2);
  width: 100%;
  max-width: 420px;
  overflow: hidden;
}
.confirm-body {
  padding: 24px;
  text-align: center;
}
.confirm-body p {
  margin: 0 0 8px;
  font-size: 14px;
  color: var(--text-primary);
  line-height: 1.5;
}
.confirm-url {
  display: block;
  font-size: 12px;
  color: var(--text-secondary);
  word-break: break-all;
}
.confirm-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 16px 24px;
  border-top: 1px solid var(--border);
}
</style>

<script setup>
import { onMounted, onBeforeUnmount, watch, ref } from 'vue'
import { useConfirmModal } from '../composables/useConfirmModal'

const { state, confirm, cancel } = useConfirmModal()

const modalRef = ref(null)
const confirmBtnRef = ref(null)

function onKeydown(e) {
  if (!state.show) return
  if (e.key === 'Escape') { e.preventDefault(); cancel() }
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown))

watch(() => state.show, (open) => {
  if (open) {
    requestAnimationFrame(() => confirmBtnRef.value?.focus())
  }
})
</script>

<template>
  <Teleport to="body">
    <transition name="cm-fade">
      <div
        v-if="state.show"
        ref="modalRef"
        class="cm-backdrop"
        role="dialog"
        aria-modal="true"
        :aria-label="state.title || 'Conferma'"
        @click.self="cancel"
      >
        <div class="cm-dialog">
          <div v-if="state.thumb" class="cm-thumb">
            <img :src="state.thumb" alt="" />
          </div>
          <div class="cm-body">
            <p v-if="state.title" class="cm-title">{{ state.title }}</p>
            <p v-if="state.message" class="cm-message">{{ state.message }}</p>
          </div>
          <div class="cm-footer">
            <button class="btn btn-ghost" @click="cancel">{{ state.cancelLabel }}</button>
            <button
              ref="confirmBtnRef"
              class="btn"
              :class="state.variant === 'danger' ? 'btn-danger' : 'btn-primary'"
              @click="confirm"
            >{{ state.confirmLabel }}</button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<style scoped>
.cm-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.45);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.cm-dialog {
  background: var(--bg-card);
  border-radius: var(--radius-lg);
  box-shadow: 0 24px 48px rgba(0,0,0,.2);
  width: 100%;
  max-width: 400px;
  overflow: hidden;
}

.cm-thumb {
  display: flex;
  justify-content: center;
  padding: 16px 16px 0;
}
.cm-thumb img {
  width: 64px;
  height: 64px;
  object-fit: cover;
  border-radius: var(--radius);
}

.cm-body {
  padding: 24px;
  text-align: center;
}
.cm-title {
  margin: 0 0 6px;
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
  line-height: 1.4;
}
.cm-message {
  margin: 0;
  font-size: 14px;
  color: var(--text-light);
  line-height: 1.5;
}

.cm-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 16px 24px;
  border-top: 1px solid var(--border);
}

.cm-fade-enter-active { transition: opacity .15s ease; }
.cm-fade-leave-active { transition: opacity .1s ease; }
.cm-fade-enter-from, .cm-fade-leave-to { opacity: 0; }
</style>

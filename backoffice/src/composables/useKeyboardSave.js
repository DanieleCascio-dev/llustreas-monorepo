import { onMounted, onBeforeUnmount } from 'vue'

export function useKeyboardSave(saveFn) {
  function handler(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
      e.preventDefault()
      saveFn()
    }
  }

  onMounted(() => document.addEventListener('keydown', handler))
  onBeforeUnmount(() => document.removeEventListener('keydown', handler))
}

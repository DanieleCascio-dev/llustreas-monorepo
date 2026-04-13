import { reactive, readonly } from 'vue'

const state = reactive({
  show: false,
  title: '',
  message: '',
  thumb: '',
  confirmLabel: 'Sì',
  cancelLabel: 'Annulla',
  variant: 'danger',
  _resolve: null,
})

function open({
  title = '',
  message = '',
  thumb = '',
  confirmLabel = 'Sì',
  cancelLabel = 'Annulla',
  variant = 'danger',
} = {}) {
  return new Promise((resolve) => {
    state.title = title
    state.message = message
    state.thumb = thumb
    state.confirmLabel = confirmLabel
    state.cancelLabel = cancelLabel
    state.variant = variant
    state._resolve = resolve
    state.show = true
  })
}

function confirm() {
  state.show = false
  if (state._resolve) state._resolve(true)
  state._resolve = null
}

function cancel() {
  state.show = false
  if (state._resolve) state._resolve(false)
  state._resolve = null
}

export function useConfirmModal() {
  return {
    state: readonly(state),
    open,
    confirm,
    cancel,
  }
}

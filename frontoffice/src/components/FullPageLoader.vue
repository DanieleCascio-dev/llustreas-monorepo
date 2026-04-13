<script setup lang="ts">
// Componente: FullPageLoader
// - Overlay full-screen con spinner
// - Garantisce visibilità minima (minDuration)
// - Non interferisce col tuo lock scroll del menu: usa una classe dedicata sul body

import { onBeforeUnmount, ref, watch, withDefaults, defineProps, defineEmits } from 'vue'

// Props tipizzate
type Props = {
  modelValue: boolean                  // v-model per mostrare/nascondere
  minDuration?: number                 // ms di visibilità minima (default 1000)
  lockScroll?: boolean                 // blocca lo scroll del body (default true)
  zIndex?: number                      // z-index dell'overlay (default 2000)
  background?: string                  // colore/sfondo overlay (puoi passare 'var(--header-bg)')
  ariaLabel?: string                   // label accessibile
  accentColor?: string                 // colore spinner (default: var(--header-underline) o viola)
}

const props = withDefaults(defineProps<Props>(), {
  minDuration: 1000,
  lockScroll: true,
  zIndex: 2000,
  background: '#f5f1ef',
  ariaLabel: 'Caricamento in corso',
  accentColor: 'var(--brand-violet, #5b3c88)'
})

// Emits
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'shown'): void
  (e: 'hidden'): void
}>()

// Stato interno: visibilità effettiva dell'overlay
const visible = ref(false)

// Timer e flag per gestione durata minima
let minTimerId: number | undefined
let minElapsed = false

// Scroll lock non-invasivo: usiamo una classe dedicata
let savedScrollY = 0
const lockBodyScroll = () => {
  if (!props.lockScroll) return
  savedScrollY = window.scrollY
  document.body.style.position = 'fixed'
  document.body.style.top = `-${savedScrollY}px`
  document.body.style.left = '0'
  document.body.style.right = '0'
  document.body.style.overflow = 'hidden'
}
const unlockBodyScroll = () => {
  if (!props.lockScroll) return
  document.body.style.position = ''
  document.body.style.top = ''
  document.body.style.left = ''
  document.body.style.right = ''
  document.body.style.overflow = ''
  window.scrollTo(0, savedScrollY)
}

// Mostra overlay e avvia timer di durata minima
const show = () => {
  clearTimers()
  visible.value = true
  lockBodyScroll()
  minElapsed = false
  minTimerId = window.setTimeout(() => {
    minElapsed = true
    // Se nel frattempo il parent ha già chiesto di chiudere (modelValue=false), chiudi ora
    if (!props.modelValue) hideNow()
  }, props.minDuration)
  emit('shown')
}

// Nascondi overlay rispettando la durata minima
const scheduleHide = () => {
  if (minElapsed) {
    hideNow()
  } // altrimenti: aspettiamo che il timer scada
}

const hideNow = () => {
  if (!visible.value) return
  clearTimers()
  visible.value = false
  unlockBodyScroll()
  emit('hidden')
  // Allinea lo stato con il parent se non è già false
  if (props.modelValue) emit('update:modelValue', false)
}

const clearTimers = () => {
  if (minTimerId) {
    window.clearTimeout(minTimerId)
    minTimerId = undefined
  }
}

// Reagisci ai cambi di v-model
watch(
    () => props.modelValue,
    (val) => {
      if (val) show()
      else scheduleHide()
    },
    { immediate: true }
)

onBeforeUnmount(() => {
  clearTimers()
  unlockBodyScroll()
})
</script>

<template>
  <Transition name="fp-fade">
    <div
        v-if="visible"
        class="fp-loader"
        role="status"
        :aria-label="ariaLabel"
        aria-live="polite"
        :style="{ zIndex: String(zIndex), background: background, color: accentColor }"
    >
      <svg class="fp-logo" viewBox="0 0 486.96 397.68" aria-hidden="true">
        <path d="m31.31,190.44c5.46-.76,4.97,4.66,3.94,8.13-.24.8-.4,1.64-.44,2.48-1.15,21.7,10.33.92,17.31-5.71.65-.61,1.77-1.92,2.97-1.79,1.95.21,2.78,1.67,2.93,3.42.66,7.76-16.19,23.65-23.38,21.86-10.48-2.61-7.61-27.28-3.33-28.38Z"/>
        <path d="m35.53,175.56c6.84.35,4.16,9.7-1.28,8.95-4.58-1.39-2.39-8.37,1.28-8.95Z"/>
        <path d="m72.3,154.53c6.31,4.35-7.42,40.91-2.07,54.74.89,2.31,3.87,3.02,5.77,1.43,4.95-4.14,6.97-9.79,10.82-14.51.66-.81,1.63-1.34,2.68-1.4,13.88-.84-14.32,36.5-23.16,23.26-8.83-13.13-4.41-50.6,3.42-62.94.54-.86,1.7-1.15,2.54-.58Z"/>
        <path d="m108.89,155.62c3.44,7.52-8.07,40.3-3.07,53.56.88,2.34,3.87,3.09,5.79,1.49,4.9-4.11,6.94-9.72,10.7-14.39.69-.86,1.73-1.41,2.84-1.41,13.41-.07-14.56,35.96-22.49,23.45-10.13-11.18-5.24-49.63,2.44-62.8.86-1.47,3.09-1.43,3.79.11Z"/>
        <path d="m144.08,207.36c4.99-6.03,7.15-25.15,14.43-18.16.64.61,1.05,1.44,1.15,2.32.49,4.72-1.48,12.47.86,16.27.62,1,1.94,1.25,2.91.59,5.63-3.85,11.96-20.88,16.48-12.22.53,1.01.61,2.22.18,3.28-4.2,10.57-20.62,27.55-27.83,9.66-13.99,19.99-22.63-.75-19.03-16.81.24-1.07.68-2.14,1.39-2.94s1.29-1.13,2.01-1.35c7.28-2.24,3.05,11.25,3.79,18.24.19,1.82,2.5,2.52,3.67,1.11Z"/>
        <path d="m215.88,191.99c-8.25.23-18.36,12.84-23.08,5.21-.69-1.11-.63-2.54.17-3.57,2.09-2.68,4.91-3.79,7.75-5,.94-.4,1.66-1.26,1.74-2.28.22-2.91-3.29-5.08-6.48-4.35-10.75,4.31-18.23,19.13-5.37,24.04-18.27,14.99-5.91,37.93,13.29,21.22,6.96-5.88,9.3-16.1,2.81-23.27,4-3.13,18.21-4.66,12.62-10.54-.9-.94-2.17-1.5-3.47-1.46Zm-21.69,32.71c-11.58,1.88-.04-12.38,4.86-15.34,9.19,2.59-.3,13.65-4.86,15.34Z"/>
        <path d="m232.29,154.89c6.28-.84,4.43,6.54,3.32,10.48,5.37-.53,11.92,3.4,7.07,7.27-.54.43-1.23.64-1.91.61-8.04-.35-6.88.86-8.22,8.23-3.38,21.24.06,45.57,15.42,14.95.47-.94,1.46-1.54,2.51-1.47,12.05.75-8.41,30.11-18.44,24.11-13.95-7.34-5.13-32.6-7.25-43.68-.18-.96-1.1-1.59-2.06-1.41-3.63.7-8.16,3.11-10.89.34-3.67-8.63,12.35-6.33,16.11-9.21.91-3.47.78-8.04,4.35-10.23Z"/>
        <path d="m267.32,208.08c6.84.35,4.16,9.7-1.28,8.95-4.58-1.39-2.39-8.37,1.28-8.95Z"/>
        <path d="m282.65,184.9c6.74,1.86,16.89-3.34,20.73,4,2.62,6.98-6.54,17.78-1.02,23.27,8.43-3.08,10.07-14.05,17.88-18.08,1.63-.84,3.66.12,3.94,1.93,1.34,8.62-19.41,29.06-26.67,22.8-10.13-4.35-.54-18.69-2.55-25.22-.24-.76-1.02-1.23-1.79-1.05-5.63,1.35-6.11,15.97-12.69,9.78-.63-.59-.91-1.47-.79-2.33.43-3.16,2.52-4.24,2.99-8.03-2.42-1.11-3.78-4.14-2.46-6.17.52-.8,1.52-1.16,2.45-.91Z"/>
        <path d="m346.85,208.59c-6.11,5.03-17.57,4.67-12.78-6.39,12.07,6.69,23.4-13.58,12.49-17.74-3.08-1.17-6.56-.47-9.16,1.55-32.34,25.02,2.86,53.15,30.47,13.17,1.03-1.48,1.1-3.51-.03-4.91-4.49-5.51-16.41,12.53-20.98,14.32Zm-4.08-16.75c.73,1.3-3.14,4.89-4.59,3.79-.23-.17-.29-.49-.18-.75.51-1.24,2.78-2.45,3.92-3.19.28-.18.69-.14.86.15Z"/>
        <path d="m405.4,205.01c-14.35,19.92-1.79-19.23-16.11-18.92-15.87-7.5-29.31,27.01-12.67,31.09,1.86.46,3.86.11,5.45-.96,2.94-1.99,5.48-5.63,7.74-7.88,5.97,21.95,24.37,2.34,27.87-8.69-.82-13.3-10.03,1.26-12.27,5.37Zm-25.77,3.06c-.81.81-2.23.46-2.53-.65-1.42-5.28,3.35-13.84,7.58-14.69,7.6-.92-.94,11.24-5.05,15.34Z"/>
        <path d="m457.46,193.91c-6.23-5.39-15.2,3.42-20.61,5.73-14.81-.95-3.25-8.75,3.07-12.27,1.7-3.13-2.11-5.79-5.11-5.37-7.29,2.08-16.81,11.56-12.02,19.43,3.78,5.16,8.41,2.92,2.05,8.95-17.78,20.08,9.61,31.92,20.71,12.28,10.07-11.99-10.72-19.23,10.74-23.52,1.42-.35,1.94-1.51,2.04-3.18.04-.77-.28-1.53-.86-2.04Zm-19.33,25.68c-15.4,15.24-12.6-4.37-.77-10.48,5.12,1.92,3.58,7.97.77,10.48Z"/>
      </svg>
      <span class="fp-sr-only">Caricamento…</span>
      <slot />
    </div>
  </Transition>
</template>

<style scoped lang="scss">
/* Overlay full-screen */
.fp-loader {
  position: fixed;
  inset: 0;
  display: grid;
  place-items: center;
}

.fp-logo {
  width: clamp(120px, 30vw, 200px);
  height: auto;

  path {
    fill: currentColor;
    fill-rule: evenodd;
  }

  animation: fp-pulse 1.6s ease-in-out infinite;
}

@keyframes fp-pulse {
  0%, 100% { opacity: 0.4; transform: scale(0.96); }
  50%      { opacity: 1;   transform: scale(1); }
}

/* Fade dell’overlay */
.fp-fade-enter-active,
.fp-fade-leave-active { transition: opacity .25s ease; }
.fp-fade-enter-from,
.fp-fade-leave-to { opacity: 0; }

/* Accessibilità */
.fp-sr-only{
  position: absolute !important;
  width: 1px !important;
  height: 1px !important;
  padding: 0 !important;
  margin: -1px !important;
  overflow: hidden !important;
  clip: rect(0,0,0,0) !important;
  white-space: nowrap !important;
  border: 0 !important;
}
</style>


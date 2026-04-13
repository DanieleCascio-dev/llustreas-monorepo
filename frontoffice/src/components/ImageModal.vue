<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  visible: { type: Boolean, required: true },
  imageSrc: { type: Object, default: () => ({ src: '', title: '' }) },
})

const emit = defineEmits(['close', 'prev', 'next'])

const isMobile = ref(window.innerWidth <= 768)
const isPortrait = ref(window.innerHeight > window.innerWidth)
const hintDismissed = ref(false)

function onResize() {
  isMobile.value = window.innerWidth <= 768
  isPortrait.value = window.innerHeight > window.innerWidth
}

function dismissHint() {
  hintDismissed.value = true
}

function onKeydown(e) {
  if (!props.visible) return
  if (e.key === 'Escape') emit('close')
  if (e.key === 'ArrowLeft') emit('prev')
  if (e.key === 'ArrowRight') emit('next')
}

onMounted(() => {
  window.addEventListener('resize', onResize)
  window.addEventListener('keydown', onKeydown)
})

let savedScrollY = 0

function lockBody() {
  savedScrollY = window.scrollY
  document.body.style.position = 'fixed'
  document.body.style.top = `-${savedScrollY}px`
  document.body.style.left = '0'
  document.body.style.right = '0'
  document.body.style.overflow = 'hidden'
}

function unlockBody() {
  document.body.style.position = ''
  document.body.style.top = ''
  document.body.style.left = ''
  document.body.style.right = ''
  document.body.style.overflow = ''
  window.scrollTo(0, savedScrollY)
}

onBeforeUnmount(() => {
  window.removeEventListener('resize', onResize)
  window.removeEventListener('keydown', onKeydown)
  unlockBody()
  clearTimeout(hintTimer)
})

let hintTimer = 0

watch(() => props.visible, (open) => {
  if (open) lockBody()
  else unlockBody()
  clearTimeout(hintTimer)
  if (open) {
    hintDismissed.value = false
    hintTimer = window.setTimeout(dismissHint, 3000)
  }
})

const touchStartX = ref(0)

function handleTouchStart(e) {
  touchStartX.value = e.touches[0].clientX
}

function handleTouchEnd(e) {
  const delta = touchStartX.value - e.changedTouches[0].clientX
  if (delta > 50) emit('next')
  else if (delta < -50) emit('prev')
}
</script>

<template>
  <div v-if="visible" class="modal-overlay" @click="emit('close')">
    <div
      class="modal-body"
      @click.stop
      @touchstart.passive="handleTouchStart"
      @touchend.passive="handleTouchEnd"
    >
      <button class="btn-close-modal" @click="emit('close')" aria-label="Chiudi">×</button>

      <img :src="imageSrc?.src" alt="Immagine" />

      <div v-if="imageSrc?.title" class="img-title">{{ imageSrc.title }}</div>

      <button class="btn-nav btn-prev" @click="emit('prev')" aria-label="Precedente">‹</button>
      <button class="btn-nav btn-next" @click="emit('next')" aria-label="Successiva">›</button>

      <!-- Suggerimento rotazione — solo portrait mobile -->
      <transition name="hint-fade">
        <div
          v-if="isMobile && isPortrait && !hintDismissed"
          class="rotate-hint"
          @click="dismissHint"
        >
          <span class="rotate-icon" aria-hidden="true">📱↻</span>
          <span>Ruota il dispositivo per una vista migliore</span>
        </div>
      </transition>
    </div>
  </div>
</template>

<style scoped lang="scss">
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.85);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 2000;
  padding: env(safe-area-inset-top, 0) env(safe-area-inset-right, 0) env(safe-area-inset-bottom, 0) env(safe-area-inset-left, 0);
}

.modal-body {
  position: relative;
  max-width: 92vw;
  max-height: 92vh;
  max-height: 92dvh;
  display: flex;
  justify-content: center;
  align-items: center;

  @media (min-width: 768px) {
    max-width: 90vw;
    max-height: 90vh;
    max-height: 90dvh;
  }
}

.modal-body img {
  display: block;
  max-width: 90vw;
  max-height: 85vh;
  max-height: 85dvh;
  width: auto;
  height: auto;
  object-fit: contain;
}

.img-title {
  position: absolute;
  bottom: -32px;
  left: 0;
  right: 0;
  color: white;
  font-size: 1rem;
  text-align: center;

  @media (min-width: 768px) {
    font-size: 1.25rem;
  }
}

.btn-close-modal {
  position: absolute;
  top: -8px;
  right: -4px;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  color: white;
  font-size: 2.5rem;
  cursor: pointer;
  z-index: 10;
  -webkit-tap-highlight-color: transparent;

  &:focus-visible {
    outline: 2px solid white;
    outline-offset: 2px;
    border-radius: 4px;
  }

  @media (hover: hover) {
    &:hover { transform: scale(1.2); }
  }

  @media (min-width: 768px) {
    top: -12px;
    right: -40px;
    font-size: 3rem;
  }
}

.btn-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.15);
  border: none;
  border-radius: 50%;
  color: white;
  font-size: 1.8rem;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  opacity: 0.5;
  transition: opacity 0.2s ease, background 0.2s ease;

  &:active {
    opacity: 1;
    background: rgba(255, 255, 255, 0.3);
  }

  &:focus-visible {
    outline: 2px solid white;
    outline-offset: 2px;
    opacity: 1;
  }

  @media (hover: hover) {
    &:hover {
      opacity: 1;
      background: rgba(255, 255, 255, 0.25);
    }
  }

  @media (min-width: 768px) {
    width: 56px;
    height: 56px;
    font-size: 2.2rem;
    opacity: 0.7;
  }
}

.btn-prev {
  left: 4px;

  @media (min-width: 768px) {
    left: -64px;
  }
}

.btn-next {
  right: 4px;

  @media (min-width: 768px) {
    right: -64px;
  }
}

.rotate-hint {
  position: absolute;
  bottom: -56px;
  left: 50%;
  transform: translateX(-50%);
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(255, 255, 255, 0.15);
  -webkit-backdrop-filter: blur(6px);
  backdrop-filter: blur(6px);
  color: rgba(255, 255, 255, 0.85);
  font-size: 0.75rem;
  padding: 6px 14px;
  border-radius: 20px;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  animation: hint-pulse 2.5s ease-in-out infinite;
}

.rotate-icon {
  font-size: 1rem;
  line-height: 1;
}

@keyframes hint-pulse {
  0%, 100% { opacity: 0.85; }
  50% { opacity: 0.5; }
}

.hint-fade-enter-active { transition: opacity 0.3s ease; }
.hint-fade-leave-active { transition: opacity 0.2s ease; }
.hint-fade-enter-from,
.hint-fade-leave-to { opacity: 0; }
</style>


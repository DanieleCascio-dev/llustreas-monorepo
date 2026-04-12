<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'

const x = ref(-100)
const y = ref(-100)
const blooming = ref(false)
const visible = ref(true)
const isTouch = ref(false)

watch(blooming, (val) => {
  document.documentElement.classList.toggle('cursor-blooming', val)
})

function onMove(e) {
  x.value = e.clientX
  y.value = e.clientY
}

const INTERACTIVE = 'a, button, [role="button"], .btn, [type="submit"], [data-cursor-pointer]'

function checkPointer(e) {
  const el = e.target?.closest?.(INTERACTIVE)
  blooming.value = !!el
}

function onEnter() { visible.value = true }
function onLeave() { visible.value = false }

function onTouchStart() {
  isTouch.value = true
  document.documentElement.classList.remove('cursor-active')
}

onMounted(() => {
  if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
    isTouch.value = true
    return
  }
  document.documentElement.classList.add('cursor-active')
  window.addEventListener('mousemove', onMove, { passive: true })
  window.addEventListener('mouseover', checkPointer, { passive: true })
  document.addEventListener('mouseenter', onEnter)
  document.addEventListener('mouseleave', onLeave)
  window.addEventListener('touchstart', onTouchStart, { once: true, passive: true })
})

onBeforeUnmount(() => {
  document.documentElement.classList.remove('cursor-active', 'cursor-blooming')
  window.removeEventListener('mousemove', onMove)
  window.removeEventListener('mouseover', checkPointer)
  document.removeEventListener('mouseenter', onEnter)
  document.removeEventListener('mouseleave', onLeave)
})
</script>

<template>
  <div
    v-if="!isTouch"
    class="custom-cursor"
    :class="{ 'is-blooming': blooming, 'is-hidden': !visible || !blooming }"
    :style="{ transform: `translate(${x}px, ${y}px)` }"
  >
    <svg class="cursor-svg" width="40" height="48" viewBox="0 0 40 48">
      <!-- Stelo inclinato -->
      <path d="M14 22 Q8 33 3 46" stroke="#5a9e4b" stroke-width="2" fill="none" stroke-linecap="round"/>
      <path d="M7 35 Q2 31 4 38" stroke="#5a9e4b" stroke-width="1.2" fill="#6db85a" stroke-linecap="round"/>
      <path d="M10 29 Q16 27 12 33" stroke="#5a9e4b" stroke-width="1.2" fill="#6db85a" stroke-linecap="round"/>

      <!-- Petali viola -->
      <ellipse class="petal p1" cx="15" cy="7" rx="3.2" ry="5.2" fill="#a88fca" stroke="#5b3c88" stroke-width=".5" transform="rotate(0,15,14)"/>
      <ellipse class="petal p2" cx="15" cy="7" rx="3.2" ry="5.2" fill="#a88fca" stroke="#5b3c88" stroke-width=".5" transform="rotate(51.4,15,14)"/>
      <ellipse class="petal p3" cx="15" cy="7" rx="3.2" ry="5.2" fill="#a88fca" stroke="#5b3c88" stroke-width=".5" transform="rotate(102.8,15,14)"/>
      <ellipse class="petal p4" cx="15" cy="7" rx="3.2" ry="5.2" fill="#a88fca" stroke="#5b3c88" stroke-width=".5" transform="rotate(154.3,15,14)"/>
      <ellipse class="petal p5" cx="15" cy="7" rx="3.2" ry="5.2" fill="#a88fca" stroke="#5b3c88" stroke-width=".5" transform="rotate(205.7,15,14)"/>
      <ellipse class="petal p6" cx="15" cy="7" rx="3.2" ry="5.2" fill="#a88fca" stroke="#5b3c88" stroke-width=".5" transform="rotate(257.1,15,14)"/>
      <ellipse class="petal p7" cx="15" cy="7" rx="3.2" ry="5.2" fill="#a88fca" stroke="#5b3c88" stroke-width=".5" transform="rotate(308.6,15,14)"/>

      <!-- Centro -->
      <circle cx="15" cy="14" r="4" fill="#f5b84d"/>
      <circle cx="15" cy="14" r="2.5" fill="#e8943a"/>
    </svg>
  </div>
</template>

<style scoped>
.custom-cursor {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 99999;
  pointer-events: none;
  will-change: transform;
  opacity: 0;
  transition: opacity 0.18s ease;
}

.custom-cursor.is-blooming {
  opacity: 1;
}

.custom-cursor.is-hidden {
  opacity: 0;
}

.cursor-svg {
  display: block;
  margin-left: -3px;
  margin-top: -46px;
  filter: drop-shadow(0 1px 2px rgba(0,0,0,0.15));
}

.petal {
  transform-origin: 15px 14px;
  opacity: 0;
  transform: scale(0);
}

.is-blooming .petal {
  animation: bloom 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}

.is-blooming .p1 { animation-delay: 0s; }
.is-blooming .p2 { animation-delay: 0.045s; }
.is-blooming .p3 { animation-delay: 0.09s; }
.is-blooming .p4 { animation-delay: 0.135s; }
.is-blooming .p5 { animation-delay: 0.18s; }
.is-blooming .p6 { animation-delay: 0.225s; }
.is-blooming .p7 { animation-delay: 0.27s; }

@keyframes bloom {
  0% {
    opacity: 0;
    transform: scale(0);
  }
  60% {
    opacity: 1;
    transform: scale(1.2);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}
</style>

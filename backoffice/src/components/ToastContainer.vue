<script setup>
import { useToast } from '../composables/useToast'

const { toasts, remove } = useToast()
</script>

<template>
  <Teleport to="body">
    <div class="toast-container">
      <TransitionGroup name="toast">
        <div
          v-for="t in toasts"
          :key="t.id"
          class="toast-item"
          :class="`toast--${t.type}`"
          @click="remove(t.id)"
        >
          <span class="toast-icon">
            <template v-if="t.type === 'success'">&#10003;</template>
            <template v-else-if="t.type === 'error'">&#10007;</template>
            <template v-else>&#8505;</template>
          </span>
          <span class="toast-msg">{{ t.message }}</span>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.toast-container{position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column-reverse;gap:8px;pointer-events:none}
.toast-item{display:flex;align-items:center;gap:10px;padding:12px 20px;border-radius:10px;font-size:14px;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,.15);pointer-events:auto;cursor:pointer;max-width:380px;backdrop-filter:blur(8px)}
.toast--success{background:#10b981;color:#fff}
.toast--error{background:#ef4444;color:#fff}
.toast--info{background:#6366f1;color:#fff}
.toast-icon{font-size:16px;flex-shrink:0}
.toast-msg{line-height:1.4}

.toast-enter-active{transition:all .3s ease}
.toast-leave-active{transition:all .25s ease}
.toast-enter-from{opacity:0;transform:translateX(40px)}
.toast-leave-to{opacity:0;transform:translateX(40px)}
.toast-move{transition:transform .3s ease}
</style>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const email = ref('')
const password = ref('')
const error = ref('')

async function handleLogin() {
  error.value = ''
  const ok = await auth.login(email.value, password.value)
  if (ok) {
    router.push('/')
  } else {
    error.value = 'Credenziali non valide'
  }
}
</script>

<template>
  <div class="login-page">
    <form class="login-card" @submit.prevent="handleLogin">
      <div class="login-logo">illust.reas</div>
      <p class="login-sub">Backoffice</p>

      <div v-if="error" class="login-error">{{ error }}</div>

      <div class="stack">
        <div>
          <label class="label">Email</label>
          <input v-model="email" type="email" class="input" placeholder="admin@illustreas.com" required autofocus />
        </div>
        <div>
          <label class="label">Password</label>
          <input v-model="password" type="password" class="input" placeholder="••••••••" required />
        </div>
        <button type="submit" class="btn btn-primary login-btn" :disabled="auth.loading">
          {{ auth.loading ? 'Accesso...' : 'Accedi' }}
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.login-page{min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg-sidebar)}
.login-card{background:var(--bg-card);border-radius:var(--radius-lg);padding:40px;width:380px;box-shadow:var(--shadow-lg)}
.login-logo{font-size:28px;font-weight:800;text-align:center;color:var(--primary)}
.login-sub{text-align:center;color:var(--text-light);font-size:14px;margin-bottom:24px}
.login-error{background:#fff0f0;color:var(--danger);padding:10px;border-radius:var(--radius);font-size:14px;text-align:center;margin-bottom:8px}
.login-btn{width:100%;justify-content:center;padding:12px;font-size:15px;margin-top:8px}
</style>

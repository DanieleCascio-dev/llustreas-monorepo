import { defineStore } from 'pinia'
import { ref } from 'vue'
import { auth as authApi } from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const loading = ref(false)

  const isAuthenticated = () => !!user.value

  async function login(email, password) {
    loading.value = true
    try {
      const { data } = await authApi.login(email, password)
      user.value = data.user
      localStorage.setItem('authenticated', '1')
      return true
    } catch {
      return false
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try { await authApi.logout() } catch { /* ignore */ }
    user.value = null
    localStorage.removeItem('authenticated')
  }

  async function fetchUser() {
    if (!localStorage.getItem('authenticated')) return false
    try {
      const { data } = await authApi.user()
      user.value = data
      return true
    } catch {
      user.value = null
      localStorage.removeItem('authenticated')
      return false
    }
  }

  return { user, loading, isAuthenticated, login, logout, fetchUser }
})

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

const nav = [
  { path: '/', label: 'Dashboard', icon: '◈' },
  { path: '/projects', label: 'Progetti', icon: '▦' },
  { path: '/projects-preview', label: 'Projects Preview', icon: '▣' },
  { path: '/gallery', label: 'Galleria', icon: '◫' },
  { path: '/gallery-preview', label: 'Gallery Preview', icon: '◧' },
  { path: '/hero', label: 'Hero', icon: '◆' },
  { path: '/about-me', label: 'Chi Sono', icon: '✦' },
  { path: '/instagram', label: 'Instagram', icon: '📷' },
  { path: '/settings', label: 'Impostazioni', icon: '⚙' },
]

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="layout">
    <aside class="sidebar">
      <div class="sidebar-logo">illust.reas</div>
      <nav class="sidebar-nav">
        <router-link
          v-for="item in nav"
          :key="item.path"
          :to="item.path"
          class="nav-item"
          :class="{
            active:
              item.path === '/'
                ? $route.path === '/'
                : $route.path === item.path || $route.path.startsWith(item.path + '/'),
          }"
        >
          <span class="nav-icon">{{ item.icon }}</span>
          {{ item.label }}
        </router-link>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-user">{{ auth.user?.name || 'Admin' }}</div>
        <button class="btn btn-sm btn-ghost sidebar-logout" @click="handleLogout">Esci</button>
      </div>
    </aside>
    <main class="main">
      <router-view />
    </main>
  </div>
</template>

<style scoped>
.layout{display:flex;min-height:100vh}
.sidebar{width:var(--sidebar-w);background:var(--bg-sidebar);color:var(--text-sidebar);display:flex;flex-direction:column;padding:20px 0;position:fixed;top:0;left:0;bottom:0;z-index:10}
.sidebar-logo{font-size:22px;font-weight:800;color:#fff;padding:0 24px;margin-bottom:32px}
.sidebar-nav{flex:1;display:flex;flex-direction:column;gap:2px;padding:0 12px}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:var(--radius);color:var(--text-sidebar);font-size:14px;font-weight:500;transition:all var(--transition)}
.nav-item:hover{background:rgba(255,255,255,.06);color:#fff}
.nav-item.active{background:var(--primary);color:#fff}
.nav-icon{font-size:16px;width:20px;text-align:center}
.sidebar-footer{padding:16px 24px;border-top:1px solid rgba(255,255,255,.08)}
.sidebar-user{font-size:13px;color:var(--text-sidebar);margin-bottom:8px}
.sidebar-logout{width:100%;justify-content:center;color:var(--text-sidebar);border-color:rgba(255,255,255,.15)}
.sidebar-logout:hover{color:#fff;border-color:rgba(255,255,255,.3)}
.main{flex:1;margin-left:var(--sidebar-w);padding:32px;min-width:0;overflow-x:hidden}
</style>

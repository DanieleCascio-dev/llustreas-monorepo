import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from './stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('./views/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: '/',
    component: () => import('./components/AppLayout.vue'),
    meta: { auth: true },
    children: [
      { path: '', name: 'Dashboard', component: () => import('./views/DashboardView.vue') },
      { path: 'projects', name: 'Projects', component: () => import('./views/ProjectsView.vue') },
      { path: 'projects/new', name: 'ProjectCreate', component: () => import('./views/ProjectEditView.vue') },
      { path: 'projects/:id', name: 'ProjectEdit', component: () => import('./views/ProjectEditView.vue'), props: true },
      { path: 'projects-preview', name: 'ProjectsPreview', component: () => import('./views/ProjectsPreviewView.vue') },
      { path: 'gallery', name: 'Gallery', component: () => import('./views/GalleryView.vue') },
      { path: 'gallery-preview', name: 'GalleryPreview', component: () => import('./views/GalleryPreviewView.vue') },
      { path: 'hero', name: 'Hero', component: () => import('./views/HeroView.vue') },
      { path: 'about-me', name: 'AboutMe', component: () => import('./views/AboutMeView.vue') },
      { path: 'instagram', name: 'Instagram', component: () => import('./views/InstagramView.vue') },
      { path: 'settings', name: 'Settings', component: () => import('./views/SettingsView.vue') },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.user && localStorage.getItem('authenticated')) {
    await auth.fetchUser()
  }

  if (to.meta.auth && !auth.isAuthenticated()) {
    return { name: 'Login' }
  }
  if (to.meta.guest && auth.isAuthenticated()) {
    return { path: '/' }
  }
})

export default router

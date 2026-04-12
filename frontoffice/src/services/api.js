/**
 * Servizio API per il frontoffice.
 * Tutte le chiamate passano dal proxy Vite verso il backend Laravel.
 */
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '',
  headers: { Accept: 'application/json' },
})

// Endpoint pubblici (non richiedono autenticazione)
export const publicApi = {
  projects: () => api.get('/api/public/projects'),
  project: (slug) => api.get(`/api/public/projects/${slug}`),
  gallery: () => api.get('/api/public/gallery'),
  galleryPreview: () => api.get('/api/public/gallery-preview'),
  projectsPreview: () => api.get('/api/public/projects-preview'),
  setting: (key) => api.get(`/api/public/settings/${key}`),
  hero: () => api.get('/api/public/hero'),
  instagram: () => api.get('/api/public/instagram'),
}

export default api

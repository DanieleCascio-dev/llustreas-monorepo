import axios from 'axios'

const api = axios.create({
  baseURL: '',
  withCredentials: true,
  headers: { 'Accept': 'application/json' },
})

api.interceptors.response.use(
  res => res,
  err => {
    if (err.response?.status === 401) {
      localStorage.removeItem('authenticated')
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    }
    return Promise.reject(err)
  }
)

export async function csrf() {
  await api.get('/sanctum/csrf-cookie')
}

export const auth = {
  async login(email, password) {
    await csrf()
    return api.post('/api/login', { email, password })
  },
  logout: () => api.post('/api/logout'),
  user: () => api.get('/api/user'),
}

export const projects = {
  list: () => api.get('/api/admin/projects'),
  get: (id) => api.get(`/api/admin/projects/${id}`),
  create: (data) => api.post('/api/admin/projects', data),
  update: (id, data) => api.put(`/api/admin/projects/${id}`, data),
  delete: (id) => api.delete(`/api/admin/projects/${id}`),
  duplicate: (id) => api.post(`/api/admin/projects/${id}/duplicate`),
  reorder: (order) => api.put('/api/admin/projects-reorder', { order }),
}

export const projectImages = {
  list: (projectId) => api.get(`/api/admin/projects/${projectId}/images`),
  create: (projectId, data) => api.post(`/api/admin/projects/${projectId}/images`, data),
  update: (projectId, imageId, data) => api.put(`/api/admin/projects/${projectId}/images/${imageId}`, data),
  delete: (projectId, imageId) => api.delete(`/api/admin/projects/${projectId}/images/${imageId}`),
  reorder: (projectId, order) => api.put(`/api/admin/projects/${projectId}/images-reorder`, { order }),
}

export const gallery = {
  list: (layout = 'desktop') => api.get('/api/admin/gallery', { params: { layout } }),
  create: (data) => api.post('/api/admin/gallery', data),
  update: (id, data) => api.put(`/api/admin/gallery/${id}`, data),
  delete: (id) => api.delete(`/api/admin/gallery/${id}`),
  reorder: (order, layout = 'desktop') => api.put('/api/admin/gallery-reorder', { order, layout }),
}

export const projectsPreview = {
  featured: () => api.get('/api/admin/projects-preview'),
  available: () => api.get('/api/admin/projects-preview/available'),
  toggle: (id) => api.put(`/api/admin/projects-preview/${id}/toggle`),
  reorder: (order) => api.put('/api/admin/projects-preview-reorder', { order }),
}

export const galleryPreview = {
  list: () => api.get('/api/admin/gallery-preview'),
  create: (data) => api.post('/api/admin/gallery-preview', data),
  update: (id, data) => api.put(`/api/admin/gallery-preview/${id}`, data),
  delete: (id) => api.delete(`/api/admin/gallery-preview/${id}`),
  reorder: (order) => api.put('/api/admin/gallery-preview-reorder', { order }),
}

export const cloudinaryBrowser = {
  folders: (path) => api.get('/api/admin/cloudinary/folders', { params: { path } }),
  createFolder: (path) => api.post('/api/admin/cloudinary/folders', { path }),
  deleteFolder: (path) => api.delete('/api/admin/cloudinary/folders', { data: { path } }),
  images: (folder, cursor) => api.get('/api/admin/cloudinary/images', { params: { folder, cursor } }),
  sign: (folder) => api.post('/api/admin/cloudinary/sign', { folder }),
}

export const hero = {
  list: () => api.get('/api/admin/hero'),
  create: (data) => api.post('/api/admin/hero', data),
  update: (id, data) => api.put(`/api/admin/hero/${id}`, data),
  delete: (id) => api.delete(`/api/admin/hero/${id}`),
  reorder: (order) => api.put('/api/admin/hero-reorder', { order }),
}

export const heroImages = {
  create: (layerId, data) => api.post(`/api/admin/hero/${layerId}/images`, data),
  update: (layerId, imageId, data) => api.put(`/api/admin/hero/${layerId}/images/${imageId}`, data),
  delete: (layerId, imageId) => api.delete(`/api/admin/hero/${layerId}/images/${imageId}`),
  reorder: (layerId, order) => api.put(`/api/admin/hero/${layerId}/images-reorder`, { order }),
}

export const instagram = {
  list: () => api.get('/api/admin/instagram'),
  create: (data) => api.post('/api/admin/instagram', data),
  update: (id, data) => api.put(`/api/admin/instagram/${id}`, data),
  delete: (id) => api.delete(`/api/admin/instagram/${id}`),
  reorder: (order) => api.put('/api/admin/instagram-reorder', { order }),
  fetchThumbnail: (id) => api.post(`/api/admin/instagram/${id}/fetch-thumbnail`),
}

export const settings = {
  get: (key) => api.get(`/api/admin/settings/${key}`),
  update: (key, value) => api.put(`/api/admin/settings/${key}`, { value }),
}

export default api

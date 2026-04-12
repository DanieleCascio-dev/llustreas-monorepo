import { createPinia, defineStore } from 'pinia'
import { publicApi } from './services/api'

const pinia = createPinia()

/**
 * Store principale dell'applicazione.
 * Tutti i dati vengono caricati dall'API Laravel (non più statici).
 * Ogni fetch avviene una sola volta grazie ai flag *Loaded.
 */
export const useProjectStore = defineStore('projectStore', {
  state: () => ({
    // PROGETTI (lista per pagina /projects)
    projects: [],
    projectsLoaded: false,

    // PROGETTI IN EVIDENZA (homepage)
    featuredProjects: [],
    featuredLoaded: false,

    // GALLERIA (3 colonne di immagini)
    columns: [],
    columnsLoaded: false,

    // GALLERIA PREVIEW (immagini homepage)
    galleryPreview: [],
    galleryPreviewLoaded: false,

    // PROGETTO SINGOLO (dettaglio corrente)
    currentProject: null,
    currentProjectSlug: null,

    // HERO LAYERS (parallax homepage)
    heroLayers: [],
    heroLayersLoaded: false,

    // INSTAGRAM POSTS
    instagramPosts: [],
    instagramPostsLoaded: false,

    // ABOUT ME
    aboutMe: null,
    aboutMeLoaded: false,

    // IMPOSTAZIONI SITO
    galleryPreviewMode: 'carousel',
    galleryPreviewModeLoaded: false,
  }),

  getters: {
    getProjectBySlug: (state) => (slug) => {
      return state.projects.find((p) => p.slug === slug)
    },
    getProjectById: (state) => (id) => {
      return state.projects.find((p) => p.id === parseInt(id))
    },
  },

  actions: {
    // Carica la lista dei progetti pubblicati
    async fetchProjects() {
      if (this.projectsLoaded) return
      try {
        const { data } = await publicApi.projects()
        this.projects = data.map((p) => ({
          id: p.id,
          title: p.title,
          slug: p.slug,
          hero: p.hero_url,
          gif: p.gif_url,
          layout: p.layout,
          order: p.order,
          description: p.description,
          info: p.info,
        }))
        this.projectsLoaded = true
      } catch (e) {
        console.error('Errore caricamento progetti:', e)
      }
    },

    // Carica i progetti in evidenza per la homepage
    async fetchFeaturedProjects() {
      if (this.featuredLoaded) return
      try {
        const { data } = await publicApi.projectsPreview()
        this.featuredProjects = data.map((p) => ({
          id: p.id,
          title: p.title,
          slug: p.slug,
          gif: p.gif_url,
          info: p.info,
        }))
        this.featuredLoaded = true
      } catch (e) {
        console.error('Errore caricamento progetti in evidenza:', e)
      }
    },

    // Carica un progetto singolo per slug (sempre fresco, senza cache)
    async fetchProject(slug) {
      try {
        const { data } = await publicApi.project(slug)
        this.currentProject = data
        this.currentProjectSlug = slug
      } catch (e) {
        console.error('Errore caricamento progetto:', e)
        this.currentProject = null
        this.currentProjectSlug = null
      }
    },

    // Carica le 3 colonne della galleria completa
    async fetchGallery() {
      if (this.columnsLoaded) return
      try {
        const { data } = await publicApi.gallery()
        this.columns = data
        this.columnsLoaded = true
      } catch (e) {
        console.error('Errore caricamento galleria:', e)
      }
    },

    // Carica i layer hero per il parallax
    async fetchHeroLayers() {
      if (this.heroLayersLoaded) return
      try {
        const { data } = await publicApi.hero()
        this.heroLayers = data
        this.heroLayersLoaded = true
      } catch (e) {
        console.error('Errore caricamento hero layers:', e)
      }
    },

    // Carica i post Instagram
    async fetchInstagramPosts() {
      if (this.instagramPostsLoaded) return
      try {
        const { data } = await publicApi.instagram()
        this.instagramPosts = data
        this.instagramPostsLoaded = true
      } catch (e) {
        console.error('Errore caricamento post Instagram:', e)
      }
    },

    // Carica i dati About Me
    async fetchAboutMe() {
      if (this.aboutMeLoaded) return
      try {
        const { data } = await publicApi.setting('about_me')
        if (data.value) this.aboutMe = data.value
        this.aboutMeLoaded = true
      } catch {
        this.aboutMeLoaded = true
      }
    },

    // Carica la modalita' gallery preview (carousel o grid)
    async fetchGalleryPreviewMode() {
      if (this.galleryPreviewModeLoaded) return
      try {
        const { data } = await publicApi.setting('gallery_preview_mode')
        if (data.value) this.galleryPreviewMode = data.value
        this.galleryPreviewModeLoaded = true
      } catch {
        this.galleryPreviewModeLoaded = true
      }
    },

    // Carica le immagini della gallery preview (homepage)
    async fetchGalleryPreview() {
      if (this.galleryPreviewLoaded) return
      try {
        const { data } = await publicApi.galleryPreview()
        this.galleryPreview = data
        this.galleryPreviewLoaded = true
      } catch (e) {
        console.error('Errore caricamento gallery preview:', e)
      }
    },
  },
})

export default pinia

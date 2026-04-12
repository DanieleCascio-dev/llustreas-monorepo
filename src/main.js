import { createApp } from 'vue'
import { router } from "./router";
import pinia from './store';
import { MotionPlugin } from '@vueuse/motion'
// importiamo bootstrap
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'


import { vReveal } from './directives/reveal.js'
import App from './App.vue'
// ✅ Applica il tema “light” solo per ProjectDetail PRIMA del mount
// ✅ Calcola la route iniziale PRIMA del mount (senza aspettare currentRoute)
const initial = router.resolve(
    window.location.pathname + window.location.search + window.location.hash
)
// Solo per ProjectDetail (come richiesto)
const firstIsLight = initial.name === 'ProjectDetail'
// Se in futuro userai meta: const firstIsLight = initial.matched.some(r => r.meta?.lightHeader)

document.documentElement.classList.toggle('header--light', firstIsLight)

const app = createApp(App)
app.directive('reveal', vReveal)
app.use(router).use(pinia).use(MotionPlugin).mount('#app')

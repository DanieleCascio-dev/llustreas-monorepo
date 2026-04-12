import { createRouter, createWebHistory } from "vue-router";

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior(to, from, savedPosition) {
    if (to.hash) {
      return new Promise((resolve) => {
        const check = () => {
          const el = document.querySelector(to.hash)
          if (el) {
            resolve({ el: to.hash, behavior: 'smooth' })
          } else {
            requestAnimationFrame(check)
          }
        }
        check()
      })
    }
    if (savedPosition) return savedPosition
    return { top: 0, behavior: 'instant' }
  },
  routes: [
    {
      path: "/",
      name: "home",
      component: () => import("/src/pages/Home.vue"),
    },
    {
      path: "/projects",
      name: "projects",
      component: () => import("/src/pages/Projects.vue"),
    },
    {
      path: "/about",
      name: "about",
      component: () => import("/src/pages/About.vue"),
    },
    {
      path: "/project/:slug",
      name: "ProjectDetail",
      meta: { lightHeader: true },
      component: () => import("/src/pages/ProjectDetail.vue"),
      props: true,
    },
    {
      path: "/gallery",
      name: "gallery",
      component: () => import("/src/pages/Gallery.vue"),
    },
    {
      path: "/:pathMatch(.*)*",
      name: "NotFound",
      component: () => import("./pages/NotFound.vue"),
    },
  ],
});

router.beforeEach((to, from, next) => {
  const isLight = to.matched.some(r => r.meta?.lightHeader)
  document.documentElement.classList.toggle('header--light', isLight)
  next()
})

export { router };

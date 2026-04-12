/**
 * v-reveal directive
 * Adds a scroll-triggered reveal animation using IntersectionObserver.
 * Usage: v-reveal or v-reveal="200" (delay in ms for stagger)
 */
const observerMap = new WeakMap()

export const vReveal = {
  mounted(el, binding) {
    const delay = typeof binding.value === 'number' ? binding.value : 0

    el.classList.add('reveal')
    if (delay) {
      el.style.transitionDelay = `${delay}ms`
    }

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          el.classList.add('reveal--visible')
          observer.unobserve(el)
        }
      },
      { threshold: 0.15 }
    )

    observer.observe(el)
    observerMap.set(el, observer)
  },

  unmounted(el) {
    const observer = observerMap.get(el)
    if (observer) {
      observer.disconnect()
      observerMap.delete(el)
    }
  }
}

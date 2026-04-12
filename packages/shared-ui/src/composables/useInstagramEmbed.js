import { nextTick } from 'vue'

export function useInstagramEmbed() {
  function loadEmbedScript() {
    if (window.instgrm) {
      window.instgrm.Embeds.process()
      return
    }
    if (document.querySelector('script[src*="instagram.com/embed.js"]')) return
    const script = document.createElement('script')
    script.src = '//www.instagram.com/embed.js'
    script.async = true
    script.onload = () => {
      if (window.instgrm) window.instgrm.Embeds.process()
    }
    document.head.appendChild(script)
  }

  function scheduleEmbed(delay = 200) {
    nextTick(() =>
      setTimeout(() => {
        if (window.instgrm) window.instgrm.Embeds.process()
      }, delay),
    )
  }

  return { loadEmbedScript, scheduleEmbed }
}

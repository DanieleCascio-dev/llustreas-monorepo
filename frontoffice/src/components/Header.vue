<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const navbarOpen = ref(false)
const activeNav = ref<'home' | 'projects' | 'gallery' | 'about' | ''>('')

const toggleNav = () => { navbarOpen.value = !navbarOpen.value }
const closeNav = () => { navbarOpen.value = false }

let savedScrollY = 0

watch(navbarOpen, (open) => {
  if (open) {
    savedScrollY = window.scrollY
    document.body.style.position = 'fixed'
    document.body.style.top = `-${savedScrollY}px`
    document.body.style.left = '0'
    document.body.style.right = '0'
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.position = ''
    document.body.style.top = ''
    document.body.style.left = ''
    document.body.style.right = ''
    document.body.style.overflow = ''
    window.scrollTo(0, savedScrollY)
  }
})

watch(() => route.fullPath, () => {
  closeNav()
})

const smoothScrollTo = (hash: string) => {
  const el = document.querySelector(hash)
  if (el) {
    const headerH = document.querySelector('nav.nav-fixed')?.clientHeight ?? 80
    const y = el.getBoundingClientRect().top + window.scrollY - headerH
    window.scrollTo({ top: y, left: 0, behavior: 'smooth' })
  }
}

const onNavClickTop = () => {
  closeNav()
  requestAnimationFrame(() => {
    window.scrollTo({ top: 0, left: 0, behavior: 'smooth' })
  })
}

const handleGalleryClick = () => {
  activeNav.value = 'gallery'
  closeNav()
}

const scrollToAboutMe = async () => {
  closeNav()
  if (route.name === 'home') {
    smoothScrollTo('#about-me')
  } else {
    await router.push({ path: '/', hash: '#about-me' })
  }
  activeNav.value = 'about'
}

const handleHomeClick = () => {
  activeNav.value = 'home'
  onNavClickTop()
}
const handleProjectsClick = () => {
  activeNav.value = 'projects'
  onNavClickTop()
}
</script>

<template>
  <nav class="nav-fixed" role="navigation" aria-label="Menu principale">
    <div class="nav-inner">
      <!-- Hamburger a destra su mobile — min 44x44 touch area -->
      <button
        class="hamburger-btn"
        type="button"
        :aria-expanded="navbarOpen ? 'true' : 'false'"
        aria-label="Apri menu"
        @click="toggleNav"
      >
        <span class="hamburger" :class="{ 'is-open': navbarOpen }" aria-hidden="true">
          <span class="bar"></span>
          <span class="bar"></span>
          <span class="bar"></span>
        </span>
      </button>

      <!-- Overlay mobile + link desktop -->
      <div class="nav-menu" :class="{ 'is-open': navbarOpen }" :aria-hidden="!navbarOpen && 'true'">
        <ul class="nav-list">
          <li class="nav-item" style="--i:0">
            <router-link to="/" :class="{ 'is-active': activeNav === 'home' }" class="nav-link" @click="handleHomeClick">HOME</router-link>
          </li>
          <li class="nav-item" style="--i:1">
            <router-link to="/projects" :class="{ 'is-active': activeNav === 'projects' }" class="nav-link" @click="handleProjectsClick">PROGETTI</router-link>
          </li>
          <li class="nav-item" style="--i:2">
            <router-link to="/gallery" :class="{ 'is-active': activeNav === 'gallery' }" class="nav-link" @click="handleGalleryClick">ILLUSTRAZIONI</router-link>
          </li>
          <li class="nav-item" style="--i:3">
            <a href="#about-me" :class="{ 'is-active': activeNav === 'about' }" class="nav-link" @click.prevent="scrollToAboutMe">CHI SONO</a>
          </li>
        </ul>

        <div class="nav-social" style="--i:4">
          <a href="https://www.instagram.com/illust.reas/" target="_blank" rel="noopener noreferrer" class="nav-social-link" aria-label="Instagram">
            <img src="../assets/img/icone/instagram.svg" alt="Instagram" />
          </a>
          <a href="https://www.behance.net/letiziaragione" target="_blank" rel="noopener noreferrer" class="nav-social-link" aria-label="Behance">
            <img src="../assets/img/icone/behance.svg" alt="Behance" />
          </a>
          <a href="https://www.linkedin.com/in/letizia-ragione/" target="_blank" rel="noopener noreferrer" class="nav-social-link" aria-label="LinkedIn">
            <img src="../assets/img/icone/linkedin.svg" alt="LinkedIn" />
          </a>
        </div>
      </div>
    </div>
  </nav>
</template>

<style scoped lang="scss">
@use "../style/partials/variables" as *;

/* ── Navbar fissa ── */
.nav-fixed {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 999;
  height: 60px;
  display: flex;
  align-items: center;
  background-color: var(--header-bg);
  transition: background-color 0.35s ease;
  padding: env(safe-area-inset-top, 0) env(safe-area-inset-right, 0) 0 env(safe-area-inset-left, 0);

  @media (min-width: 768px) {
    height: 80px;
  }
}

.nav-inner {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0 16px;
  position: relative;

  @media (min-width: 768px) {
    justify-content: center;
  }
}

/* ── Hamburger button — a destra, 44x44 minimo ── */
.hamburger-btn {
  position: relative;
  z-index: 1002;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  padding: 0;
  border: none;
  background: transparent;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;

  &:focus-visible {
    outline: 2px solid var(--header-fg);
    outline-offset: 2px;
    border-radius: 4px;
  }

  @media (min-width: 768px) {
    display: none;
  }
}

.hamburger {
  width: 28px;
  height: 22px;
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.hamburger .bar {
  position: absolute;
  left: 0;
  width: 100%;
  height: 2px;
  background: var(--header-fg);
  border-radius: 2px;
  transition:
    transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
    opacity 0.2s ease,
    top 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.hamburger .bar:nth-child(1) { top: 2px; }
.hamburger .bar:nth-child(2) { top: 10px; }
.hamburger .bar:nth-child(3) { top: 18px; }

.hamburger.is-open .bar:nth-child(1) { top: 10px; transform: rotate(45deg); }
.hamburger.is-open .bar:nth-child(2) { opacity: 0; }
.hamburger.is-open .bar:nth-child(3) { top: 10px; transform: rotate(-45deg); }

/* ── Menu overlay mobile / inline desktop ── */
.nav-menu {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background-color: var(--header-bg);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition: opacity 0.15s ease, visibility 0.15s ease;

  &.is-open {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
  }

  @media (min-width: 768px) {
    position: static;
    flex-direction: row;
    background-color: transparent;
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
    transition: none;
  }
}

/* ── Lista voci menu ── */
.nav-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 28px;

  @media (min-width: 768px) {
    flex-direction: row;
    gap: 2rem;
  }

  @media (min-width: 992px) {
    gap: 5rem;
  }
}

/* Stagger animazione voci su mobile */
.nav-item {
  opacity: 0;
  transform: translateY(8px);
  transition: opacity 0.15s ease, transform 0.15s ease;
  transition-delay: 0s;

  .nav-menu.is-open & {
    opacity: 1;
    transform: translateY(0);
    transition-delay: calc(var(--i, 0) * 0.04s);
  }

  @media (min-width: 768px) {
    opacity: 1;
    transform: none;
    transition: none;
  }
}

/* ── Social icons nel menu mobile ── */
.nav-social {
  display: flex;
  gap: 20px;
  margin-top: 36px;
  opacity: 0;
  transform: translateY(8px);
  transition: opacity 0.15s ease, transform 0.15s ease;
  transition-delay: 0s;

  .nav-menu.is-open & {
    opacity: 1;
    transform: translateY(0);
    transition-delay: calc(var(--i, 0) * 0.04s);
  }

  @media (min-width: 768px) {
    display: none;
  }
}

.nav-social-link {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  -webkit-tap-highlight-color: transparent;

  img {
    width: 32px;
    height: 32px;
  }

  &:active {
    opacity: 0.7;
  }

  &:focus-visible {
    outline: 2px solid var(--header-fg);
    outline-offset: 2px;
    border-radius: 4px;
  }
}

/* ── Link ── */
.nav-link {
  position: relative;
  display: inline-block;
  color: var(--header-fg);
  font-weight: 400;
  font-size: clamp(1.25rem, 5vw, 1.75rem);
  line-height: 1.2;
  text-decoration: none;
  white-space: nowrap;
  padding: 8px 4px;
  -webkit-tap-highlight-color: transparent;

  &:hover,
  &:focus,
  &:focus-visible,
  &:active,
  &:visited {
    color: var(--header-fg) !important;
    text-decoration: none;
  }

  &:focus-visible {
    outline: 2px solid var(--header-fg);
    outline-offset: 2px;
    border-radius: 2px;
  }

  &::after {
    content: "";
    position: absolute;
    left: 4px;
    right: 4px;
    bottom: 2px;
    height: 2px;
    background: var(--header-underline);
    transform: scaleX(0);
    transform-origin: left center;
    transition: transform 0.25s ease;
  }

  @media (hover: hover) {
    &:hover::after { transform: scaleX(1); }
    &.is-active:hover::after { transform: scaleX(0); }
  }

  &.is-active { font-weight: 600; }

  &.router-link-active,
  &.router-link-exact-active {
    font-weight: inherit;
  }

  &.router-link-active:not(:hover)::after,
  &.router-link-exact-active:not(:hover)::after {
    transform: scaleX(0);
  }

  &.is-active.router-link-active,
  &.is-active.router-link-exact-active { font-weight: 600; }

  @media (min-width: 768px) {
    font-size: 1rem;
    padding: 4px 0;

    &::after {
      left: 0;
      right: 0;
      bottom: -2px;
    }
  }
}
</style>

<style lang="scss">
#gallery-preview,
#about-me { scroll-margin-top: 90px; }
</style>

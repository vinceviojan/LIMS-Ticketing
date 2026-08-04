<template>
  <footer class="clay-footer">
    <div class="clay-footer__top">
      <div class="clay-footer__brand">
        <p class="clay-footer__logo">YourApp</p>
        <p class="clay-footer__tagline">
          Soft, friendly tools for getting things done — built with a little clay and a lot of care.
        </p>
      </div>

      <div class="clay-footer__columns">
        <div class="clay-footer__column">
          <h3 class="clay-footer__heading">Product</h3>
          <q-btn flat dense no-caps class="clay-footer__link" label="Features" @click="go('/features')" />
          <q-btn flat dense no-caps class="clay-footer__link" label="Pricing" @click="go('/pricing')" />
          <q-btn flat dense no-caps class="clay-footer__link" label="Changelog" @click="go('/changelog')" />
        </div>

        <div class="clay-footer__column">
          <h3 class="clay-footer__heading">Company</h3>
          <q-btn flat dense no-caps class="clay-footer__link" label="About" @click="go('/about')" />
          <q-btn flat dense no-caps class="clay-footer__link" label="Careers" @click="go('/careers')" />
          <q-btn flat dense no-caps class="clay-footer__link" label="Contact" @click="go('/contact')" />
        </div>

        <div class="clay-footer__column">
          <h3 class="clay-footer__heading">Legal</h3>
          <q-btn flat dense no-caps class="clay-footer__link" label="Privacy" @click="go('/privacy')" />
          <q-btn flat dense no-caps class="clay-footer__link" label="Terms" @click="go('/terms')" />
        </div>
      </div>

      <div class="clay-footer__newsletter">
        <h3 class="clay-footer__heading">Stay in the loop</h3>
        <form class="clay-footer__form" @submit.prevent="subscribe">
          <q-input
            v-model="email"
            type="email"
            dense
            borderless
            class="clay-footer__input"
            placeholder="you@example.com"
            required
          />
          <q-btn round unelevated class="clay-footer__submit" aria-label="Subscribe" type="submit">
            <q-icon name="arrow_forward" />
          </q-btn>
        </form>

        <div class="clay-footer__socials">
          <q-btn round unelevated class="clay-footer__social" aria-label="Twitter" href="#" target="_blank">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
              <path d="M18.9 2H22l-7.6 8.7L23.3 22h-7l-5.5-6.6L4.5 22H1.4l8.1-9.3L1 2h7.2l5 6.1L18.9 2zm-1.2 18h1.7L7.4 4H5.6l12.1 16z" />
            </svg>
          </q-btn>
          <q-btn round unelevated class="clay-footer__social" aria-label="GitHub" href="#" target="_blank">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
              <path d="M12 .5C5.7.5.9 5.3.9 11.6c0 5 3.2 9.2 7.8 10.7.6.1.8-.3.8-.6v-2.2c-3.2.7-3.9-1.4-3.9-1.4-.5-1.4-1.3-1.7-1.3-1.7-1.1-.7.1-.7.1-.7 1.2.1 1.8 1.2 1.8 1.2 1 1.8 2.8 1.3 3.5 1 .1-.8.4-1.3.8-1.6-2.6-.3-5.3-1.3-5.3-5.7 0-1.3.5-2.3 1.2-3.1-.1-.3-.5-1.5.1-3.1 0 0 1-.3 3.3 1.2a11.4 11.4 0 0 1 6 0c2.3-1.5 3.3-1.2 3.3-1.2.6 1.6.2 2.8.1 3.1.8.8 1.2 1.9 1.2 3.1 0 4.4-2.7 5.4-5.3 5.7.4.4.8 1.1.8 2.2v3.3c0 .3.2.7.8.6 4.6-1.5 7.8-5.7 7.8-10.7C23.1 5.3 18.3.5 12 .5z" />
            </svg>
          </q-btn>
          <q-btn round unelevated class="clay-footer__social" aria-label="LinkedIn" href="#" target="_blank">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
              <path d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zM3 9h4v12H3zM9 9h3.8v1.6h.1c.5-1 1.9-2.1 3.9-2.1 4.2 0 5 2.7 5 6.3V21h-4v-5.6c0-1.3 0-3-1.8-3s-2.1 1.4-2.1 3V21H9z" />
            </svg>
          </q-btn>
        </div>
      </div>
    </div>

    <div class="clay-footer__divider" />

    <div class="clay-footer__bottom">
      <p class="clay-footer__copyright">© {{ year }} YourApp. All rights reserved.</p>
      <q-btn round unelevated class="clay-footer__totop" aria-label="Back to top" @click="scrollTop">
        <q-icon name="arrow_upward" />
      </q-btn>
    </div>
  </footer>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const email = ref('')
const year = computed(() => new Date().getFullYear())

const go = (path) => {
  router.push(path)
}

const subscribe = () => {
  // wire this up to your API / store action
  console.log('subscribe:', email.value)
  email.value = ''
}

const scrollTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>

<style lang="scss" scoped>
@import './footer.scss';
</style>
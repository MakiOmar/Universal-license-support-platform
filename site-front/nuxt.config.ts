// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  modules: [
    '@nuxt/eslint',
    '@nuxt/hints',
    '@nuxt/image',
    '@nuxt/scripts',
    '@nuxt/ui',
    '@nuxt/test-utils',
    '@pinia/nuxt'
  ],

  css: ['~/assets/css/main.css'],

  vite: {
    resolve: {
      alias: {
        '~': new URL('.', import.meta.url).pathname,
        '@': new URL('.', import.meta.url).pathname,
      },
    },
  },
})
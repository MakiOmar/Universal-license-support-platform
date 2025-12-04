// https://nuxt.com/docs/api/configuration/nuxt-config
import { resolve } from 'path'
import { fileURLToPath } from 'url'

const __dirname = fileURLToPath(new URL('.', import.meta.url))

export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  
  // Ensure pages directory is detected
  pages: true,

  modules: [
    '@nuxt/eslint',
    '@nuxt/hints',
    '@nuxt/image',
    '@nuxt/scripts',
    '@nuxt/ui',
    '@nuxtjs/tailwindcss',
    '@nuxt/test-utils',
    '@pinia/nuxt'
  ],

  css: [resolve(__dirname, 'assets/css/main.css')],

  vite: {
    resolve: {
      alias: {
        '~': resolve(__dirname),
        '@': resolve(__dirname),
        '~~': resolve(__dirname),
        '@@': resolve(__dirname),
      },
    },
  },
})

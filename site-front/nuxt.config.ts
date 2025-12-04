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
    '@nuxt/test-utils',
    '@pinia/nuxt'
  ],

  // Configure @nuxt/ui to use Tailwind
  ui: {
    global: true,
    icons: ['heroicons']
  },

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

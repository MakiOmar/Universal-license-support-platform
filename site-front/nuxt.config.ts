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
    // '@nuxt/ui', // Commented out to avoid conflicts with Tailwind v3
    '@nuxt/test-utils',
    '@pinia/nuxt'
  ],

  // Public API base must include /api/v1 (Laravel route prefix)
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api/v1',
    },
  },

  css: [resolve(__dirname, 'assets/css/main.css')],

  postcss: {
    plugins: {
      tailwindcss: {},
      autoprefixer: {},
    },
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

import { defineVitestConfig } from '@nuxt/test-utils/config'
import { resolve } from 'path'
import { fileURLToPath } from 'url'
import tsconfigPaths from 'vite-tsconfig-paths'
import type { Plugin } from 'vite'

const __dirname = fileURLToPath(new URL('.', import.meta.url))
const rootDir = resolve(__dirname)

// CSS mock plugin
const cssMockPlugin = (): Plugin => ({
  name: 'mock-css',
  enforce: 'pre',
  load(id) {
    if (id.endsWith('.css') || id.includes('assets/css')) {
      return 'export default {}'
    }
  },
  resolveId(id) {
    // Handle both ~ and ~~ aliases for CSS
    if (id.includes('assets/css/main.css')) {
      return resolve(rootDir, 'assets/css/main.css')
    }
  },
})

export default defineVitestConfig({
  plugins: [
    tsconfigPaths({
      root: rootDir,
    }),
    cssMockPlugin(),
  ],
  resolve: {
    alias: {
      '~': rootDir,
      '@': rootDir,
      '~~': rootDir,
      '@@': rootDir,
      // Explicitly resolve CSS paths for both aliases
      '~/assets/css/main.css': resolve(rootDir, 'assets/css/main.css'),
      '~~/assets/css/main.css': resolve(rootDir, 'assets/css/main.css'),
    },
  },
  server: {
    deps: {
      inline: ['@nuxt/test-utils'],
    },
  },
})

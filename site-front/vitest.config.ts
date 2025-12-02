import { defineVitestConfig } from '@nuxt/test-utils/config'
import { resolve } from 'path'
import { fileURLToPath } from 'url'
import tsconfigPaths from 'vite-tsconfig-paths'

const __dirname = fileURLToPath(new URL('.', import.meta.url))
const rootDir = resolve(__dirname)

export default defineVitestConfig({
  plugins: [
    tsconfigPaths({
      root: rootDir,
    }),
    // Mock CSS imports
    {
      name: 'mock-css',
      load(id) {
        if (id.endsWith('.css')) {
          return 'export default {}'
        }
      },
    },
  ],
  resolve: {
    alias: {
      '~': rootDir,
      '@': rootDir,
      '~~': rootDir,
      '@@': rootDir,
      // Explicitly resolve CSS paths
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

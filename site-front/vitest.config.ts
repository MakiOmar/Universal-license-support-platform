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
  ],
  resolve: {
    alias: {
      '~': rootDir,
      '@': rootDir,
      '~~': rootDir,
      '@@': rootDir,
      // Resolve CSS imports to empty module
      '~/assets/css/main.css': resolve(rootDir, 'assets/css/main.css'),
    },
  },
  // Mock CSS and other asset imports
  server: {
    deps: {
      inline: ['@nuxt/test-utils'],
    },
  },
})

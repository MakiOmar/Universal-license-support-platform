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
    },
  },
  // Handle CSS and asset imports
  assetsInclude: ['**/*.css'],
  // Mock CSS imports
  server: {
    deps: {
      inline: ['@nuxt/test-utils'],
    },
  },
  // Configure Vite to handle CSS
  css: {
    modules: {
      localsConvention: 'camelCase',
    },
  },
})

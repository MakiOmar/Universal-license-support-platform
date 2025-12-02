import { defineVitestConfig } from '@nuxt/test-utils/config'
import { resolve } from 'path'
import tsconfigPaths from 'vite-tsconfig-paths'

export default defineVitestConfig({
  plugins: [
    tsconfigPaths({
      root: resolve(__dirname, '.'),
    }),
  ],
  resolve: {
    alias: {
      '~': resolve(__dirname, '.'),
      '@': resolve(__dirname, '.'),
    },
  },
})


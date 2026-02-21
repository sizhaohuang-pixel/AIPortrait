import { defineConfig } from 'vite'
import uni from '@dcloudio/vite-plugin-uni'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    uni(),
  ],
  base: './',
  server: {
    port: 8080,
    host: '0.0.0.0',
    open: true
  }
})

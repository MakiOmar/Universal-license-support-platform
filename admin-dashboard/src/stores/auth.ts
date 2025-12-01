import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const apiKey = ref<string | null>(localStorage.getItem('api_key'))
  const loading = ref(false)

  const isAuthenticated = computed(() => !!apiKey.value)

  async function login(key: string) {
    loading.value = true
    try {
      // Test the API key by making a health check or simple request
      await api.get('/health')
      apiKey.value = key
      localStorage.setItem('api_key', key)
      return true
    } catch (error) {
      console.error('Login failed:', error)
      return false
    } finally {
      loading.value = false
    }
  }

  function logout() {
    apiKey.value = null
    localStorage.removeItem('api_key')
  }

  return {
    apiKey,
    loading,
    isAuthenticated,
    login,
    logout
  }
})


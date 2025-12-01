import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('admin_token'))
  const user = ref<any>(JSON.parse(localStorage.getItem('admin_user') || 'null'))
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)

  async function login(email: string, password: string) {
    loading.value = true
    try {
      const response = await api.post('/admin/login', {
        email,
        password
      })

      token.value = response.data.token
      user.value = response.data.user
      
      localStorage.setItem('admin_token', response.data.token)
      localStorage.setItem('admin_user', JSON.stringify(response.data.user))
      
      return true
    } catch (error: any) {
      console.error('Login failed:', error)
      return false
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await api.post('/admin/logout')
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('admin_token')
      localStorage.removeItem('admin_user')
    }
  }

  async function fetchUser() {
    try {
      const response = await api.get('/admin/me')
      user.value = response.data.user
      localStorage.setItem('admin_user', JSON.stringify(response.data.user))
    } catch (error) {
      console.error('Failed to fetch user:', error)
      logout()
    }
  }

  return {
    token,
    user,
    loading,
    isAuthenticated,
    login,
    logout,
    fetchUser
  }
})


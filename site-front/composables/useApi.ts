import { useAuthStore } from '~/stores/auth'

/**
 * Normalize API base so callers always hit /api/v1, even if env is set to /api.
 */
function normalizeApiBase(base: string): string {
  const trimmed = base.replace(/\/+$/, '')

  if (trimmed.endsWith('/api/v1')) {
    return trimmed
  }

  if (trimmed.endsWith('/api')) {
    return `${trimmed}/v1`
  }

  return trimmed
}

export const useApi = () => {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()

  // Prefer runtime config (from .env / nuxt.config), keep a safe default
  const API_BASE_URL = normalizeApiBase(
    String(config.public.apiBase || 'http://localhost:8000/api/v1')
  )
  const CUSTOMER_API_BASE_URL = `${API_BASE_URL}/customer`

  async function request<T = any>(
    url: string,
    options: RequestInit = {}
  ): Promise<T> {
    const token = authStore.token

    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...options.headers,
    }

    if (token) {
      headers['Authorization'] = `Bearer ${token}`
    }

    // Allow absolute URLs; otherwise prefix with API base
    const endpoint = url.startsWith('http') ? url : `${API_BASE_URL}${url.startsWith('/') ? url : `/${url}`}`

    const response = await fetch(endpoint, {
      ...options,
      headers,
    })

    if (!response.ok) {
      let error: any = { message: response.statusText }
      
      try {
        error = await response.json()
      } catch {
        // If response is not JSON, use status text
        error = { message: response.statusText }
      }

      if (response.status === 401) {
        // Unauthorized - clear auth and redirect to login
        // Only redirect if not already on login/register pages
        if (process.client && !window.location.pathname.includes('/login') && !window.location.pathname.includes('/register')) {
          authStore.logout()
          await navigateTo('/login')
        }
      }

      // Extract error message from various response formats
      const errorMessage = error.message || 
                          error.error?.message || 
                          (error.errors && Object.values(error.errors).flat().join(', ')) ||
                          'An error occurred'
      
      throw new Error(errorMessage)
    }

    return response.json()
  }

  function get<T = any>(url: string, options?: RequestInit): Promise<T> {
    return request<T>(url, { ...options, method: 'GET' })
  }

  function post<T = any>(url: string, data?: any, options?: RequestInit): Promise<T> {
    return request<T>(url, {
      ...options,
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined,
    })
  }

  function put<T = any>(url: string, data?: any, options?: RequestInit): Promise<T> {
    return request<T>(url, {
      ...options,
      method: 'PUT',
      body: data ? JSON.stringify(data) : undefined,
    })
  }

  function del<T = any>(url: string, options?: RequestInit): Promise<T> {
    return request<T>(url, { ...options, method: 'DELETE' })
  }

  return {
    request,
    get,
    post,
    put,
    delete: del,
    API_BASE_URL,
    CUSTOMER_API_BASE_URL,
  }
}

// Backwards-compatible named exports for pages that import constants
export const API_BASE_URL = 'http://localhost:8000/api/v1'
export const CUSTOMER_API_BASE_URL = `${API_BASE_URL}/customer`

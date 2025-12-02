import { useAuthStore } from '~/stores/auth'

export const API_BASE_URL = 'http://127.0.0.1:8000/api/v1'

export const useApi = () => {
  const authStore = useAuthStore()

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

    const response = await fetch(`${API_BASE_URL}${url}`, {
      ...options,
      headers,
    })

    if (!response.ok) {
      const error = await response.json().catch(() => ({
        message: response.statusText,
      }))

      if (response.status === 401) {
        // Unauthorized - clear auth and redirect to login
        authStore.logout()
        await navigateTo('/login')
      }

      throw new Error(error.message || 'An error occurred')
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
  }
}


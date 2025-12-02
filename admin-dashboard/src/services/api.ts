import axios, { AxiosRequestConfig, AxiosResponse } from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api/v1'

// Admin API base URL (for admin routes)
export const ADMIN_API_BASE_URL = `${API_BASE_URL}/admin`

// Request deduplication: track in-flight requests
const pendingRequests = new Map<string, Promise<AxiosResponse>>()

// Response cache: cache GET requests for a short time
const responseCache = new Map<string, { data: AxiosResponse; timestamp: number }>()
const CACHE_TTL = 5000 // 5 seconds cache

// Generate cache key from request config
function getCacheKey(config: AxiosRequestConfig): string {
  const url = config.url || ''
  const params = config.params ? JSON.stringify(config.params) : ''
  const method = (config.method || 'get').toUpperCase()
  return `${method}:${url}:${params}`
}

// Clear cache for related endpoints after mutations
function clearRelatedCache(method: string, url: string) {
  if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method.toUpperCase())) {
    // Clear cache for related GET endpoints
    const urlParts = url.split('/')
    const resource = urlParts[urlParts.length - 1]?.split('?')[0]
    
    responseCache.forEach((_, key) => {
      if (key.includes(resource) || key.includes(url)) {
        responseCache.delete(key)
      }
    })
  }
}

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  withCredentials: true // Required for Sanctum SPA authentication
})

// Request interceptor: add auth token and handle caching/deduplication
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('admin_token')
    if (token) {
      config.headers = config.headers || {}
      config.headers['Authorization'] = `Bearer ${token}`
    }

    // Only deduplicate and cache GET requests
    if (config.method?.toLowerCase() === 'get') {
      const cacheKey = getCacheKey(config)
      
      // Check cache first
      const cached = responseCache.get(cacheKey)
      if (cached && Date.now() - cached.timestamp < CACHE_TTL) {
        // Return cached response by creating a resolved promise
        return Promise.resolve(cached.data) as any
      }

      // Check if request is already in-flight
      if (pendingRequests.has(cacheKey)) {
        // Return the existing promise
        return pendingRequests.get(cacheKey) as any
      }

      // Create new request and track it
      const requestPromise = axios(config)
        .then((response) => {
          // Cache successful GET responses
          responseCache.set(cacheKey, {
            data: response,
            timestamp: Date.now()
          })
          pendingRequests.delete(cacheKey)
          return response
        })
        .catch((error) => {
          pendingRequests.delete(cacheKey)
          throw error
        })

      pendingRequests.set(cacheKey, requestPromise)
      return requestPromise as any
    } else {
      // For non-GET requests, clear related cache
      clearRelatedCache(config.method || 'get', config.url || '')
    }

    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Handle 401 errors
    if (error.response?.status === 401) {
      localStorage.removeItem('admin_token')
      localStorage.removeItem('admin_user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api

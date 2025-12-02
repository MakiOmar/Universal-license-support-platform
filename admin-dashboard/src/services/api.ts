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
function getCacheKey(method: string, url: string, params?: any): string {
  const paramsStr = params ? JSON.stringify(params) : ''
  return `${method.toUpperCase()}:${url}:${paramsStr}`
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

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('admin_token')
    if (token) {
      config.headers = config.headers || {}
      config.headers['Authorization'] = `Bearer ${token}`
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
    if (error.response?.status === 401) {
      localStorage.removeItem('admin_token')
      localStorage.removeItem('admin_user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// Wrapper functions with deduplication and caching
const originalGet = api.get.bind(api)
api.get = function(url: string, config?: AxiosRequestConfig) {
  const method = 'get'
  const params = config?.params
  const cacheKey = getCacheKey(method, url, params)
  
  // Check cache first
  const cached = responseCache.get(cacheKey)
  if (cached && Date.now() - cached.timestamp < CACHE_TTL) {
    return Promise.resolve(cached.data)
  }

  // Check if request is already in-flight
  if (pendingRequests.has(cacheKey)) {
    return pendingRequests.get(cacheKey)!
  }

  // Create new request
  const requestPromise = originalGet(url, config)
    .then((response) => {
      // Cache successful responses
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
  return requestPromise
}

// Wrap POST/PUT/PATCH/DELETE to clear cache
const originalPost = api.post.bind(api)
const originalPut = api.put.bind(api)
const originalPatch = api.patch.bind(api)
const originalDelete = api.delete.bind(api)

api.post = function(url: string, data?: any, config?: AxiosRequestConfig) {
  clearRelatedCache('POST', url)
  return originalPost(url, data, config)
}

api.put = function(url: string, data?: any, config?: AxiosRequestConfig) {
  clearRelatedCache('PUT', url)
  return originalPut(url, data, config)
}

api.patch = function(url: string, data?: any, config?: AxiosRequestConfig) {
  clearRelatedCache('PATCH', url)
  return originalPatch(url, data, config)
}

api.delete = function(url: string, config?: AxiosRequestConfig) {
  clearRelatedCache('DELETE', url)
  return originalDelete(url, config)
}

export default api

export default defineNuxtRouteMiddleware((to, from) => {
  const authStore = useAuthStore()

  // Load auth from storage if not already loaded
  if (!authStore.isAuthenticated) {
    authStore.loadFromStorage()
  }

  // Public routes that don't require authentication
  const publicRoutes = ['/login', '/register', '/forgot-password', '/products']
  const isPublicRoute = publicRoutes.some(route => to.path === route || to.path.startsWith(route + '/'))

  // If not authenticated and trying to access protected route
  if (!authStore.isAuthenticated && !isPublicRoute) {
    return navigateTo('/login')
  }

  // If authenticated and trying to access auth pages, redirect to dashboard
  if (authStore.isAuthenticated && (to.path === '/login' || to.path === '/register')) {
    return navigateTo('/')
  }
})


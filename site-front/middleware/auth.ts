export default defineNuxtRouteMiddleware((to, from) => {
  const authStore = useAuthStore()

  // Load auth from storage if not already loaded
  if (!authStore.isAuthenticated) {
    authStore.loadFromStorage()
  }

  // If not authenticated and trying to access protected route
  if (!authStore.isAuthenticated && to.path !== '/login' && to.path !== '/register' && to.path !== '/forgot-password') {
    return navigateTo('/login')
  }

  // If authenticated and trying to access auth pages, redirect to dashboard
  if (authStore.isAuthenticated && (to.path === '/login' || to.path === '/register')) {
    return navigateTo('/')
  }
})


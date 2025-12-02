<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <NuxtLink to="/" class="text-xl font-bold text-indigo-600">
                ULSP Portal
              </NuxtLink>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <NuxtLink
                v-if="authStore.isAuthenticated"
                to="/"
                class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                active-class="border-indigo-500 text-gray-900"
                inactive-class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700"
              >
                Dashboard
              </NuxtLink>
              <NuxtLink
                to="/products"
                class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                active-class="border-indigo-500 text-gray-900"
                inactive-class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700"
              >
                Products
              </NuxtLink>
              <NuxtLink
                v-if="authStore.isAuthenticated"
                to="/licenses"
                class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                active-class="border-indigo-500 text-gray-900"
                inactive-class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700"
              >
                My Licenses
              </NuxtLink>
              <NuxtLink
                v-if="authStore.isAuthenticated"
                to="/tickets"
                class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                active-class="border-indigo-500 text-gray-900"
                inactive-class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700"
              >
                Support Tickets
              </NuxtLink>
            </div>
          </div>
          <div class="hidden sm:ml-6 sm:flex sm:items-center">
            <div class="ml-3 relative">
              <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-700">{{ authStore.fullName || authStore.customer?.email }}</span>
                <NuxtLink
                  to="/profile"
                  class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium"
                >
                  Profile
                </NuxtLink>
                <button
                  @click="handleLogout"
                  class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium"
                >
                  Logout
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Page Content -->
    <main>
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
const authStore = useAuthStore()
const { toastSuccess } = useAlerts()

// Load auth from storage on mount
onMounted(() => {
  authStore.loadFromStorage()
})

async function handleLogout() {
  authStore.logout()
  toastSuccess('Logged out successfully')
  await navigateTo('/login')
}
</script>


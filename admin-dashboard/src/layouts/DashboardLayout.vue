<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-gray-900 text-white">
      <div class="flex items-center justify-center h-16 bg-gray-800">
        <h1 class="text-xl font-bold">ULSP Admin</h1>
      </div>
      <nav class="mt-8">
        <router-link
          v-for="item in navigation"
          :key="item.name"
          :to="item.to"
          class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white"
          :class="{ 'bg-gray-800 text-white': $route.name === item.name }"
        >
          <component :is="item.icon" class="w-5 h-5 mr-3" />
          {{ item.label }}
        </router-link>
      </nav>
      <div class="absolute bottom-0 w-full p-4">
        <button
          @click="handleLogout"
          class="w-full flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 rounded"
        >
          Logout
        </button>
      </div>
    </div>

    <!-- Main content -->
    <div class="ml-64">
      <header class="bg-white shadow">
        <div class="px-8 py-4 flex justify-between items-center">
          <h2 class="text-2xl font-semibold text-gray-800">{{ currentPageTitle }}</h2>
          <div v-if="authStore.user" class="text-sm text-gray-600">
            Logged in as: <span class="font-medium">{{ authStore.user.name }}</span>
          </div>
        </div>
      </header>
      <main class="p-8">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const navigation = [
  { name: 'Dashboard', to: '/', label: 'Dashboard', icon: 'div' },
  { name: 'Licenses', to: '/licenses', label: 'Licenses', icon: 'div' },
  { name: 'Tickets', to: '/tickets', label: 'Tickets', icon: 'div' },
  { name: 'Customers', to: '/customers', label: 'Customers', icon: 'div' },
  { name: 'Products', to: '/products', label: 'Products', icon: 'div' },
  { name: 'ApiKeys', to: '/api-keys', label: 'API Keys', icon: 'div' }
]

const currentPageTitle = computed(() => {
  const item = navigation.find(n => n.name === route.name)
  return item?.label || 'Dashboard'
})

onMounted(() => {
  if (authStore.isAuthenticated && !authStore.user) {
    authStore.fetchUser()
  }
})

function handleLogout() {
  authStore.logout()
  router.push({ name: 'Login' })
}
</script>


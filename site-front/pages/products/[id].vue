<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
      <NuxtLink to="/products" class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">
        ← Back to Products
      </NuxtLink>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else-if="product" class="space-y-6">
      <!-- Product Details -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200">
          <h1 class="text-3xl font-bold text-gray-900">{{ product.name }}</h1>
          <p class="mt-2 text-sm text-gray-500">{{ product.type }}</p>
        </div>
        <div class="px-6 py-5">
          <div class="grid grid-cols-2 gap-4 mb-6">
            <div>
              <span class="text-sm text-gray-500">Version:</span>
              <span class="ml-2 text-sm text-gray-900">{{ product.version || 'N/A' }}</span>
            </div>
            <div>
              <span class="text-sm text-gray-500">Status:</span>
              <span
                :class="{
                  'bg-green-100 text-green-800': product.status === 'active',
                  'bg-gray-100 text-gray-800': product.status !== 'active'
                }"
                class="ml-2 px-2 py-1 text-xs font-semibold rounded-full"
              >
                {{ product.status }}
              </span>
            </div>
          </div>
          <div v-if="product.description">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Description</h3>
            <p class="text-gray-700 whitespace-pre-wrap">{{ product.description }}</p>
          </div>
        </div>
      </div>

      <!-- Purchase Section -->
      <div v-if="authStore.isAuthenticated" class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">Get License</h2>
        </div>
        <div class="px-6 py-5">
          <p class="text-sm text-gray-600 mb-4">
            To purchase a license for this product, please contact support through a ticket.
          </p>
          <div class="flex gap-3">
            <NuxtLink
              to="/tickets"
              class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium"
            >
              Contact Support
            </NuxtLink>
            <NuxtLink
              to="/licenses"
              class="inline-block px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 text-sm font-medium"
            >
              View My Licenses
            </NuxtLink>
          </div>
        </div>
      </div>

      <div v-else class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <p class="text-sm text-yellow-800">
          Please <NuxtLink to="/login" class="font-medium underline">log in</NuxtLink> or 
          <NuxtLink to="/register" class="font-medium underline">create an account</NuxtLink> to purchase a license.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const api = useApi()
const authStore = useAuthStore()
const { API_BASE_URL } = api

const loading = ref(true)
const product = ref<any>(null)

onMounted(async () => {
  await fetchProduct()
})

async function fetchProduct() {
  loading.value = true
  try {
    const response = await api.get(`${API_BASE_URL}/products/${route.params.id}`)
    product.value = response.data || response
  } catch (error) {
    console.error('Failed to fetch product:', error)
  } finally {
    loading.value = false
  }
}
</script>


<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Products</h1>
      <p class="mt-2 text-sm text-gray-600">Browse available software products</p>
    </div>

    <div class="mb-6">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search products..."
        class="w-full max-w-md px-4 py-2 border rounded-md"
        @input="debouncedSearch"
      />
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else-if="products.length === 0" class="bg-white shadow rounded-lg p-8 text-center">
      <p class="text-gray-500">No products found.</p>
    </div>

    <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="product in products"
        :key="product.id"
        class="bg-white shadow rounded-lg overflow-hidden hover:shadow-md transition-shadow"
      >
        <div class="p-6">
          <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ product.name }}</h3>
          <p class="text-sm text-gray-500 mb-4">{{ product.type }}</p>
          <p class="text-sm text-gray-700 mb-4 line-clamp-3">{{ product.description || 'No description available.' }}</p>
          <div class="flex items-center justify-between">
            <span v-if="product.version" class="text-xs text-gray-500">v{{ product.version }}</span>
            <NuxtLink
              :to="`/products/${product.id}`"
              class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium"
            >
              View Details
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const api = useApi()
const { API_BASE_URL } = api

const loading = ref(true)
const products = ref<any[]>([])
const searchQuery = ref('')
let searchTimeout: NodeJS.Timeout | null = null

onMounted(async () => {
  await fetchProducts()
})

function debouncedSearch() {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  searchTimeout = setTimeout(() => {
    fetchProducts()
  }, 300)
}

async function fetchProducts() {
  loading.value = true
  try {
    const params: any = {}
    if (searchQuery.value) {
      params.search = searchQuery.value
    }
    
    const queryString = new URLSearchParams(params).toString()
    const url = `${API_BASE_URL}/products${queryString ? `?${queryString}` : ''}`
    
    const response = await api.get<{ data: any[] }>(url)
    // Handle paginated response
    if (response.data && Array.isArray(response.data)) {
      products.value = response.data
    } else if (Array.isArray(response)) {
      products.value = response
    } else {
      products.value = []
    }
  } catch (error) {
    console.error('Failed to fetch products:', error)
    products.value = []
  } finally {
    loading.value = false
  }
}
</script>


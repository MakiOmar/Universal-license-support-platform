<template>
  <div>
    <div class="mb-4 flex justify-between items-center">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search products..."
        class="px-4 py-2 border rounded-md w-full max-w-md"
      />
      <button
        @click="showCreateModal = true"
        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
      >
        Create Product
      </button>
    </div>

    <!-- Create Product Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click.self="showCreateModal = false"
    >
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Product</h3>
          <form @submit.prevent="handleCreate">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full px-3 py-2 border rounded-md"
                placeholder="Product Name"
              />
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">Slug *</label>
              <input
                v-model="form.slug"
                type="text"
                required
                class="w-full px-3 py-2 border rounded-md font-mono"
                placeholder="product-slug"
              />
              <p class="text-xs text-gray-500 mt-1">URL-friendly identifier (e.g., my-product)</p>
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea
                v-model="form.description"
                rows="3"
                class="w-full px-3 py-2 border rounded-md"
                placeholder="Product description"
              ></textarea>
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
              <select v-model="form.type" required class="w-full px-3 py-2 border rounded-md">
                <option value="">Select type</option>
                <option value="wordpress_plugin">WordPress Plugin</option>
                <option value="web_app">Web App</option>
                <option value="desktop_app">Desktop App</option>
                <option value="mobile_app">Mobile App</option>
                <option value="api_service">API Service</option>
                <option value="saas_product">SaaS Product</option>
              </select>
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">Version</label>
              <input
                v-model="form.version"
                type="text"
                class="w-full px-3 py-2 border rounded-md"
                placeholder="1.0.0"
              />
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select v-model="form.status" class="w-full px-3 py-2 border rounded-md">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="archived">Archived</option>
              </select>
            </div>
            <div v-if="error" class="mb-4 text-red-600 text-sm">{{ error }}</div>
            <div class="flex justify-end gap-2">
              <button
                type="button"
                @click="showCreateModal = false"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="creating"
                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
              >
                <span v-if="creating">Creating...</span>
                <span v-else>Create Product</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Version</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="product in filteredProducts" :key="product.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ product.name }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono">{{ product.slug }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ product.type }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ product.version || 'N/A' }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getStatusClass(product.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                {{ product.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ formatDate(product.created_at) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <button
                @click="handleDelete(product)"
                class="text-red-600 hover:text-red-900"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api, { ADMIN_API_BASE_URL } from '../services/api'

const products = ref([])
const loading = ref(false)
const searchQuery = ref('')
const showCreateModal = ref(false)
const creating = ref(false)
const error = ref('')

const form = ref({
  name: '',
  slug: '',
  description: '',
  type: '',
  version: '',
  status: 'active'
})

const filteredProducts = computed(() => {
  if (!searchQuery.value) return products.value
  const query = searchQuery.value.toLowerCase()
  return products.value.filter((p: any) =>
    p.name?.toLowerCase().includes(query) ||
    p.slug?.toLowerCase().includes(query) ||
    p.type?.toLowerCase().includes(query)
  )
})

function getStatusClass(status: string) {
  const classes: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    archived: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function formatDate(date: string | null) {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

async function fetchProducts() {
  loading.value = true
  try {
    const response = await api.get(`${ADMIN_API_BASE_URL}/products`, { params: { per_page: 100 } })
    products.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch products:', error)
  } finally {
    loading.value = false
  }
}

async function handleCreate() {
  error.value = ''
  creating.value = true
  
  try {
    const response = await api.post(`${ADMIN_API_BASE_URL}/products`, form.value)
    
    // Add the new product to the list
    products.value.unshift(response.data.data || response.data)
    
    // Reset form and close modal
    form.value = {
      name: '',
      slug: '',
      description: '',
      type: '',
      version: '',
      status: 'active'
    }
    showCreateModal.value = false
  } catch (err: any) {
    if (err.response?.data?.message) {
      error.value = err.response.data.message
    } else if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      error.value = Object.values(errors).flat().join(', ')
    } else {
      error.value = 'Failed to create product. Please try again.'
    }
  } finally {
    creating.value = false
  }
}

async function handleDelete(product: any) {
  if (!confirm(`Are you sure you want to delete "${product.name}"?`)) {
    return
  }
  
  try {
    await api.delete(`${ADMIN_API_BASE_URL}/products/${product.id}`)
    products.value = products.value.filter((p: any) => p.id !== product.id)
  } catch (err: any) {
    alert('Failed to delete product. Please try again.')
  }
}

onMounted(() => {
  fetchProducts()
})
</script>


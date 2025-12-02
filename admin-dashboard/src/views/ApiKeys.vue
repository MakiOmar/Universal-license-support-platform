<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">API Keys</h1>
        <p class="mt-2 text-sm text-gray-600">Manage API keys for external integrations</p>
      </div>
      <button
        @click="showCreateModal = true"
        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
      >
        Create API Key
      </button>
    </div>

    <!-- Search and Filters -->
    <div class="mb-6 flex gap-4">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search API keys..."
        class="flex-1 px-4 py-2 border rounded-md"
        @input="debouncedSearch"
      />
      <select v-model="statusFilter" @change="fetchApiKeys" class="px-4 py-2 border rounded-md">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <!-- API Keys Table -->
    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">API Key</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate Limit</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Used</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <template v-for="apiKey in apiKeys" :key="apiKey?.id">
            <tr v-if="apiKey">
            <td class="px-6 py-4 whitespace-nowrap">
              <code class="text-sm text-gray-900 font-mono">{{ truncateKey(apiKey.api_key) }}</code>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ apiKey.customer?.email || 'N/A' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ apiKey.product?.name || 'N/A' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ apiKey.rate_limit }}/hour
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="{
                  'bg-green-100 text-green-800': apiKey.status === 'active',
                  'bg-gray-100 text-gray-800': apiKey.status === 'inactive'
                }"
                class="px-2 py-1 text-xs font-semibold rounded-full"
              >
                {{ apiKey.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(apiKey.last_used_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(apiKey.expires_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                @click="editApiKey(apiKey)"
                class="text-indigo-600 hover:text-indigo-900 mr-4"
              >
                Edit
              </button>
              <button
                @click="regenerateSecret(apiKey)"
                class="text-yellow-600 hover:text-yellow-900 mr-4"
              >
                Regenerate Secret
              </button>
              <button
                @click="deleteApiKey(apiKey)"
                class="text-red-600 hover:text-red-900"
              >
                Delete
              </button>
            </td>
          </tr>
          </template>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
        </div>
        <div class="flex gap-2">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="!pagination.prev_page_url"
            class="px-4 py-2 border rounded-md disabled:opacity-50"
          >
            Previous
          </button>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="!pagination.next_page_url"
            class="px-4 py-2 border rounded-md disabled:opacity-50"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div
      v-if="showCreateModal || editingApiKey"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click.self="closeModal"
    >
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-bold mb-4">
          {{ editingApiKey ? 'Edit API Key' : 'Create API Key' }}
        </h3>
        <form @submit.prevent="saveApiKey">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Customer</label>
            <select
              v-model="formData.customer_id"
              required
              class="w-full px-3 py-2 border rounded-md"
            >
              <option value="">Select Customer</option>
              <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                {{ customer.email }} ({{ customer.first_name }} {{ customer.last_name }})
              </option>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Product</label>
            <select
              v-model="formData.product_id"
              required
              class="w-full px-3 py-2 border rounded-md"
            >
              <option value="">Select Product</option>
              <option v-for="product in products" :key="product.id" :value="product.id">
                {{ product.name }}
              </option>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Rate Limit (per hour)</label>
            <input
              v-model.number="formData.rate_limit"
              type="number"
              min="1"
              max="10000"
              class="w-full px-3 py-2 border rounded-md"
            />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Expires At (optional)</label>
            <input
              v-model="formData.expires_at"
              type="datetime-local"
              class="w-full px-3 py-2 border rounded-md"
            />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select v-model="formData.status" class="w-full px-3 py-2 border rounded-md">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="flex justify-end gap-2">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 border rounded-md"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
            >
              {{ editingApiKey ? 'Update' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Secret Display Modal -->
    <div
      v-if="showSecretModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click.self="showSecretModal = false"
    >
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-bold mb-4">API Secret</h3>
        <p class="text-sm text-gray-600 mb-4">
          <strong>Important:</strong> This secret will only be shown once. Please save it securely.
        </p>
        <div class="bg-gray-100 p-4 rounded-md mb-4">
          <code class="text-sm break-all">{{ displayedSecret }}</code>
        </div>
        <button
          @click="showSecretModal = false"
          class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
        >
          I've Saved It
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api, { ADMIN_API_BASE_URL } from '../services/api'
import { useAlerts } from '../utils/alerts'

const router = useRouter()

const loading = ref(true)
const apiKeys = ref<any[]>([])
const customers = ref<any[]>([])
const products = ref<any[]>([])
const searchQuery = ref('')
const statusFilter = ref('')
const pagination = ref<any>(null)
const showCreateModal = ref(false)
const editingApiKey = ref<any>(null)
const showSecretModal = ref(false)
const displayedSecret = ref('')

const formData = ref({
  customer_id: '',
  product_id: '',
  rate_limit: 1000,
  expires_at: '',
  status: 'active'
})

let searchTimeout: NodeJS.Timeout | null = null

const { toastSuccess, toastError, confirmAction } = useAlerts()

onMounted(async () => {
  await Promise.all([fetchApiKeys(), fetchCustomers(), fetchProducts()])
})

async function fetchApiKeys() {
  loading.value = true
  try {
    const params: any = { per_page: 25 }
    if (searchQuery.value) params.search = searchQuery.value
    if (statusFilter.value) params.status = statusFilter.value

    const response = await api.get(`${ADMIN_API_BASE_URL}/api-keys`, { params })
    
    // Handle paginated response
    if (response.data && Array.isArray(response.data)) {
      apiKeys.value = response.data
      pagination.value = null
    } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
      apiKeys.value = response.data.data || []
      pagination.value = {
        current_page: response.data.current_page || 1,
        from: response.data.from || 0,
        to: response.data.to || 0,
        total: response.data.total || 0,
        prev_page_url: response.data.prev_page_url,
        next_page_url: response.data.next_page_url
      }
    } else {
      apiKeys.value = []
      pagination.value = null
    }
  } catch (error: any) {
    console.error('Failed to fetch API keys:', error)
    toastError(error.response?.data?.message || 'Failed to fetch API keys')
    apiKeys.value = []
  } finally {
    loading.value = false
  }
}

async function fetchCustomers() {
  try {
    const response = await api.get(`${ADMIN_API_BASE_URL}/customers?per_page=1000`)
    customers.value = response.data || []
  } catch (error) {
    console.error('Failed to fetch customers:', error)
  }
}

async function fetchProducts() {
  try {
    const response = await api.get(`${ADMIN_API_BASE_URL}/products`, { params: { per_page: 1000 } })
    products.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Failed to fetch products:', error)
    products.value = []
  }
}

function debouncedSearch() {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  searchTimeout = setTimeout(() => {
    fetchApiKeys()
  }, 300)
}

function changePage(page: number) {
  // Implementation would need to update the API call with page parameter
  fetchApiKeys()
}

function truncateKey(key: string) {
  if (!key) return 'N/A'
  return key.substring(0, 16) + '...' + key.substring(key.length - 8)
}

function formatDate(date: string | null) {
  if (!date) return 'Never'
  return new Date(date).toLocaleDateString()
}

function editApiKey(apiKey: any) {
  editingApiKey.value = apiKey
  formData.value = {
    customer_id: apiKey.customer_id.toString(),
    product_id: apiKey.product_id.toString(),
    rate_limit: apiKey.rate_limit,
    expires_at: apiKey.expires_at ? new Date(apiKey.expires_at).toISOString().slice(0, 16) : '',
    status: apiKey.status
  }
  showCreateModal.value = true
}

function closeModal() {
  showCreateModal.value = false
  editingApiKey.value = null
  formData.value = {
    customer_id: '',
    product_id: '',
    rate_limit: 1000,
    expires_at: '',
    status: 'active'
  }
}

async function saveApiKey() {
  try {
    const payload = {
      ...formData.value,
      customer_id: parseInt(formData.value.customer_id),
      product_id: parseInt(formData.value.product_id),
      rate_limit: parseInt(formData.value.rate_limit.toString())
    }

    if (editingApiKey.value) {
      await api.put(`${ADMIN_API_BASE_URL}/api-keys/${editingApiKey.value.id}`, payload)
      toastSuccess('API key updated successfully')
    } else {
      const response = await api.post(`${ADMIN_API_BASE_URL}/api-keys`, payload)
      if (response.data?.api_secret) {
        displayedSecret.value = response.data.api_secret
        showSecretModal.value = true
      }
      toastSuccess('API key created successfully')
    }
    
    closeModal()
    await fetchApiKeys()
  } catch (error: any) {
    console.error('Failed to save API key:', error)
    toastError(error.response?.data?.message || 'Failed to save API key')
  }
}

async function regenerateSecret(apiKey: any) {
  const confirmed = await confirmAction(
    'Regenerate Secret',
    'Are you sure you want to regenerate the API secret? The old secret will no longer work.',
    'warning'
  )
  
  if (!confirmed) return

  try {
    const response = await api.post(`${ADMIN_API_BASE_URL}/api-keys/${apiKey.id}/regenerate-secret`)
    if (response.data?.api_secret) {
      displayedSecret.value = response.data.api_secret
      showSecretModal.value = true
    }
    toastSuccess('API secret regenerated successfully')
    await fetchApiKeys()
  } catch (error: any) {
    console.error('Failed to regenerate secret:', error)
    toastError(error.response?.data?.message || 'Failed to regenerate secret')
  }
}

async function deleteApiKey(apiKey: any) {
  const confirmed = await confirmAction(
    'Delete API Key',
    `Are you sure you want to delete this API key? This action cannot be undone.`,
    'warning'
  )
  
  if (!confirmed) return

  try {
    await api.delete(`${ADMIN_API_BASE_URL}/api-keys/${apiKey.id}`)
    toastSuccess('API key deleted successfully')
    await fetchApiKeys()
  } catch (error: any) {
    console.error('Failed to delete API key:', error)
    toastError(error.response?.data?.message || 'Failed to delete API key')
  }
}
</script>


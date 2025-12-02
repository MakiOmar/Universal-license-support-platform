<template>
  <div>
    <div class="mb-4 flex justify-between items-center">
      <div class="flex gap-4">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search licenses..."
          class="px-4 py-2 border rounded-md"
        />
        <select v-model="statusFilter" class="px-4 py-2 border rounded-md">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="pending">Pending</option>
          <option value="expired">Expired</option>
          <option value="suspended">Suspended</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
      >
        Create License
      </button>
    </div>

    <!-- Bulk Actions Toolbar -->
    <div v-if="selectedLicenses.length > 0" class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4 flex justify-between items-center">
      <div class="text-sm text-blue-800">
        <strong>{{ selectedLicenses.length }}</strong> license(s) selected
      </div>
      <div class="flex gap-2">
        <button
          @click="openBulkStatusModal"
          class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
        >
          Update Status
        </button>
        <button
          @click="openBulkTransferModal"
          class="px-3 py-1.5 bg-purple-600 text-white text-sm rounded-md hover:bg-purple-700"
        >
          Transfer
        </button>
        <button
          @click="openBulkRenewModal"
          class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-md hover:bg-green-700"
        >
          Renew
        </button>
        <button
          @click="handleBulkDelete"
          class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-md hover:bg-red-700"
        >
          Delete
        </button>
        <button
          @click="clearSelection"
          class="px-3 py-1.5 bg-gray-400 text-white text-sm rounded-md hover:bg-gray-500"
        >
          Clear Selection
        </button>
      </div>
    </div>

    <!-- Create / Edit License Modal -->
    <div
      v-if="showFormModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click.self="closeFormModal"
    >
      <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ editingLicense ? 'Edit License' : 'Create License' }}
          </h3>

          <form @submit.prevent="handleFormSubmit" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
                <select
                  v-model.number="form.product_id"
                  required
                  class="w-full px-3 py-2 border rounded-md"
                >
                  <option value="">Select product</option>
                  <option
                    v-for="product in products"
                    :key="product.id"
                    :value="product.id"
                  >
                    {{ product.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Customer *</label>
                <select
                  v-model.number="form.customer_id"
                  required
                  class="w-full px-3 py-2 border rounded-md"
                >
                  <option value="">Select customer</option>
                  <option
                    v-for="customer in customers"
                    :key="customer.id"
                    :value="customer.id"
                  >
                    {{ customer.email }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">License Type *</label>
                <select v-model="form.license_type" required class="w-full px-3 py-2 border rounded-md">
                  <option value="">Select type</option>
                  <option value="domain">Domain-based</option>
                  <option value="machine_id">Machine ID</option>
                  <option value="device_id">Device ID</option>
                  <option value="api_key">API Key</option>
                  <option value="subscription">Subscription</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max Activations</label>
                <input
                  v-model.number="form.max_activations"
                  type="number"
                  min="1"
                  max="100"
                  class="w-full px-3 py-2 border rounded-md"
                  placeholder="1"
                />
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">License Key</label>
                <input
                  v-model="form.license_key"
                  type="text"
                  class="w-full px-3 py-2 border rounded-md font-mono"
                  placeholder="Leave empty to auto-generate"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select v-model="form.status" class="w-full px-3 py-2 border rounded-md">
                  <option value="pending">Pending</option>
                  <option value="active">Active</option>
                  <option value="expired">Expired</option>
                  <option value="suspended">Suspended</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Purchased At</label>
                <input
                  v-model="form.purchased_at"
                  type="datetime-local"
                  class="w-full px-3 py-2 border rounded-md"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">License Expiration Period</label>
                <div class="flex gap-2">
                  <input
                    v-model.number="form.expires_period_value"
                    type="number"
                    min="1"
                    placeholder="Number"
                    class="w-24 px-3 py-2 border rounded-md"
                  />
                  <select
                    v-model="form.expires_period_unit"
                    class="flex-1 px-3 py-2 border rounded-md"
                  >
                    <option value="">Select unit</option>
                    <option value="days">Days</option>
                    <option value="months">Months</option>
                    <option value="years">Years</option>
                  </select>
                </div>
                <p class="text-xs text-gray-500 mt-1">Calculated from purchased date</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Support Expiration Period</label>
                <div class="flex gap-2">
                  <input
                    v-model.number="form.support_expires_period_value"
                    type="number"
                    min="1"
                    placeholder="Number"
                    class="w-24 px-3 py-2 border rounded-md"
                  />
                  <select
                    v-model="form.support_expires_period_unit"
                    class="flex-1 px-3 py-2 border rounded-md"
                  >
                    <option value="">Select unit</option>
                    <option value="days">Days</option>
                    <option value="months">Months</option>
                    <option value="years">Years</option>
                  </select>
                </div>
                <p class="text-xs text-gray-500 mt-1">Calculated from purchased date</p>
              </div>
            </div>

            <div v-if="formError" class="text-sm text-red-600">
              {{ formError }}
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button
                type="button"
                @click="closeFormModal"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="formSaving"
                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
              >
                <span v-if="formSaving">{{ editingLicense ? 'Saving...' : 'Creating...' }}</span>
                <span v-else>{{ editingLicense ? 'Save Changes' : 'Create License' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Transfer License Modal -->
    <div
      v-if="showTransferModal && transferLicense"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click.self="closeTransferModal"
    >
      <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Transfer License</h3>
          <p class="text-sm text-gray-600 mb-3">
            License: <span class="font-mono">{{ transferLicense.license_key }}</span>
          </p>
          <form @submit.prevent="handleTransferSubmit" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">New Customer *</label>
              <select
                v-model.number="transferCustomerId"
                required
                class="w-full px-3 py-2 border rounded-md"
              >
                <option value="">Select customer</option>
                <option
                  v-for="customer in customers"
                  :key="customer.id"
                  :value="customer.id"
                >
                  {{ customer.email }}
                </option>
              </select>
            </div>

            <div v-if="transferError" class="text-sm text-red-600">
              {{ transferError }}
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button
                type="button"
                @click="closeTransferModal"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="transferSaving"
                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
              >
                <span v-if="transferSaving">Transferring...</span>
                <span v-else>Transfer License</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else-if="filteredLicenses.length === 0" class="bg-white shadow rounded-lg overflow-hidden p-8 text-center">
      <p class="text-gray-500">
        <span v-if="searchQuery || statusFilter">No licenses match your filters.</span>
        <span v-else>No licenses found. Create your first license to get started.</span>
      </p>
    </div>

    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              <input
                type="checkbox"
                :checked="selectedLicenses.length === filteredLicenses.length && filteredLicenses.length > 0"
                @change="toggleSelectAll"
                class="rounded border-gray-300"
              />
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">License Key</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="license in filteredLicenses" :key="license.id">
            <td class="px-6 py-4 whitespace-nowrap">
              <input
                type="checkbox"
                :checked="isSelected(license.id)"
                @change="toggleSelection(license.id)"
                class="rounded border-gray-300"
              />
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono">{{ license.license_key }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ license.product?.name || 'N/A' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ license.customer?.email || 'N/A' }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getStatusClass(license.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                {{ license.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ formatDate(license.expires_at) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-3">
              <router-link :to="`/licenses/${license.id}`" class="text-indigo-600 hover:text-indigo-900">View</router-link>
              <button
                type="button"
                @click="openEditModal(license)"
                class="text-gray-700 hover:text-gray-900"
              >
                Edit
              </button>
              <button
                type="button"
                @click="openTransferModal(license)"
                class="text-blue-600 hover:text-blue-900"
              >
                Transfer
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import api, { ADMIN_API_BASE_URL } from '../services/api'
import { useAlerts } from '../utils/alerts'

const licenses = ref([])
const loading = ref(false)
const searchQuery = ref('')
const statusFilter = ref('')

const products = ref<any[]>([])
const customers = ref<any[]>([])

const showFormModal = ref(false)
const formSaving = ref(false)
const formError = ref('')
const editingLicense = ref<any | null>(null)

const form = ref({
  product_id: '' as number | '',
  customer_id: '' as number | '',
  license_type: '',
  max_activations: null as number | null,
  license_key: '',
  status: 'pending',
  purchased_at: '',
  expires_period_value: null as number | null,
  expires_period_unit: '' as string,
  support_expires_period_value: null as number | null,
  support_expires_period_unit: '' as string
})

const showTransferModal = ref(false)
const transferSaving = ref(false)
const transferError = ref('')
const transferLicense = ref<any | null>(null)
const transferCustomerId = ref<number | ''>('')

const { toastSuccess, toastError, confirmAction } = useAlerts()

// Bulk operations
const selectedLicenses = ref<number[]>([])
const showBulkStatusModal = ref(false)
const showBulkTransferModal = ref(false)
const showBulkRenewModal = ref(false)
const bulkStatus = ref('')
const bulkTransferCustomerId = ref<number | ''>('')
const bulkRenewForm = ref({
  period_value: 1,
  period_unit: 'year'
})
const bulkProcessing = ref(false)
const bulkError = ref('')

// Selection management
function isSelected(licenseId: number) {
  return selectedLicenses.value.includes(licenseId)
}

function toggleSelection(licenseId: number) {
  const index = selectedLicenses.value.indexOf(licenseId)
  if (index > -1) {
    selectedLicenses.value.splice(index, 1)
  } else {
    selectedLicenses.value.push(licenseId)
  }
}

function toggleSelectAll() {
  if (selectedLicenses.value.length === filteredLicenses.value.length) {
    selectedLicenses.value = []
  } else {
    selectedLicenses.value = filteredLicenses.value.map((l: any) => l.id)
  }
}

function clearSelection() {
  selectedLicenses.value = []
}

const filteredLicenses = computed(() => {
  let filtered = licenses.value

  if (statusFilter.value) {
    filtered = filtered.filter((l: any) => l.status === statusFilter.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter((l: any) =>
      l.license_key?.toLowerCase().includes(query) ||
      l.product?.name?.toLowerCase().includes(query) ||
      l.customer?.email?.toLowerCase().includes(query)
    )
  }

  return filtered
})

function getStatusClass(status: string) {
  const classes: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    expired: 'bg-red-100 text-red-800',
    suspended: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function formatDate(date: string | null) {
  if (!date) return 'Never'
  return new Date(date).toLocaleDateString()
}

async function fetchLicenses() {
  loading.value = true
  try {
    const response = await api.get(`${ADMIN_API_BASE_URL}/licenses`, { params: { per_page: 100 } })
    // Handle both paginated and non-paginated responses
    if (response.data && Array.isArray(response.data)) {
      licenses.value = response.data
    } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
      licenses.value = response.data.data
    } else {
      licenses.value = []
    }
  } catch (error: any) {
    console.error('Failed to fetch licenses:', error)
    licenses.value = []
    if (error.response?.status !== 401) {
      toastError('Failed to load licenses. Please refresh the page.')
    }
  } finally {
    loading.value = false
  }
}

async function fetchMetadata() {
  try {
    const [productsRes, customersRes] = await Promise.all([
      api.get(`${ADMIN_API_BASE_URL}/products`, { params: { per_page: 100 } }),
      api.get(`${ADMIN_API_BASE_URL}/customers`, { params: { per_page: 100 } })
    ])

    products.value = productsRes.data.data || productsRes.data
    customers.value = customersRes.data.data || customersRes.data
  } catch (error) {
    console.error('Failed to fetch license metadata:', error)
  }
}

function resetForm() {
  form.value = {
    product_id: '' as number | '',
    customer_id: '' as number | '',
    license_type: '',
    max_activations: null,
    license_key: '',
    status: 'pending',
    purchased_at: '',
    expires_period_value: null,
    expires_period_unit: '',
    support_expires_period_value: null,
    support_expires_period_unit: ''
  }
  formError.value = ''
  editingLicense.value = null
}

function openCreateModal() {
  resetForm()
  showFormModal.value = true
}

function calculatePeriodFromDates(purchasedDate: string | null, expiresDate: string | null): { value: number | null, unit: string } {
  if (!purchasedDate || !expiresDate) {
    return { value: null, unit: '' }
  }

  const purchased = new Date(purchasedDate)
  const expires = new Date(expiresDate)
  const diffMs = expires.getTime() - purchased.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))

  if (diffDays < 30) {
    return { value: diffDays, unit: 'days' }
  } else if (diffDays < 365) {
    const months = Math.round(diffDays / 30)
    return { value: months, unit: 'months' }
  } else {
    const years = Math.round(diffDays / 365)
    return { value: years, unit: 'years' }
  }
}

function openEditModal(license: any) {
  editingLicense.value = license
  const purchasedAt = license.purchased_at ? String(license.purchased_at).slice(0, 16) : ''
  
  // Calculate periods from existing dates
  const expiresPeriod = calculatePeriodFromDates(license.purchased_at, license.expires_at)
  const supportExpiresPeriod = calculatePeriodFromDates(license.purchased_at, license.support_expires_at)

  form.value = {
    product_id: license.product_id,
    customer_id: license.customer_id,
    license_type: license.license_type || '',
    max_activations: license.max_activations ?? null,
    license_key: license.license_key || '',
    status: license.status || 'pending',
    purchased_at: purchasedAt,
    expires_period_value: expiresPeriod.value,
    expires_period_unit: expiresPeriod.unit,
    support_expires_period_value: supportExpiresPeriod.value,
    support_expires_period_unit: supportExpiresPeriod.unit
  }
  formError.value = ''
  showFormModal.value = true
}

function closeFormModal() {
  if (formSaving.value) {
    return
  }
  showFormModal.value = false
}

async function handleFormSubmit() {
  formSaving.value = true
  formError.value = ''

  const payload: any = {
    product_id: form.value.product_id,
    customer_id: form.value.customer_id,
    license_type: form.value.license_type,
    status: form.value.status
  }

  if (form.value.max_activations) {
    payload.max_activations = form.value.max_activations
  }
  if (form.value.license_key) {
    payload.license_key = form.value.license_key
  }
  if (form.value.purchased_at) {
    payload.purchased_at = form.value.purchased_at
  }
  
  // Send period values if provided
  if (form.value.expires_period_value && form.value.expires_period_unit) {
    payload.expires_period_value = form.value.expires_period_value
    payload.expires_period_unit = form.value.expires_period_unit
  }
  
  if (form.value.support_expires_period_value && form.value.support_expires_period_unit) {
    payload.support_expires_period_value = form.value.support_expires_period_value
    payload.support_expires_period_unit = form.value.support_expires_period_unit
  }

  try {
    if (editingLicense.value) {
      const response = await api.put(
        `${ADMIN_API_BASE_URL}/licenses/${editingLicense.value.id}`,
        payload
      )
      const updated = response.data.data || response.data
      licenses.value = licenses.value.map((l: any) => (l.id === updated.id ? updated : l))
      toastSuccess('License updated successfully')
    } else {
      const response = await api.post(`${ADMIN_API_BASE_URL}/licenses`, payload)
      const created = response.data.data || response.data
      licenses.value.unshift(created)
      toastSuccess('License created successfully')
    }

    showFormModal.value = false
  } catch (err: any) {
    if (err.response?.data?.message) {
      formError.value = err.response.data.message
    } else if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      formError.value = Object.values(errors).flat().join(', ')
    } else {
      formError.value = 'Failed to save license. Please try again.'
    }
    toastError(formError.value || 'Failed to save license. Please try again.')
  } finally {
    formSaving.value = false
  }
}

function openTransferModal(license: any) {
  transferLicense.value = license
  transferCustomerId.value = '' as number | ''
  transferError.value = ''
  showTransferModal.value = true
}

function closeTransferModal() {
  if (transferSaving.value) {
    return
  }
  showTransferModal.value = false
}

async function handleTransferSubmit() {
  if (!transferLicense.value || !transferCustomerId.value) {
    return
  }

  transferSaving.value = true
  transferError.value = ''

  try {
    const response = await api.post(
      `${ADMIN_API_BASE_URL}/licenses/${transferLicense.value.id}/transfer`,
      { new_customer_id: transferCustomerId.value }
    )

    const updated = response.data.license?.data || response.data.license || null
    if (updated) {
      licenses.value = licenses.value.map((l: any) => (l.id === updated.id ? updated : l))
    }

    showTransferModal.value = false
    toastSuccess('License transferred successfully')
  } catch (err: any) {
    if (err.response?.data?.message) {
      transferError.value = err.response.data.message
    } else if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      transferError.value = Object.values(errors).flat().join(', ')
    } else {
      transferError.value = 'Failed to transfer license. Please try again.'
    }
    toastError(transferError.value || 'Failed to transfer license. Please try again.')
  } finally {
    transferSaving.value = false
  }
}

// Bulk operation handlers
function openBulkStatusModal() {
  bulkStatus.value = ''
  bulkError.value = ''
  showBulkStatusModal.value = true
}

function openBulkTransferModal() {
  bulkTransferCustomerId.value = ''
  bulkError.value = ''
  showBulkTransferModal.value = true
}

function openBulkRenewModal() {
  bulkRenewForm.value = { period_value: 1, period_unit: 'year' }
  bulkError.value = ''
  showBulkRenewModal.value = true
}

async function handleBulkStatusUpdate() {
  if (!bulkStatus.value) {
    bulkError.value = 'Please select a status'
    return
  }

  bulkProcessing.value = true
  bulkError.value = ''

  try {
    const response = await api.post(`${ADMIN_API_BASE_URL}/licenses/bulk`, {
      license_ids: selectedLicenses.value,
      action: 'update_status',
      status: bulkStatus.value
    })

    toastSuccess(response.data.message || 'Status updated successfully')
    selectedLicenses.value = []
    showBulkStatusModal.value = false
    await fetchLicenses()
  } catch (err: any) {
    if (err.response?.data?.message) {
      bulkError.value = err.response.data.message
    } else if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      bulkError.value = Object.values(errors).flat().join(', ')
    } else {
      bulkError.value = 'Failed to update status. Please try again.'
    }
    toastError(bulkError.value || 'Failed to update status. Please try again.')
  } finally {
    bulkProcessing.value = false
  }
}

async function handleBulkTransfer() {
  if (!bulkTransferCustomerId.value) {
    bulkError.value = 'Please select a customer'
    return
  }

  bulkProcessing.value = true
  bulkError.value = ''

  try {
    const response = await api.post(`${ADMIN_API_BASE_URL}/licenses/bulk`, {
      license_ids: selectedLicenses.value,
      action: 'transfer',
      new_customer_id: bulkTransferCustomerId.value
    })

    toastSuccess(response.data.message || 'Licenses transferred successfully')
    selectedLicenses.value = []
    showBulkTransferModal.value = false
    await fetchLicenses()
  } catch (err: any) {
    if (err.response?.data?.message) {
      bulkError.value = err.response.data.message
    } else if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      bulkError.value = Object.values(errors).flat().join(', ')
    } else {
      bulkError.value = 'Failed to transfer licenses. Please try again.'
    }
    toastError(bulkError.value || 'Failed to transfer licenses. Please try again.')
  } finally {
    bulkProcessing.value = false
  }
}

async function handleBulkRenew() {
  if (!bulkRenewForm.value.period_value || !bulkRenewForm.value.period_unit) {
    bulkError.value = 'Please fill in all renewal fields'
    return
  }

  bulkProcessing.value = true
  bulkError.value = ''

  try {
    const response = await api.post(`${ADMIN_API_BASE_URL}/licenses/bulk`, {
      license_ids: selectedLicenses.value,
      action: 'renew',
      renewal_period_value: bulkRenewForm.value.period_value,
      renewal_period_unit: bulkRenewForm.value.period_unit
    })

    toastSuccess(response.data.message || 'Licenses renewed successfully')
    selectedLicenses.value = []
    showBulkRenewModal.value = false
    await fetchLicenses()
  } catch (err: any) {
    if (err.response?.data?.message) {
      bulkError.value = err.response.data.message
    } else if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      bulkError.value = Object.values(errors).flat().join(', ')
    } else {
      bulkError.value = 'Failed to renew licenses. Please try again.'
    }
    toastError(bulkError.value || 'Failed to renew licenses. Please try again.')
  } finally {
    bulkProcessing.value = false
  }
}

async function handleBulkDelete() {
  const confirmed = await confirmAction(
    `Are you sure you want to delete ${selectedLicenses.value.length} license(s)? This action cannot be undone.`
  )

  if (!confirmed) {
    return
  }

  bulkProcessing.value = true
  bulkError.value = ''

  try {
    const response = await api.post(`${ADMIN_API_BASE_URL}/licenses/bulk`, {
      license_ids: selectedLicenses.value,
      action: 'delete'
    })

    toastSuccess(response.data.message || 'Licenses deleted successfully')
    selectedLicenses.value = []
    await fetchLicenses()
  } catch (err: any) {
    if (err.response?.data?.message) {
      bulkError.value = err.response.data.message
    } else if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      bulkError.value = Object.values(errors).flat().join(', ')
    } else {
      bulkError.value = 'Failed to delete licenses. Please try again.'
    }
    toastError(bulkError.value || 'Failed to delete licenses. Please try again.')
  } finally {
    bulkProcessing.value = false
  }
}

const route = useRoute()

// Watch for route changes to refresh data when returning to this page
watch(() => route.name, (newName) => {
  if (newName === 'Licenses') {
    fetchLicenses()
    fetchMetadata()
  }
}, { immediate: true })

onMounted(() => {
  fetchLicenses()
  fetchMetadata()
})
</script>

